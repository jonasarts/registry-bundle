Setting up the bundle
=====================

## Install the bundle

First add the bundle to your composer.json file: 

```json
{
    // ...
    "require": {
        // ...
        "jonasarts/registry-bundle": "1.0.*"
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

You have two choices to enable the bundle. You can either enable the bundle in the kernel or just register the service in the config.

### Enable the bundle via the kernel

Enable the bundle in the kernel:

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

To use the RegistryController, register also the routes in *app/config/routing.yml*:

```yaml
ja_registry:
    resource: "@RegistryBundle/Controller/"
    type:     annotation
    prefix:   /
```

This will generate two routes to manage the registry keys: ``_registry`` and ``_system``.

### Enable the service via the config

You don't need to enable the bundle in *app/AppKernel.php*. You only need to register the service in *app/config/config.yml*:

```yaml
services:
    // ...
    registry_manager:
        class: jonasarts\Bundle\RegistryBundle\RegistryManager
        arguments: [ @doctrine.orm.entity_manager ]
```

Additionaly, you have to register the entities for the entity manager in *app/config/config.yml*:

```yml
doctrine:
    // ...

    orm:
        auto_generate_proxy_classes: %kernel.debug%
        auto_mapping: true
        mappings:
            registry-bundle:
                type: annotation
                dir: %kernel.root_dir%/../vendor/jonasarts/registry-bundle/jonasarts/Bundle/RegistryBundle/Entity
                prefix: jonasarts\Bundle\RegistryBundle\Entity
                alias: RegistryBundle
                is_bundle: false
```

Do not use the RegistryController. (Do not register the RegistryController routes in *app/config/routing.yml*.)

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