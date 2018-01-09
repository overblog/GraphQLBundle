Inheritance
===========

In some cases, inheritance can help to not repeating yourself, better organisation,
clearer schema, easier interfaces implementation...

Here an example using inheritance:

```yaml
ObjectB:
    type: object
    config:
        interfaces: [InterfaceA]
        fields:
             foo: {type: String!}

# ObjectA inherited config from InterfaceA and ObjectC
ObjectA:
    type: object
    # ObjectB inherited config (fields, args...) from ObjectA
    heirs: [ObjectB]
    inherits: [InterfaceA, ObjectC]

ObjectC:
    type: object
    config:
        fields:
            baz: {type: String!}

InterfaceA:
    type: interface
    config:
        resolveType: '@=...'
        fields:
            bar: {type: String!}
            bar2: {type: String!}
            bar3: {type: String!}
```

This is equivalent to:

```yaml
ObjectB:
    type: object
    config:
        interfaces: [InterfaceA]
        fields:
             foo: {type: String!}
             bar: {type: String!}
             bar2: {type: String!}
             bar3: {type: String!}
             baz: {type: String!}

ObjectA:
    type: object
    config:
        fields:
            bar: {type: String!}
            bar2: {type: String!}
            bar3: {type: String!}
            baz: {type: String!}

ObjectC:
    type: object
    config:
        fields:
            baz: {type: String!}

InterfaceA:
    type: interface
    config:
        resolveType: '@=...'
        fields:
            bar: {type: String!}
            bar2: {type: String!}
            bar3: {type: String!}
```

**Notes:**
 * Inheritance works only with the generated types
 * Type can be inherited even from any type (from different files)
  * Type must be of same type to be extended, only `object` types can also inherit from
    `interface` type
 * `heirs` is the inverse of `inherits` section
 * Inheritance priority is defined by the order in the `inherits` section.
 * Inheritance use internally [array_replace_recursive](http://php.net/manual/en/function.array-replace-recursive.php) php function.
   for example ObjectA config is the result of
   `array_replace_recursive(ObjectCConfig, InterfaceAConfig, ObjectAConfig)`

You can also create decorator types to be used as reusable templates.
Decorators are only virtual and will not exists in final schema.
That is the reason why decorator should never be reference as type in schema definition.

Here is an example of a decorator type:

```yaml
ObjectA:
    decorator: true
    config:
        fields:
            bar: {type: String!}
```
