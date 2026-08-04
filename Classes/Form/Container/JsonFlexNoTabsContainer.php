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

use TYPO3\CMS\Backend\Form\Container\AbstractContainer;

/**
 * Handle a record that has no tabs.
 *
 * This container is called by FullRecordContainer and just wraps the output
 * of PaletteAndSingleContainer in some HTML.
 */
class JsonFlexNoTabsContainer extends AbstractContainer
{
	/**
	 * Default field information enabled for this element.
	 *
	 * @var array
	 */
	protected $defaultFieldInformation = [
		'tcaDescription' => [
			'renderType' => 'tcaDescription',
		],
	];
	
	/**
	 * Entry method
	 *
	 * @return array As defined in initializeResultArray() of AbstractNode
	 */
	public function render(): array
	{
		$resultArray = $this->initializeResultArray();
		
		$fieldInformationResult = $this->renderFieldInformation();
		$resultArray['html'] = '<div>' . $fieldInformationResult['html'] . '</div>';
		$resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldInformationResult, false);
		
		$options = $this->data;
		$options['renderType'] = 'paletteAndSingleContainer';
		$childResult = $this->nodeFactory->create($options)->render();
		$resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $childResult, true);
		$resultArray['html'] = '<div class="tab-content">' . $resultArray['html'] . '</div>';
		return $resultArray;
	}
}
