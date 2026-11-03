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
use TYPO3\CMS\Core\Configuration\Tca\TcaMigration;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\SysLog\Action\Database as SystemLogDatabaseAction;
use TYPO3\CMS\Core\SysLog\Error as SystemLogErrorClassification;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class JsonFlexDataHandler implements SingletonInterface {

	/**
	 * Sub-column types that are processed by DataHandler::checkValue_SW(),
	 * like core does for FlexForm fields. Types that require a real database
	 * column or relation table (inline, file, category, slug, MM relations)
	 * are stored as submitted.
	 */
	protected const PROCESSED_TYPES = ['input', 'check', 'radio', 'select', 'text', 'folder', 'group', 'datetime', 'number', 'email', 'link', 'color'];

	/**
	 * @var array<string, array{jsonFlexFields: list<string>, hasColumnsOverrides: bool}>
	 */
	private array $tableInfoCache = [];

	/**
	 * @param array $fieldArray : The array of fields and values that have been saved to the datamap
	 * @param string $table : The name of the table the data should be saved to
	 * @param int $id : The uid of the page we are currently working on
	 * @param \TYPO3\CMS\Core\DataHandling\DataHandler $parentObj : The parent object that triggered this hook
	 *
	 * @return void
	 */
	public function processDatamap_preProcessFieldArray(&$fieldArray, $table, $id, DataHandler $parentObj) {
		$tableInfo = $this->getTableInfo((string)$table);
		// Skip tables without jsonFlex fields and records without submitted jsonFlex data
		$submittedJsonFlexFields = array_filter($tableInfo['jsonFlexFields'], static fn(string $field): bool => is_array($fieldArray[$field] ?? null));
		if ($submittedJsonFlexFields === []) {
			return;
		}
		$columns = $GLOBALS['TCA'][$table]['columns'] ?? [];
		$isNew = str_contains((string)$id, 'NEW');
		$currentRecord = null;
		// The form renders the type dependent configuration including columnsOverrides,
		// so the submitted data has to be processed with the same configuration.
		if ($tableInfo['hasColumnsOverrides']) {
			$currentRecord = $isNew ? [] : (BackendUtility::getRecord($table, $id, '*', '', false) ?? []);
			$columns = $this->applyColumnsOverrides($table, $columns, array_merge($currentRecord, $fieldArray));
		}
		foreach ($columns as $field=>$config) {
			if (!$this->isJsonFlex($config['config'] ?? []) || !is_array($fieldArray[$field] ?? null)) {
				continue;
			}
			$fieldConfig = $config['config'];

			// Currently stored value, needed to keep values that fail validation and for mergeDataOnUpdate
			$storedValue = [];
			$storedValueIsInvalid = false;
			if (!$isNew) {
				$currentRecord ??= BackendUtility::getRecord($table, $id, '*', '', false) ?? [];
				$rawValue = $currentRecord[$field] ?? null;
				if (is_string($rawValue) && $rawValue !== '') {
					$decoded = json_decode($rawValue, true, 512);
					if (is_array($decoded)) {
						$storedValue = $decoded;
					} elseif (json_last_error() !== JSON_ERROR_NONE || $decoded !== null) {
						$storedValueIsInvalid = true;
					}
				}
			}

			if ($storedValueIsInvalid && ($fieldConfig['mergeDataOnUpdate'] ?? null)) {
				// The stored data cannot be merged. Overwriting it would destroy data the
				// editor has never seen (the field is rendered empty), so keep it untouched.
				unset($fieldArray[$field]);
				$parentObj->log($table, (int)$id, SystemLogDatabaseAction::UPDATE, null, SystemLogErrorClassification::USER_ERROR,
					'Field "{field}" of record {table}:{uid} contains invalid JSON and is configured with mergeDataOnUpdate. The submitted data was not saved.',
					null, ['field' => $field, 'table' => $table, 'uid' => $id]);
				continue;
			}

			$fieldArray[$field] = $this->processSubFields($fieldArray[$field], $fieldConfig['columns'] ?? [], $storedValue, $table, $id, $field, $parentObj);
			if (!$isNew && ($fieldConfig['mergeDataOnUpdate'] ?? null) && $storedValue) {
				$fieldArray[$field] = $this->mergeSubFields($storedValue, $fieldArray[$field], $fieldConfig['columns'] ?? []);
			}
			$fieldArray[$field] = json_encode($fieldArray[$field], JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE);
		}
	}

	/**
	 * Runs sub-fields through the DataHandler, like core FlexForms do
	 * for their pseudo fields. Nested jsonFlex columns are handled recursively.
	 * Submitted keys that are not configured in $columns are dropped.
	 */
	private function processSubFields(array $values, array $columns, array $storedValues, string $table, int|string $id, string $path, DataHandler $dataHandler): array {
		$columns = $this->migrateColumns($table, $columns);
		$result = [];
		foreach ($values as $name=>$value) {
			if (!isset($columns[$name])) {
				continue;
			}
			$config = $columns[$name]['config']??[];
			if (in_array($config['type']??'', self::PROCESSED_TYPES, true) && !($config['MM']??'')) {
				// $field must be empty for pseudo fields, the path goes into $recFID (table:uid:field)
				$checkResult = $dataHandler->checkValue_SW([], $value, $config, $table, $id, '', '', 0, $table.':'.$id.':'.$path.'.'.$name, '', 0);
				if (array_key_exists('value', $checkResult)) {
					$result[$name] = $checkResult['value'];
				} elseif (array_key_exists($name, $storedValues)) {
					// Validation failed: keep the stored value, like core does for regular columns
					$result[$name] = $storedValues[$name];
				}
			} elseif ($this->isJsonFlex($config)) {
				if (is_array($value)) {
					$result[$name] = $this->processSubFields($value, $config['columns']??[], is_array($storedValues[$name] ?? null) ? $storedValues[$name] : [], $table, $id, $path.'.'.$name, $dataHandler);
				} elseif (array_key_exists($name, $storedValues)) {
					$result[$name] = $storedValues[$name];
				}
			} else {
				$result[$name] = $value;
			}
		}
		return $result;
	}

	private function isJsonFlex(array $config): bool {
		return ($config['type']??'') === 'user' && ($config['renderType']??'') === 'jsonFlex';
	}

	/**
	 * Determines per table which columns can be jsonFlex fields, either in the base TCA
	 * or via columnsOverrides of any record type, and whether the table has columnsOverrides.
	 * The result is cached, as the hook is called for every record that is saved.
	 *
	 * @return array{jsonFlexFields: list<string>, hasColumnsOverrides: bool}
	 */
	private function getTableInfo(string $table): array {
		if (isset($this->tableInfoCache[$table])) {
			return $this->tableInfoCache[$table];
		}
		$columns = $GLOBALS['TCA'][$table]['columns'] ?? [];
		$jsonFlexFields = [];
		foreach ($columns as $field=>$config) {
			if ($this->isJsonFlex($config['config'] ?? [])) {
				$jsonFlexFields[$field] = true;
			}
		}
		$hasColumnsOverrides = false;
		foreach ($GLOBALS['TCA'][$table]['types'] ?? [] as $type) {
			if (empty($type['columnsOverrides']) || !is_array($type['columnsOverrides'])) {
				continue;
			}
			$hasColumnsOverrides = true;
			foreach ($type['columnsOverrides'] as $field=>$override) {
				if (!isset($columns[$field]) || !is_array($override['config'] ?? null)) {
					continue;
				}
				$config = $columns[$field]['config'] ?? [];
				ArrayUtility::mergeRecursiveWithOverrule($config, $override['config']);
				if ($this->isJsonFlex($config)) {
					$jsonFlexFields[$field] = true;
				}
			}
		}
		return $this->tableInfoCache[$table] = [
			'jsonFlexFields' => array_map('strval', array_keys($jsonFlexFields)),
			'hasColumnsOverrides' => $hasColumnsOverrides,
		];
	}

	/**
	 * Applies the columnsOverrides of the record type, like the FormDataProvider
	 * TcaColumnsOverrides does for rendering.
	 */
	private function applyColumnsOverrides(string $table, array $columns, array $row): array {
		$recordType = (string)BackendUtility::getTCAtypeValue($table, $row);
		foreach ($GLOBALS['TCA'][$table]['types'][$recordType]['columnsOverrides'] ?? [] as $field=>$override) {
			if (isset($columns[$field]) && is_array($override)) {
				$columns[$field] = array_replace_recursive($columns[$field], $override);
			}
		}
		return $columns;
	}

	/**
	 * Applies the TCA migrations to the sub-columns of one level, like
	 * JsonFlexContainer does for rendering. Sub-columns are not part of
	 * $GLOBALS['TCA'][$table]['columns'] and are therefore not migrated during
	 * bootstrap, e.g. select items in the legacy format ['label', 'value'].
	 * Nested jsonFlex columns are migrated when their level is processed.
	 */
	private function migrateColumns(string $table, array $columns): array {
		if ($columns === []) {
			return [];
		}
		// TcaMigration::migrate() returns a TcaProcessingResult object since TYPO3 v14, an array before
		$migrationResult = GeneralUtility::makeInstance(TcaMigration::class)->migrate([$table => ['columns' => $columns]]);
		$migratedTca = is_array($migrationResult) ? $migrationResult : $migrationResult->getTca();
		return $migratedTca[$table]['columns'] ?? $columns;
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
			if ($this->isJsonFlex($config) && is_array($value) && is_array($currentValues[$name]??null) && ($config['mergeDataOnUpdate']??null)) {
				$currentValues[$name] = $this->mergeSubFields($currentValues[$name], $value, $config['columns']??[]);
			} else {
				$currentValues[$name] = $value;
			}
		}
		return $currentValues;
	}
}
