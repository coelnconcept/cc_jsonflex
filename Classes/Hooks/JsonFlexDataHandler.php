<?php

declare(strict_types=1);

namespace CoelnConcept\CcJsonflex\Hooks;

/*
 *
 * This file is part of the "CC JSON Flex" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2026
 *
 */

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\ArrayUtility;

class JsonFlexDataHandler implements SingletonInterface {

	/**
	 * @param array $fieldArray : The array of fields and values that have been saved to the datamap
	 * @param string $table : The name of the table the data should be saved to
	 * @param int $id : The uid of the page we are currently working on
	 * @param \TYPO3\CMS\Core\DataHandling\DataHandler $parentObj : The parent object that triggered this hook
	 *
	 * @return void
	 */
	public function processDatamap_preProcessFieldArray(&$fieldArray, $table, $id, \TYPO3\CMS\Core\DataHandling\DataHandler $parentObj) {
		foreach ($GLOBALS['TCA'][$table]['columns']??[] as $field=>$config) {
			if (($config['config']['type']??null) == 'user' && ($config['config']['renderType']??null) == 'jsonFlex' && is_array($fieldArray[$field]??null)) {
				if (!str_contains((string)$id, 'NEW') && ($config['config']['mergeDataOnUpdate']??null)) {
					$currentValue = BackendUtility::getRecord($table, $id, $field, '', false)[$field] ?: [];
					if ($currentValue) $currentValue = json_decode($currentValue, true, 512) ?: [];
					if ($currentValue && is_array($currentValue)) {
						ArrayUtility::mergeRecursiveWithOverrule($currentValue, $fieldArray[$field]);
						$fieldArray[$field] = $currentValue;
					}
				}
				$fieldArray[$field] = json_encode($fieldArray[$field]);
			}
		}
	}
}