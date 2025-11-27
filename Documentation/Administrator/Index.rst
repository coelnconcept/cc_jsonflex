.. include:: ../Includes.txt

.. _administrator:

====================
Administrator Manual
====================

.. _administrator-usage:

Usage
=====

.. _administrator-usage-tca:

TCA
---

Configuation:
"""""""""""""

- :php:`'type' => 'user'` Do not use :php:`json` as type, it would result in special issues. :php:`user` is the correct type.
- :php:`'renderType' => 'jsonFlex'`
- :php:`'columns' => [ ... ]` configure the sub-columns
- Optional: :php:`'mergeDataOnUpdate' => true` If this option is set, already existing JSON data will be merged. This is especially useful, if you have a mix of auto-created data and parts you want to edit.

Example 1:
""""""""""

Simple fields

.. code-block:: php

   'jsondata' => [
   	'label' => 'Database column for example 1',
   	'config' => [
   		'type' => 'user',
   		'renderType' => 'jsonFlex',
   		'columns' => [
   			'placeholder' => [
   				'label' => 'JSON field: simple text input',
   				'config' => [
   					'type' => 'input',
   					'size' => 30,
   					'eval' => 'trim',
   					'default' => ''
   				],
   			],
   			'required' => [
   				'label' => 'JSON field: simple checkbox',
   				'config' => [
   					'type' => 'check',
   				],
   			],
   			'mediatype' => [
   				'label' => 'JSON field: simple select',
   				'config' => [
   					'type' => 'select',
   					'renderType' => 'selectSingle',
   					'items' => [
   						['', ''],
   						['Image', 'image'],
   						['Audio', 'audio'],
   						['Video', 'video'],
   					],
   				],
   			],
   		],
   	]
   ]

Example 2:
""""""""""

Using `Record Types <https://docs.typo3.org/m/typo3/reference-tca/main/en-us/Types/Index.html>`_ and palettes.

**Important**: The type column has to be a real database column, it cannot be a JsonFlex field.

.. code-block:: php

   'type' => [
   	'label' => 'Dataset type, like CType in tt_content',
   	'config' => [
   		'type' => 'select',
   		'renderType' => 'selectSingle',
   		'items' => [
   			['', 'default'],
   			['Container element', 'container'],
   			['Special content', 'special'],
   		]
   	]
   ],
   'datafields' => [
   	'label' => 'Database column for example 2',
   	'config' => [
   		'type' => 'user',
   		'renderType' => 'jsonFlex',
   		'types' => [
   			'default' => ['showitem' => '--palette--;;inputPalette, mediatype'],
   			'container' => ['showitem' => 'mediatype'],
   			'special' => ['showitem' => '--palette--;;inputPalette'],
   		],
   		'palettes' => [
   			'inputPalette' => [
   				'label' => 'Palette with placeholder and required-checkbox',
   				'showitem' => 'placeholder, required',
   			],
   		],
   		'columns' => [
   			'placeholder' => [
   				'label' => 'JSON field: simple text input',
   				'config' => [
   					'type' => 'input',
   					'size' => 30,
   					'eval' => 'trim',
   					'default' => ''
   				],
   			],
   			'required' => [
   				'label' => 'JSON field: simple checkbox',
   				'config' => [
   					'type' => 'check',
   				],
   			],
   			'mediatype' => [
   				'label' => 'JSON field: simple select',
   				'config' => [
   					'type' => 'select',
   					'renderType' => 'selectSingle',
   					'items' => [
   						['', ''],
   						['Image', 'image'],
   						['Audio', 'audio'],
   						['Video', 'video'],
   					],
   				],
   			],
   		],
   	]
   ]


Example 3:
""""""""""

Nested JsonFlex columns.

.. code-block:: php

   'datafields' => [
   	'label' => 'Database column for example 2',
   	'config' => [
   		'type' => 'user',
   		'renderType' => 'jsonFlex',
   		'columns' => [
   			'placeholder' => [
   				'label' => 'JSON field: simple text input',
   				'config' => [
   					'type' => 'input',
   					'size' => 30,
   					'eval' => 'trim',
   					'default' => ''
   				],
   			],
   			'other' => [
   				'label' => 'JSON field: nested jsonFlex',
   				'config' => [
					'type' => 'user',
					'renderType' => 'jsonFlex',
					'columns' => [
						...
					]
   				],
   			],
   		],
   	]
   ]


What works
----------

- Column-Types *input*, *check*, *select*, *text*, *folder*, *group*
- `Record Types <https://docs.typo3.org/m/typo3/reference-tca/main/en-us/Types/Index.html>`_ and palettes.

What does not works
-------------------

- Column-Type *category*

What is not testet
------------------

- Everything else
