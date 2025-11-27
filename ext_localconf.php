<?php

defined('TYPO3') or die('Access denied.');

call_user_func(function () {
	$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][\CoelnConcept\CcJsonflex\Form\Container\JsonFlexContainer::class] = [
		'nodeName' => 'jsonFlex',
		'priority' => 40,
		'class' => \CoelnConcept\CcJsonflex\Form\Container\JsonFlexContainer::class,
	];
	$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][\CoelnConcept\CcJsonflex\Form\Container\JsonFlexTabsContainer::class] = [
		'nodeName' => 'jsonFlexTabsContainer',
		'priority' => 40,
		'class' => \CoelnConcept\CcJsonflex\Form\Container\JsonFlexTabsContainer::class,
	];
	$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][\CoelnConcept\CcJsonflex\Form\Container\JsonFlexNoTabsContainer::class] = [
		'nodeName' => 'jsonFlexNoTabsContainer',
		'priority' => 40,
		'class' => \CoelnConcept\CcJsonflex\Form\Container\JsonFlexNoTabsContainer::class,
	];
	$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][\CoelnConcept\CcJsonflex\Form\Container\SingleFieldContainer::class] = [
		'nodeName' => 'singleFieldContainer',
		'priority' => 60,
		'class' => \CoelnConcept\CcJsonflex\Form\Container\SingleFieldContainer::class,
	];
	
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = \CoelnConcept\CcJsonflex\Hooks\JsonFlexDataHandler::class;
	
});