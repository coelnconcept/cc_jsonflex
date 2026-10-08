..  include:: /Includes.rst.txt

..  _administrator:

====================
Administrator Manual
====================

..  _administrator-usage:

Usage
=====

..  _administrator-usage-tca:

TCA
---

..  _administrator-usage-tca-configuration:

Configuration
~~~~~~~~~~~~~

..  confval:: type
    :name: jsonflex-type
    :type: string
    :required: true

    Must be :php:`'user'`. Do not use :php:`'json'` as type, it would result in
    special issues.

..  confval:: renderType
    :name: jsonflex-renderType
    :type: string
    :required: true

    Must be :php:`'jsonFlex'`.

..  confval:: columns
    :name: jsonflex-columns
    :type: array
    :required: true

    Configures the sub-columns, using the same syntax as
    :ref:`TCA columns <t3tca:columns>`.

..  confval:: mergeDataOnUpdate
    :name: jsonflex-mergeDataOnUpdate
    :type: boolean
    :default: false

    If this option is set, already existing JSON data will be merged. This is
    especially useful if you have a mix of auto-created data and parts you want
    to edit.

..  _administrator-usage-tca-example-1:

Example 1: Simple fields
~~~~~~~~~~~~~~~~~~~~~~~~

..  code-block:: php

    'jsondata' => [
        'label' => 'Database column for example 1',
        'config' => [
            'type' => 'user',
            'renderType' => 'jsonFlex',
            'types' => [
                '1' => ['showitem' => 'placeholder, required, mediatype'],
            ],
            'columns' => [
                'placeholder' => [
                    'label' => 'JSON field: simple text input',
                    'config' => [
                        'type' => 'input',
                        'size' => 30,
                        'eval' => 'trim',
                        'default' => '',
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
        ],
    ]

..  _administrator-usage-tca-example-2:

Example 2: Record types and palettes
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Using :ref:`record types <t3tca:types>` and palettes.

..  important::

    The type column has to be a real database column, it cannot be a JsonFlex
    field.

..  code-block:: php

    'type' => [
        'label' => 'Dataset type, like CType in tt_content',
        'config' => [
            'type' => 'select',
            'renderType' => 'selectSingle',
            'items' => [
                ['', 'default'],
                ['Container element', 'container'],
                ['Special content', 'special'],
            ],
        ],
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
                        'default' => '',
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
        ],
    ]

..  _administrator-usage-tca-example-3:

Example 3: Nested JsonFlex columns
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

..  code-block:: php

    'datafields' => [
        'label' => 'Database column for example 3',
        'config' => [
            'type' => 'user',
            'renderType' => 'jsonFlex',
            'types' => [
                '1' => ['showitem' => 'placeholder, other'],
            ],
            'columns' => [
                'placeholder' => [
                    'label' => 'JSON field: simple text input',
                    'config' => [
                        'type' => 'input',
                        'size' => 30,
                        'eval' => 'trim',
                        'default' => '',
                    ],
                ],
                'other' => [
                    'label' => 'JSON field: nested jsonFlex',
                    'config' => [
                        'type' => 'user',
                        'renderType' => 'jsonFlex',
                        'types' => [
                            '1' => ['showitem' => '...'],
                        ],
                        'columns' => [
                            // ...
                        ],
                    ],
                ],
            ],
        ],
    ]

..  _administrator-scope:

Scope and limitations
=====================

..  _administrator-scope-works:

What works
----------

*   Column types *input*, *check*, *select*, *text*, *folder*, *group*
*   :ref:`Record types <t3tca:types>` and palettes

..  _administrator-scope-does-not-work:

What does not work
------------------

*   Column type *category*
*   Relations with an :php:`MM` table
*   :php:`behaviour.allowLanguageSynchronization` on sub-columns, see
    :ref:`administrator-scope-localization`

..  _administrator-scope-untested:

What is not tested
------------------

*   Everything else

..  _administrator-scope-access-control:

Access control: :php:`exclude` is not evaluated
-----------------------------------------------

..  warning::

    The :php:`exclude` option is **silently ignored** on JsonFlex sub-columns.
    Do not rely on it to hide a sub-field from backend users.

Sub-columns configured below a JsonFlex field are not real TCA columns of the
table. They therefore never appear in the backend user and group permission
setting :guilabel:`Allowed excludefields`, and there is no permission that could
be granted or denied for them.

Accordingly, the JsonFlex container does not perform the
:php:`non_exclude_fields` check that TYPO3 applies to regular record fields:
setting :php:`'exclude' => 1` (or :php:`true`) on a sub-column has no effect —
the field is rendered and editable for every backend user who may edit the
record itself.

If a value must be restricted to certain backend users, store it in a real
database column with its own TCA configuration instead of a JsonFlex sub-column.

The same applies to :php:`l10n_mode` set to :php:`'exclude'`, which is not
evaluated for sub-columns either.

..  _administrator-scope-invalid-json:

Handling of invalid JSON
------------------------

If the stored value of a JsonFlex column cannot be decoded, the field is
rendered as if it were empty, following the behaviour of the TYPO3 core for its
native :php:`json` column type. No error message is shown to the editor.
