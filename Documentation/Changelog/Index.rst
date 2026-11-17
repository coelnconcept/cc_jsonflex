..  include:: /Includes.rst.txt

..  _changelog:

==========
Change log
==========

Version 1.2.3
-------------

*   BUGFIX: Permission-check for subfields and non-administration users

Version 1.2.2
-------------

*   BUGFIX: Nested jsonFlex-fields, field descriptions, Fallback record type
*   Processing of columnsOverrides.
*   BREAKING: Keys that are not configured in columns are discarded when
    saving, also when data is written via the DataHandler API.
*   BREAKING: If validation of a sub-field fails, the stored value is kept
    instead of removing the key.

Version 1.2.1
-------------

*   BUGFIX: Process legacy configuration of sub-fields with TcaMigration.

Version 1.2.0
-------------

*   Check of sub-fields with DataHandler.
*   Merge sub-fields with option mergeDataOnUpdate.
*   BREAKING: Datatypes can change by processing via DataHandler, e.g.
    datetime as timestamp
*   BREAKING: Merge of nested jsonFlex columns with mergeDataOnUpdate.

Version 1.1.0
-------------

*   Compatibility with TYPO3 14, while remaining compatible with TYPO3 13.
*   Documentation migrated to the current TYPO3 rendering format.

Version 1.0.0
-------------

Initial release.
