# Promise

The bundle is totally "promise ready", by default it use **Webonyx/GraphQL-Php**
SyncPromise adapter (supporting the native deferred feature) and it also comes 
with [ReactPHP/Promise](https://github.com/reactphp/promise) adapter.
To integrate an other promise implementation, you must create a new service that
implements `Overblog\GraphQLBundle\Executor\Promise\PromiseAdapterInterface`
or `GraphQL\Executor\Promise\PromiseAdapter` with a `wait` method that accepts 
a Promise like argument and returns the result of the promise resolved
or throw an exception otherwise.

Config bundle to use the new service:

```yaml
overblog_graphql:
    services:
        promise_adapter: "my.promise_adapter"
```

You can use the in box `overblog_graphql.react.promise_adapter` service 
to manage ReactPHP/Promise.

The `overblog_graphql.promise_adapter` service to create promises 
in resolver like this:
 
```php
<?php

use GraphQL\Executor\Promise\PromiseAdapter;

class MyResolver
{
    public function __construct(PromiseAdapter $promiseAdapter)
    {
        $this->promiseAdapter = $promiseAdapter;
    }

    public function resolveQuery()
    {
        return $this->promiseAdapter->create(function (callable $resolve) {
            return $resolve(['name' => 'Luke']);
        });
    }
}
```

or using native supported promise like this:

```php
<?php

use React\Promise\Promise as ReactPromise;

class MyResolver
{
    public function resolveQuery()
    {
        return new ReactPromise(function (callable $resolve) {
            return $resolve(['name' => 'Luke']);
        });
    }
}
```
