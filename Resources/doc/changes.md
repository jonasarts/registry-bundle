CHANGE LOG
==========

V 1.2.3
-------

- Fixed issue #9, RegistryRead() and SystemRead() validates the array loaded from registry.yml 

V 1.2.2
-------

- Added RegistryKeyExists() and SystemKeyExists()

V 1.2.1
-------

- Fixed issue #7, missing $type in RegistryWrite()

V 1.2.0
-------

- Changed redis engine to use hash functions
- Added RegistryReadOnce() and SystemReadOnce() methods
- Added getRegistryItems() and getSystemItems() methods
- Added missing $type field to RegistryDelete() and SystemDelete() methods

V 1.1.0
-------

- Added redis engine
- Added isMode(), isMode*() and setMode() methods
