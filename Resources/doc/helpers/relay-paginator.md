# Relay Pagination helper

The `Paginator` can be used in resolvers to get a sliced data set when dealing with Relay Connections.

## Relay behavior

Relay specification: https://facebook.github.io/relay/graphql/connections.htm

Connection implementation in JS: https://github.com/graphql/graphql-relay-js/tree/master/src/connection

The `connectionFromArraySlice()` method:

This method can be used to get a slice of a data set by passing:

- the sliced data set to calculate the edges from
- the args, as a `ConnectionArguments` object
- the meta, as a `ArraySliceMetaInfo` object

The sliced data set must contains:

- the item before the first item you want
- the item after the slice, so `PageInfo->hasNextPage` can be calculated

Example:

- full data set is `['A','B','C','D','E']`
- we want 2 items after `A`, meaning `['B','C']`


- `after` cursor will be `arrayconnection:0`
- `offset` will be calculated to `0`
- so we need to passed a sliced data with `['A','B','C','D']` to `connectionFromArraySlice()`

## Paginator

See: `Overblog\GraphQLBundle\Relay\Connection\Paginator`

The purpose if this helper is to provide an easy way to paginate in a data set provided by a backend.

When constructing the paginator, you need to pass a callable which will be responsible for providing the sliced data set.

### Example

#### With a `first` Relay parameter

```php
<?php

use Overblog\GraphQLBundle\Definition\Argument;
use Overblog\GraphQLBundle\Relay\Connection\Paginator;

function getData($offset = 0)
{
    return array_slice(['A', 'B', 'C', 'D', 'E'], $offset);
}

$paginator = new Paginator(function ($offset, $limit) {
    return getData($offset);
});

$result = $paginator->forward(new Argument(['first' => 4]));

var_dump($result->edges);

```

Output

```
array(4) {
  [0]=>
  object(Overblog\GraphQLBundle\Relay\Connection\Output\Edge)#24 (2) {
    ["cursor"]=>
    string(24) "YXJyYXljb25uZWN0aW9uOjA="
    ["node"]=>
    string(1) "A"
  }
  [1]=>
  object(Overblog\GraphQLBundle\Relay\Connection\Output\Edge)#25 (2) {
    ["cursor"]=>
    string(24) "YXJyYXljb25uZWN0aW9uOjE="
    ["node"]=>
    string(1) "B"
  }
  [2]=>
  object(Overblog\GraphQLBundle\Relay\Connection\Output\Edge)#26 (2) {
    ["cursor"]=>
    string(24) "YXJyYXljb25uZWN0aW9uOjI="
    ["node"]=>
    string(1) "C"
  }
  [3]=>
  object(Overblog\GraphQLBundle\Relay\Connection\Output\Edge)#27 (2) {
    ["cursor"]=>
    string(24) "YXJyYXljb25uZWN0aW9uOjM="
    ["node"]=>
    string(1) "D"
  }
}
```
#### With an `after` Relay parameter

Note: we want 1 item after `C` so the decoded cursor is `arrayconnection:2`

```php
<?php

use Overblog\GraphQLBundle\Definition\Argument;
use Overblog\GraphQLBundle\Relay\Connection\Paginator;

function getData($offset = 0)
{
    return array_slice(['A', 'B', 'C', 'D', 'E'], $offset);
}

$paginator = new Paginator(function ($offset, $limit) {
    return getData($offset);
});

$result = $paginator->forward(
    new Argument(
        [
            'first' => 1,
            'after' => base64_encode('arrayconnection:2')
        ]
    )
);

var_dump($result->edges);

```

Output

```
array(1) {
  [0]=>
  object(Overblog\GraphQLBundle\Relay\Connection\Output\Edge)#26 (2) {
    ["cursor"]=>
    string(24) "YXJyYXljb25uZWN0aW9uOjM="
    ["node"]=>
    string(1) "D"
  }
}
```

**Important note:**

The callback function will receive:

- `$offset = 2`
- `$limit = 3`

And it must return at least `['C','D','E']`

#### With a `last` relay parameter

```php
<?php

use Overblog\GraphQLBundle\Definition\Argument;
use Overblog\GraphQLBundle\Relay\Connection\Paginator;

class DataBackend
{
    public $data = ['A', 'B', 'C', 'D', 'E'];

    public function getData($offset = 0)
    {
        return array_slice($this->data, $offset);
    }

    public function count($array)
    {
        return count($array);
    }
}

$backend = new DataBackend();

$paginator = new Paginator(function ($offset, $limit) use ($backend) {
    return $backend->getData($offset);
});

$result = $paginator->backward(
    new Argument(
        [
            'last' => 4,
        ]
    ),
    [$backend, 'count'],
    ['array' => $backend->getData]
);
```

You should get the 4 last items of the _data set_.

#### Promise handling

Paginator also supports promises if you [use that feature](https://github.com/webonyx/graphql-php/pull/67)
with the bundle. All you have to do is to toggle the `MODE_PROMISE` flag on and
update your callback to return a `Executor/Promise/Promise` instance.

```php
// Let's pretend we use dataloader ( https://github.com/overblog/dataloader-php )
public function resolveList($args)
{
    $pagination = new Paginator(function ($offset, $limit) {
        return $this->dataLoader->loadMany($this->elasticsearch->getIds($offset, $limit));
    }, Paginator::MODE_PROMISE); // This flag indicates that we will return a promise instead of an array of instances

    return $pagination->forward($args);
}
```
