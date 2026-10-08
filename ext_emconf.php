<?php

/***************************************************************
 * Extension Manager/Repository config file for ext: "cc_jsonflex"
 *
 * Manual updates:
 * Only the data in the array - anything else is removed by next write.
 * "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = [
    'title' => 'jsonFlex JSON-Flexform',
    'description' => 'Provides jsonFlex datatype in TCA for JSON-Flexform',
    'category' => 'be',
    'author' => 'Coeln Concept GmbH',
    'author_email' => 'tda@coelnconcept.de',
    'state' => 'beta',
    'clearCacheOnLoad' => 0,
    'version' => '1.2.0',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.0-14.3.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
