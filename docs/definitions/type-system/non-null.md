Non-Null
========

A trailing exclamation mark is used to denote a field that uses a Non‐Null type like this: **String!**.
With annotation, just use `nullable` option (availble with GraphQLToMany, GraphQLToOne, GraphQLColumn and with all doctrine ORM annotation.

```php
<?php

/**
 * Episode
 */
class Saison
{
    /**
     * @\Overblog\GraphQLBundle\Annotation\GraphQLToMany(target="Episode", nullable="true")
     */
    public $episodes;
}
```
