.. include:: ../Includes.txt

.. _introduction:

============
Introduction
============

`TYPO3 <https://typo3.org/>`_ uses `FlexForms <https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ApiOverview/FlexForms/Index.html>`_ to store dynamic structured content in one database column, using XML as format.

Our approach is to use the widely available format JSON and the well-established TCA configuration.

.. _what-it-does:

What does it do?
================

This extension provides the renderType *jsonFlex* for the TCA configuration to extend a database field into multiple dynamic sub-fields.

.. important::

   Please read the :ref:`administrator manual <administrator>` for usage and examples.
