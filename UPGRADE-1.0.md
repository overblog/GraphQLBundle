UPGRADE FROM 0.13 to 1.0
=======================

# Table of Contents

- [Customize the cursor encoder of the edges of a connection](#customize-the-cursor-encoder-of-the-edges-of-a-connection)
- [Change arguments of TypeGenerator](#change-arguments-of-typegenerator)

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

The `TypeGenerator` service is used internally for GraphQL types compilation. If you overridden the service definition,
please take into account the new constructor signature:

```diff
public function __construct(
    string $classNamespace,
-   array $skeletonDirs,
    ?string $cacheDir,
    array $configs,
+   TypeBuilder $typeBuilder
    bool $useClassMap = true,
-   callable $configProcessor = null,
    ?string $baseCacheDir = null,
    ?int $cacheDirMask = null
) {
```
`TypeBuilder` here is a new service `Overblog\GraphQLBundle\Generator\TypeBuilder`, which is also used internally.
