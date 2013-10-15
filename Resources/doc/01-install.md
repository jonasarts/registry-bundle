Setting up the bundle
=====================

## Install the bundle

First add the bundle to your composer.json file: 

```json
{
    // ...
    "require": {
        // ...
        "jonasarts/registry-bundle": "dev-master"
    },
    // ...
}
```

Then run composer.phar:

``` bash
$ php composer.phar install
```

## Enable the bundle

Finally, enable the bundle in the kernel:

```php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = array(
        // ...
        new jonasarts\Bundle\RegistryBundle\RegistryBundle(),
    );
}
```

And register the RegistryManager as a service in *app/config/config.yml*:

```yaml
services:
    // ...
    registry_manager:
        class: jonasarts\Bundle\RegistryBundle\RegistryManager
        arguments: [ @doctrine.orm.entity_manager ]
```

## Create the default key/name-values

This is **optional**, but highly recommended.
If you wish to use a central place to store all default values, create the file *app/config/registry.yml*:

```yaml
registry:
    registrykey/name: value
    settings/page_size: 10
    settings/language: de_DE
    multi/path/separator/with/name: multi path separator with name value string

system:
    systemkey/name: value
    some/bln/value: true
    some/int/value: 5
    some/str/value: a string
    some/flt/value: 0.5
    some/dat/value: 2013-10-15
```

Or you provide the required default values by the registry manager api calls.

## That's it

Check out the docs for information on how to use the bundle! [Return to the index.](index.md)