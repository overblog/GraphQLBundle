# Interface

An Interface is an abstract type that includes a certain set of fields that a type must include to implement the interface.
See the [official documentation](https://graphql.org/learn/schema/#interfaces) for more details.

Here is an example of an interface and two types implementing it (implementors) written with GraphQL schema language:
```graphql
# Character that represents any character in the Star Wars trilogy
interface Character {
    id: String!
    name: String
    friends: [Character]
    appearsIn: [Episode]
}

type Human implements Character {
  id: ID!
  name: String!
  friends: [Character]
  appearsIn: [Episode]!
  starships: [Starship]
  totalCredits: Int
}

type Droid implements Character {
  id: ID!
  name: String!
  friends: [Character]
  appearsIn: [Episode]!
  primaryFunction: String
}
```

We'll show you how the schema above can be implemented with this bundle. There are two main ways to do 
it: with **yaml** config files and with **annotations**. Lets take a look at both.

## With YAML
```yaml
# config/graphql/types/Character.yml
Character:
    type: interface
    config:
        resolveType: "@=resolver('character_type', [value, typeResolver])"
        description: "A character in the Star Wars Trilogy"
        fields:
            id: 'ID!'
            name: 'String'
            friends: '[Character]'
            appearsIn:
                type: '[Episode]'
                description: 'Which movies this character appears in.'

# config/graphql/types/Human.yml
Human:
    type: object
    config:
        interfaces: [Character] # multiple interfaces allowed
        fields:
            id: "ID!"
            name: 
                type: "String"
                description: "The first and last names of the human."
            friends: "[Character]"
            appearsIn: "[Episode]!"
            starships: "[Starship]" 
            totalCredits: "Int"
            

# config/graphql/types/Droid.yml
Droid:
    type: object
    config:
        interfaces: [Character] # multiple interfaces allowed
        fields:
            id: "ID!"
            name: 
                type: "String"
                description: "The codename of the droid."
            friends: "[Character]"
            appearsIn: "[Episode]!"
            primaryFunction: "String"
```

Note some important points:
- Implementing types are required to include all fields of the interface with exact same types (including nonNull 
  specification) and arguments. The only exception is when object's field type is more specific than the corresponding 
  type of the interface. This applies for `nonNull` specs (field `appearsId` in this example) and for [covariant return types](https://webonyx.github.io/graphql-php/type-system/interfaces/#covariant-return-types-for-interface-fields).
  If you would like to avoid repeating same fields in implementors you can use the [type inheritance](https://github.com/overblog/GraphQLBundle/blob/master/docs/definitions/type-inheritance.md)
  feature provided by this bundle, which automates this process.

- The entry `resolveType` defines a method which receives a `value` from a parent resolver and based on it returns a 
specific Object Type implementing the interface. The argument `typeResolver` is a helper service provided by the bundle
to help you get required Object Type by it's name. The logic of the `resolveType` method is fully on you. 
If a `resolveType` option is omitted, the bundle will loop through all interface implementors and use their `isTypeOf` 
callback to pick the first suitable one. This is obviously less efficient than single `resolveType` call. So it is 
recommended to define `resolveType` whenever possible.


##### Using `resolveType` 

Suppose we have classes `Human` and `Droid`:

```php
class Human 
{
    public $id;
    public $name;
    public $friends;
    public $appearsIn;
    public $starships;
    public $totalCredits;
    
    // ...
}

class Droid 
{
    public $id;
    public $name;
    public $friends;
    public $appearsIn;
    public $primaryFunction;
    
    // ...
}
```

... and a query type:

```yml
RootQuery:
    type: object
    config:
        fields:
            allCaracters:
                type: "[Character]" # interface as the return type
                resolve: "@=res('all_characters')"
```
> Note:
> `res` is just an [alias](https://github.com/overblog/GraphQLBundle/blob/master/docs/definitions/expression-language.md#resolver) for `resolver`.


Then our resolver could look like this:
```php
<?php

namespace App\GraphQL;

use App\Entity\Droid;
use App\Entity\Human;
use GraphQL\Type\Definition\ObjectType;
use Overblog\GraphQLBundle\Definition\Resolver\AliasedInterface;
use Overblog\GraphQLBundle\Definition\Resolver\ResolverInterface;
use Overblog\GraphQLBundle\Resolver\TypeResolver;
use Overblog\GraphQLBundle\Resolver\UnresolvableException;

class MyResolver implements ResolverInterface, AliasedInterface
{
    public function allCharacters(): array
    {
        // Get an array of Human objects from DB
        $humans = $this->humanRepository->getAll();
        // Get an array of Droid objects from DB
        $droids = $this->droidRepository->getAll();

        // We return an array of mixed results. The 'resolveType'
        // method will map each object to it's GraphQL type.
        return \array_merge($humans, $droids);
    }

    /**
     * In this example we resolve types by checking the class of the value, but 
     * it's completely up to you how you distinguish one value from another.
     *
     * @param Human|Droid  $value        Value returned by parent resolver
     * @param TypeResolver $typeResolver Helper service to resolve GraphQL type objects
     */
    public function resolveType($value, TypeResolver $typeResolver): ObjectType
    {
        if ($value instanceof Human) {
            return $typeResolver->resolve('Human');
        } 
    
        if ($value instanceof Droid) {
            return $typeResolver->resolve('Droid');
        }

        throw new UnresolvableException("Couldn't resolve type for interface 'Character'");
    }

    public static function getAliases(): array
    {
        return [
            'allCharacters' => 'all_characters',
            'resolveType' => 'character_type',
        ];
    }
}
```

Don't forget to explicitly declare the implementing types, because they won't be autodiscovered:
```yaml
overblog_graphql:
    definitions:
        schema:
            types: [Human, Droid]
    # ...
```
This happens because the types `Human` and `Droid` are never referenced in fields of other types directly 
(see [graphql-php docs](http://webonyx.github.io/graphql-php/type-system/schema/#configuration-options))

##### Using `isTypeOf`

If you omit the `resolveType` option (which is [not recommended](https://webonyx.github.io/graphql-php/type-system/interfaces/#interface-role-in-data-fetching)) 
then you must define the `isTypeOf` option on each type implementing the interface. The value of the `isTypeOf` must be
a `boolean`. You can use the [Expression Language](https://github.com/overblog/GraphQLBundle/blob/master/docs/definitions/expression-language.md) 
to resolve a correct value, namely the [`isTypeOf`](https://github.com/overblog/GraphQLBundle/blob/master/docs/definitions/expression-language.md#istypeof) function which was create especially for this purpose: 

```yaml
# config/graphql/types/Human.yml
Human:
    type: object
    config:
        isTypeOf: '@=isTypeOf("App\\Entity\\Human")'
        fields:
            # ...
            

# config/graphql/types/Droid.yml
Droid:
    type: object
    config:
        isTypeOf: '@=isTypeOf("App\\Entity\\Droid")'
        fields:
            # ...
```

The system will loop through each implementing type, call it's `isTypeOf` and stop on the first type that returns `true`.

The `isTypeOf` function is not required, you can use any of the [preregistered expression functions](https://github.com/overblog/GraphQLBundle/blob/master/docs/definitions/expression-language.md#registered-functions):
```yaml
Human:
    type: object
    config:
        # Call a static method and pass the 'value' param to check its type
        isTypeOf: '@=call("App\\GraphQL\\TypeResolver::isHuman", [value])'
        
        # ... or even use a service
        isTypeOf: '@=service("my_service").isTypeOfHuman(value)'
```

All expression functions in the `isTypeOf` option have access to the [params](https://github.com/overblog/GraphQLBundle/blob/master/docs/definitions/expression-language.md#registered-variables): `value`, `context` and `info`.

## With Annotations

```php
<?php

namespace AppBundle;

use Overblog\GraphQLBundle\Annotation as GQL;

/**
 * @GQL\TypeInterface(resolveType="@=resolver('character_type', [value])")
 * @GQL\Description("A character in the Star Wars Trilogy")
 */
abstract class Character
{
    /**
     * @GQL\Field(type="String!")
     * @GQL\Description("The id of the character.")
     */
    public $id;

    /**
     * @GQL\Field(type="String")
     * @GQL\Description("The name of the character.")
     */
    public $name;

    /**
     * @GQL\Field(type="[Character]")
     * @GQL\Description("The friends of the character.")
     */
    public $friends;

    /**
     * @GQL\Field(type="[Episode]")
     * @GQL\Description("Which movies they appear in.")
     */
    public $appearsIn;
}
```

```yaml
# src/MyBundle/Resources/config/services.yml
services:
    my.graph.resolver.character:
        class: MyBundle\GraphQL\Resolver\CharacterResolver
        arguments: ["@overblog_graphql.type_resolver"]
        tags:
            - { name: overblog_graphql.resolver, alias: "character_type", method: "resolveType" }
            - { name: overblog_graphql.resolver, alias: "character_friends", method: "resolveFriends" }
            - { name: overblog_graphql.resolver, alias: "character_hero", method: "resolveHero" }
            - { name: overblog_graphql.resolver, alias: "character_human", method: "resolveHuman" }
            - { name: overblog_graphql.resolver, alias: "character_droid", method: "resolveDroid" }
```

```php
<?php

namespace App\GraphQL\Resolver;

require_once __DIR__ . '/../../../../vendor/webonyx/graphql-php/tests/StarWarsData.php';

use GraphQL\Tests\StarWarsData;
use Overblog\GraphQLBundle\Resolver\TypeResolver;

class CharacterResolver
{
    private $typeResolver;

    public function __construct(TypeResolver $typeResolver)
    {
        $this->typeResolver = $typeResolver;
    }

    public function resolveType($value)
    {
        $humanType = $this->typeResolver->resolve('Human');
        $droidType = $this->typeResolver->resolve('Droid');

        $humans = StarWarsData::humans();
        $droids = StarWarsData::droids();

        if (isset($humans[$value['id']])) {
            return $humanType;
        }

        if (isset($droids[$value['id']])) {
            return $droidType;
        }

        return null;
    }

    public function resolveFriends($character)
    {
        return StarWarsData::getFriends($character);
    }

    public function resolveHero($args)
    {
        return StarWarsData::getHero($args['episode'] ?? null);
    }

    public function resolveHuman($args)
    {
        $humans = StarWarsData::humans();

        return $humans[$args['id']] ?? null;
    }

    public function resolveDroid($args)
    {
        $droids = StarWarsData::droids();

        return $droids[$args['id']] ?? null;
    }
}
```
