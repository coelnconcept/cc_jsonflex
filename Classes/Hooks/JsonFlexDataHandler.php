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
use TYPO3\CMS\Core\DataHandling\DataHandler;

class JsonFlexDataHandler implements SingletonInterface {

	/**
	 * Sub-column types that are processed by DataHandler::checkValue_SW(),
	 * like core does for FlexForm fields. Types that require a real database
	 * column or relation table (inline, file, category, slug, MM relations)
	 * are stored as submitted.
	 */
	protected const PROCESSED_TYPES = ['input', 'check', 'radio', 'select', 'text', 'folder', 'group', 'datetime', 'number', 'email', 'link', 'color'];

	/**
	 * @param array $fieldArray : The array of fields and values that have been saved to the datamap
	 * @param string $table : The name of the table the data should be saved to
	 * @param int $id : The uid of the page we are currently working on
	 * @param \TYPO3\CMS\Core\DataHandling\DataHandler $parentObj : The parent object that triggered this hook
	 *
	 * @return void
	 */
	public function processDatamap_preProcessFieldArray(&$fieldArray, $table, $id, DataHandler $parentObj) {
		foreach ($GLOBALS['TCA'][$table]['columns']??[] as $field=>$config) {
			if (($config['config']['type']??null) == 'user' && ($config['config']['renderType']??null) == 'jsonFlex' && is_array($fieldArray[$field]??null)) {
				$fieldArray[$field] = $this->processSubFields($fieldArray[$field], $config['config']['columns']??[], $table, $id, $field, $parentObj);
				if (!str_contains((string)$id, 'NEW') && ($config['config']['mergeDataOnUpdate']??null)) {
					$currentValue = BackendUtility::getRecord($table, $id, $field, '', false)[$field] ?? [];
					if ($currentValue) $currentValue = json_decode($currentValue, true, 512) ?: [];
					if ($currentValue && is_array($currentValue)) {
						$fieldArray[$field] = $this->mergeSubFields($currentValue, $fieldArray[$field], $config['config']['columns']??[]);
					}
				}
				$fieldArray[$field] = json_encode($fieldArray[$field]);
			}
		}
	}

	/**
	 * Runs sub-fields through the DataHandler, like core FlexForms do
	 * for their pseudo fields. Nested jsonFlex columns are handled recursively.
	 */
	private function processSubFields(array $values, array $columns, string $table, int|string $id, string $path, DataHandler $dataHandler): array {
		foreach ($columns as $name=>$column) {
			if (!array_key_exists($name, $values)) {
				continue;
			}
			$config = $column['config']??[];
			if (in_array($config['type']??'', self::PROCESSED_TYPES, true) && !($config['MM']??'')) {
				// $field must be empty for pseudo fields, the path goes into $recFID (table:uid:field)
				$result = $dataHandler->checkValue_SW([], $values[$name], $config, $table, $id, '', '', 0, $table.':'.$id.':'.$path.'.'.$name, '', 0);
				if (array_key_exists('value', $result)) {
					$values[$name] = $result['value'];
				} else {
					unset($values[$name]);
				}
			} elseif (($config['type']??'') === 'user' && ($config['renderType']??'') === 'jsonFlex' && is_array($values[$name])) {
				$values[$name] = $this->processSubFields($values[$name], $config['columns']??[], $table, $id, $path.'.'.$name, $dataHandler);
			}
		}
		return $values;
	}

	/**
	 * Merges submitted jsonFlex data into the stored data.
	 * Only nested jsonFlex columns are merged recursively, every other submitted
	 * sub-field replaces its stored value completely. Keys that were not
	 * submitted (e.g. auto-created data) are kept.
	 */
	private function mergeSubFields(array $currentValues, array $fieldArray, array $columns): array {
		foreach ($fieldArray as $name=>$value) {
			$config = $columns[$name]['config']??[];
			if (($config['type']??'') === 'user' && ($config['renderType']??'') === 'jsonFlex' && is_array($value) && is_array($currentValues[$name]??null) && ($config['mergeDataOnUpdate']??null)) {
				$currentValues[$name] = $this->mergeSubFields($currentValues[$name], $value, $config['columns']??[]);
			} else {
				$currentValues[$name] = $value;
			}
		}
		return $currentValues;
	}
}