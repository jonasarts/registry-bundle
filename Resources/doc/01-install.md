Setting up the bundle
=====================

## Install the bundle

First add the bundle to your composer.json file: 

```json
{
    // ...
    "require": {
        // ...
        "jonasarts/registry-bundle": "~1.2"
    },
    "minimum-stability": "stable",
    // ...
}
```

Then run composer.phar:

``` bash
$ php composer.phar install
```

## Enable the bundle

Register the bundle in the kernel:

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

To use the RegistryController, register also the routes in *app/config/routing.yml* or *app/config/routing_dev.yml* (this is optional and can only be used on the 'doctrine' mode registry keys):

```yaml
_registry:
    resource: "@RegistryBundle/Controller/"
    type:     annotation
    prefix:   /
```

This will generate two routes to manage the registry keys: ``_registry`` and ``_system``.

## Configuration options

[Read the bundle configuration options](02-configuration.md)

## Create the default key/name-values

If you wish to use a central place to store all application defined default values, create the defaultkeys file *app/config/registry.yml* (or any other yaml file as configured):

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

This is **optional**, but highly recommended. On using doctrine as database engine and if the defaultkeys file is found, the default values are auto-enabled. On redis as database engine, no default values will be used. To override this behavior, call ``setDefaultKeysEnabled()`` on the registry object. 
Or just provide the required default values by the registry api calls (use ``ReadRegistryDefault()`` instead of ``ReadRegistry()``).

## That's it

Check out the docs for information on how to use the bundle! [Return to the index.](index.md)