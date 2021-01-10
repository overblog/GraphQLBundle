UPGRADE FROM 0.13 to 1.0
=======================

# Table of Contents

- [UPGRADE FROM 0.13 to 1.0](#upgrade-from-013-to-10)
- [Table of Contents](#table-of-contents)
    - [Customize the cursor encoder of the edges of a connection](#customize-the-cursor-encoder-of-the-edges-of-a-connection)
    - [Change arguments of `TypeGenerator` class](#change-arguments-of-typegenerator-class)
    - [Add magic `__get` method to `ArgumentInterface` implementors](#add-magic-__get-method-to-argumentinterface-implementors)
    - [Annotations - Flattened annotations](#annotations---flattened-annotations)
    - [Annotations - Attributes changed](#annotations---attributes-changed)

### Customize the cursor encoder of the edges of a connection

The connection builder now accepts an optional custom cursor encoder as first argument of the constructor.

```diff
$connectionBuilder = new ConnectionBuilder(
+   new class implements CursorEncoderInterface {
+       public function encode($value): string
+       {
+           ...
+       }
+
+       public function decode(string $cursor)
+       {
+           ...
+       }
+   }
    static function (iterable $edges, PageInfoInterface $pageInfo) {
        ...
    },
    static function (string $cursor, $value, int $index) {
        ...
    }
);
```

### Change arguments of `TypeGenerator` class

The `Overblog\GraphQLBundle\Generator\TypeGenerator` service is used internally for GraphQL types compilation. If you 
overridden the service definition, please take into account the new constructor signature:

```diff
public function __construct(
    string $classNamespace,
-   array $skeletonDirs,
    ?string $cacheDir,
    array $configs,
+   TypeBuilder $typeBuilder
+   EventDispatcherInterface $eventDispatcher
    bool $useClassMap = true,
-   callable $configProcessor = null,
    ?string $baseCacheDir = null,
    ?int $cacheDirMask = null
) {
```
`TypeBuilder` here is a new service `Overblog\GraphQLBundle\Generator\TypeBuilder`, which is also used internally.

### Add magic `__get` method to `ArgumentInterface` implementors

The interface `Overblog\GraphQLBundle\Definition\ArgumentInterface` as well as implementing it class 
`Overblog\GraphQLBundle\Definition\Argument` now have the magic `__get` method:

```diff
interface ArgumentInterface extends ArrayAccess, Countable
{
    /**
     * @return array the old array
     */
    public function exchangeArray(array $array): array;

    public function getArrayCopy(): array;

+   /**
+    * @return mixed
+    */
+   public function __get(string $name);
}

class Argument implements ArgumentInterface
{
    // ...

+   public function __get(string $name)
+   {
+       return $this->rawArguments[$name] ?? null;
+   }
}
```
If you use your own class for resolver arguments, then it should have a `__get` method as well.


### Annotations - Flattened annotations

In order to prepare to PHP 8 attributes (they don't support nested attributes at the moment. @see https://github.com/symfony/symfony/issues/38503), the following annotations have been flattened: `@FieldsBuilder`, `@FieldBuilder`, `@ArgsBuilder`, `@Arg` and `@EnumValue`. 

Before:
```php
/**
 * @GQL\Type
 */
class MyType {
    /**
     * @GQL\Field(args={
     *   @GQL\Arg(name="arg1", type="String"),
     *   @GQL\Arg(name="arg2", type="Int")
     * })
     */
    public function myFields(?string $arg1, ?int $arg2) {..}
}

```

After:
```php
/**
 * @GQL\Type
 */
class MyType {
    /**
     * @GQL\Field
     * @GQL\Arg(name="arg1", type="String"),
     * @GQL\Arg(name="arg2", type="Int")
     */
    public function myFields(?string $arg1, ?int $arg2) {..}
}

```

### Annotations - Attributes changed

Change the attributes name of `@FieldsBuilder` annotation from `builder` and `builderConfig` to `value` and `config`. 

Before:
```php
/**
 * @GQL\Type(name="MyType", builders={@GQL\FieldsBuilder(builder="Timestamped", builderConfig={opt1: "val1"})})
 */
class MyType {

}
```

After:
```php
/**
 * @GQL\Type("MyType")
 * @GQL\FieldsBuilder(value="Timestamped", config={opt1: "val1"})
 */
class MyType {

}
```

