OverblogGraphQLPhpGenerator
===========================


GraphQL PHP types generator...

[![Code Coverage](https://scrutinizer-ci.com/g/overblog/GraphQLPhpGenerator/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/overblog/GraphQLPhpGenerator/?branch=master)
[![Build Status](https://travis-ci.org/overblog/GraphQLPhpGenerator.svg?branch=master)](https://travis-ci.org/overblog/GraphQLPhpGenerator)

Requirements
------------
PHP >= 5.4

Installation
------------

```bash
composer require overblog/graphql-php-generator
```

Usage
-----

```php
<?php
$loader = require __DIR__.'/vendor/autoload.php';

use GraphQL\Schema;
use Overblog\GraphQLGenerator\Generator\TypeGenerator;
use Symfony\Component\ExpressionLanguage\Expression;

$configs = [
    'Character' => [
        'type' => 'interface',
        'config' => [
            'description' => new Expression('\'A character\' ~ \' in the Star Wars Trilogy\''),
            'fields' => [
                'id' => ['type' => 'String!', 'description' => 'The id of the character.'],
                'name' => ['type' => 'String', 'description' => 'The name of the character.'],
                'friends' => ['type' => '[Character]', 'description' => 'The friends of the character.'],
                'appearsIn' => ['type' => '[Episode]', 'description' => 'Which movies they appear in.'],
            ],
            'resolveType' => 'Overblog\\GraphQLGenerator\\Tests\\Resolver::resolveType',
        ],
    ],
    /*...*/
    'Query' => [
        'type' => 'object',
        'config' => [
            'description' => 'A humanoid creature in the Star Wars universe or a faction in the Star Wars saga.',
            'fields' => [
                'hero' => [
                    'type' => 'Character',
                    'args' => [
                        'episode' => [
                            'type' => 'Episode',
                            'description' => 'If omitted, returns the hero of the whole saga. If provided, returns the hero of that particular episode.',
                        ],
                    ],
                    'resolve' => ['Overblog\\GraphQLGenerator\\Tests\\Resolver', 'getHero'],
                ],
            ],
        ],
        /*...*/
    ],
];

$typeGenerator = new TypeGenerator('\\My\\Schema\\NP');
$classesMap = $typeGenerator->generateClasses($configs, __DIR__ . '/cache/types');

$loader->addClassMap($classesMap);

$schema = new Schema(\My\Schema\NP\QueryType::getInstance());
```
