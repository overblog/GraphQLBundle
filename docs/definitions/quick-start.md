Quick start
===========

1. Install the bundle ([more details](../index.md))

```bash
composer require overblog/graphql-bundle
```

2. Configure the bundle to accept `graphql` format ([more details](graphql-schema-language.md))

```diff
# config/packages/graphql.yaml
overblog_graphql:
    definitions:
        schema:
            query: Query
        mappings:
            auto_discover: false
            types:
                -
-                   type: yaml
+                   type: graphql
                    dir: "%kernel.project_dir%/config/graphql/types"
                    suffix: ~
```

3. Define schema using [GraphQL schema language](http://graphql.org/learn/schema/)
in files `config/graphql/types/*.graphql`

4. Define schema Resolvers ([more details](resolver-map.md))

```php
<?php

// src/Resolver/MyResolverMap.php
namespace App\Resolver;

use Overblog\GraphQLBundle\Resolver\ResolverMap;

class MyResolverMap extends ResolverMap
{
   protected function map()
   {
      // return your resolver map
   }
}
```

5. Test your schema using [GraphiQL](graphiql) or with curl

```bash
curl 'http://127.0.0.1:8000/' \
 -H 'Content-Type: application/json' \
 --data-binary '{"query":"{ humans {id name direwolf {id name} } }","variables":{}}'
```

This is it!
