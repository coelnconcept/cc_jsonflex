<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace CoelnConcept\CcJsonflex\Form\Container;

use TYPO3\CMS\Backend\Form\Behavior\ReloadOnFieldChange;
use TYPO3\CMS\Backend\Form\FormDataGroup\TcaDatabaseRecord;
use TYPO3\CMS\Backend\Form\Utility\FormEngineUtility;
use TYPO3\CMS\Core\Authentication\JsConfirmation;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Handle palettes and single fields.
 *
 * This container is called by TabsContainer, NoTabsContainer and ListOfFieldsContainer.
 *
 * This container mostly operates on TCA showItem of a specific type - the value is
 * coming in from upper containers as "fieldArray". It handles palettes with all its
 * different options and prepares rendering of single fields for the SingleFieldContainer.
 */
class SingleFieldContainer extends \TYPO3\CMS\Backend\Form\Container\SingleFieldContainer
{
	/**
	 * Final result array accumulating results from children and final HTML
	 */
	protected array $resultArray = [];

	/**
	 * Entry method
	 *
	 * @return array As defined in initializeResultArray() of AbstractNode
	 */
	public function render(): array
	{
		if (isset($this->data['jsonFlexParentFields'])) {
			return $this->renderJsonFlex();
		}
		
		return parent::render();
	}
	
	/**
	 * Entry method
	 *
	 * @return array As defined in initializeResultArray() of AbstractNode
	 */
	protected function renderJsonFlex(): array
	{
		$resultArray = $this->initializeResultArray();

		$table = $this->data['tableName'];
		$row = $this->data['databaseRow'];
		$fieldName = $this->data['fieldName'];

		$parameterArray = [];
		$parameterArray['fieldConf'] = $this->data['processedTca']['columns'][$fieldName];

		$isOverlay = false;

		// This field decides whether the current record is an overlay (as opposed to being a standalone record)
		// Based on this decision we need to trigger field exclusion or special rendering (like readOnly)
		if (isset($this->data['jsonFlexParentFields'][0]['processedTca']['ctrl']['transOrigPointerField'])
			&& is_array($this->data['jsonFlexParentFields'][0]['processedTca']['columns'][$this->data['jsonFlexParentFields'][0]['processedTca']['ctrl']['transOrigPointerField']] ?? null)
		) {
			$parentValue = $this->data['jsonFlexParentFields'][0]['databaseRow'][$this->data['jsonFlexParentFields'][0]['processedTca']['ctrl']['transOrigPointerField']];
			if (MathUtility::canBeInterpretedAsInteger($parentValue)) {
				$isOverlay = (bool)$parentValue;
			} elseif (is_array($parentValue)) {
				// This case may apply if the value has been converted to an array by the select or group data provider
				$isOverlay = !empty($parentValue) ? (bool)$parentValue[0] : false;
			} else {
				throw new \InvalidArgumentException(
					'The given value "' . $parentValue . '" for the original language field ' . $this->data['jsonFlexParentFields'][0]['processedTca']['ctrl']['transOrigPointerField']
					. ' of table ' . $table . ' is invalid.',
					1470742770
				);
			}
			$this->data['defaultLanguageRow'] = null;
		}
		
		$tsConfig = $this->data['pageTsConfig']['TCEFORM.'][$table . '.'] ?? [];
		foreach ($this->data['jsonFlexParentFields'] as $jsonFlexParentField) {
			$tsConfig = $tsConfig[$jsonFlexParentField['fieldName'] . '.'] ?? [];
		}
		$tsConfig = $tsConfig[$fieldName . '.'] ?? [];
		$parameterArray['fieldTSConfig'] = is_array($tsConfig) ? $tsConfig : [];

		if ($parameterArray['fieldTSConfig']['disabled'] ?? false) {
			return $resultArray;
		}

		// Override fieldConf by fieldTSconfig:
		$parameterArray['fieldConf']['config'] = FormEngineUtility::overrideFieldConf($parameterArray['fieldConf']['config'], $parameterArray['fieldTSConfig']);
		$parameterArray['itemFormElName'] = 'data[' . $table . '][' . $row['uid'] . ']';
		foreach ($this->data['jsonFlexParentFields'] as $jsonFlexParentField) {
			$parameterArray['itemFormElName'] .= '[' . $jsonFlexParentField['fieldName'] . ']';
		}
		$parameterArray['itemFormElName'] .= '[' . $fieldName . ']';
		$newElementBaseName = '';
		if (isset($this->data['elementBaseName'])) {
			$newElementBaseName = $this->data['elementBaseName'] . '[' . $table . '][' . $row['uid'] . ']';
			foreach ($this->data['jsonFlexParentFields'] as $jsonFlexParentField) {
				$newElementBaseName .= '[' . $jsonFlexParentField['fieldName'] . ']';
			}
			$newElementBaseName .= '[' . $fieldName . ']';
		}

		$formDataCompilerInput = array_intersect_key($this->data, array_flip(['request','tableName','vanillaUid','effectivePid','inlineFirstPid','command','returnUrl','processedTca','databaseRow','recordTypeValue']));
		$formDataCompilerInput['databaseRow']['pid'] = $this->data['effectivePid'];
		if (!empty($this->data['overrideVals']) && is_array($this->data['overrideVals'][$table])) {
			$formDataCompilerInput['overrideValues'] = $this->data['overrideVals'][$table];
		}
		if (!empty($this->data['defaultValues']) && is_array($this->data['defaultValues'])) {
			$formDataCompilerInput['defaultValues'] = $this->data['defaultValues'];
		}
		$formDataCompiler = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Form\FormDataCompiler::class);
		$formData = $formDataCompiler->compile($formDataCompilerInput, GeneralUtility::makeInstance(TcaDatabaseRecord::class));

		// The value to show in the form field.
		$parameterArray['itemFormElValue'] = $formData['databaseRow'][$fieldName]??$row[$fieldName];
		// Set field to read-only if configured for translated records to show default language content as readonly
		// Note: In such case, the database value of this field was already overridden by DatabaseRowDefaultAsReadonly.
		if (($this->data['jsonFlexParentFields'][0]['parameterArray']['fieldConf']['l10n_display'] ?? false)
			&& GeneralUtility::inList($this->data['jsonFlexParentFields'][0]['parameterArray']['fieldConf']['l10n_display'], 'defaultAsReadonly')
			&& $isOverlay
		) {
			$parameterArray['fieldConf']['config']['readOnly'] = true;
		}

		$parameterArray['fieldChangeFunc'] = [];
		if (isset($parameterArray['fieldConf']['onChange']) && $parameterArray['fieldConf']['onChange'] === 'reload') {
			$confirmation = $this->getBackendUserAuthentication()->jsConfirmation(JsConfirmation::TYPE_CHANGE);
			$parameterArray['fieldChangeFunc']['alert'] = new ReloadOnFieldChange($confirmation);
		}
		
		// Based on the type of the item, call a render function on a child element
		$options = $this->data;
		$options['parameterArray'] = $parameterArray;
		$options['elementBaseName'] = $newElementBaseName;
		if (!empty($parameterArray['fieldConf']['config']['renderType'])) {
			$options['renderType'] = $parameterArray['fieldConf']['config']['renderType'];
		} else {
			// Fallback to type if no renderType is given
			$options['renderType'] = $parameterArray['fieldConf']['config']['type'];
		}

		return $this->nodeFactory->create($options)->render();
	}
}
