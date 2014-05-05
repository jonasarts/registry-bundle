Configure the bundle
====================

Since verison 1.1 the registry service can use redis as database engine.

## Configuration options

```yaml
registry:
    globals:
        mode:           doctrine # doctrine / redis
        defaultkeys:    %kernel.root_dir%/config/registry.yml # path and filename for the
                                                              # default key/name-values
        delimiter:      '/'
    redis:
        prefix:         'registry' # prefix redis keys to make them 'unique' 
                                   # if multiple projects are using the same redis instance
        delimiter:      ':'
```

## That's all

[Return to the index.](index.md)