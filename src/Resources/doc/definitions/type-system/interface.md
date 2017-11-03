Interface
=========

```yaml
# src/MyBundle/Resources/config/graphql/Character.types.yml
# Characters in the Star Wars trilogy are either humans or droids.
#
# This implements the following type system shorthand:
#   interface Character {
#     id: String!
#     name: String
#     friends: [Character]
#     appearsIn: [Episode]
#   }
Character:
    type: interface
    config:
        description: "A character in the Star Wars Trilogy"
        fields:
            id:
                type: "String!"
                description: "The id of the character."
            name:
                type: "String"
                description: "The name of the character."
            friends:
                type: "[Character]"
                description: "The friends of the character."
            appearsIn:
                type: "[Episode]"
                description: "Which movies they appear in."
        # used expression language to defined resolver (tagged services)
        resolveType: "@=resolver('character_type', [value])"
```

```yaml
# src/MyBundle/Resources/config/services.yml
services:
    my.graph.resolver.character:
        class: MyBundle\GraphQL\Resolver\CharacterResolver
        arguments:
            - "@overblog_graphql.type_resolver"
        tags:
            - { name: overblog_graphql.resolver, alias: "character_type", method: "resolveType" }
            - { name: overblog_graphql.resolver, alias: "character_friends", method: "resolveFriends" }
            - { name: overblog_graphql.resolver, alias: "character_hero", method: "resolveHero" }
            - { name: overblog_graphql.resolver, alias: "character_human", method: "resolveHuman" }
            - { name: overblog_graphql.resolver, alias: "character_droid", method: "resolveDroid" }
```

```php
// src/MyBundle/GraphQL/Resolver
namespace MyBundle\GraphQL\Resolver;

require_once __DIR__ . '/../../../../vendor/webonyx/graphql-php/tests/StarWarsData.php';

use GraphQL\Tests\StarWarsData;
use Overblog\GraphQLBundle\Resolver\TypeResolver;

class CharacterResolver
{
    /**
     * @var TypeResolver
     */
    private $typeResolver;
    
    public function __construct(TypeResolver $typeResolver)
    {
        $this->typeResolver = $typeResolver;
    }
    
    public function resolveType($data)
    {
        $humanType = $this->typeResolver->resolve('Human');
        $droidType = $this->typeResolver->resolve('Droid');
        
        $humans = StarWarsData::humans();
        $droids = StarWarsData::droids();
        if (isset($humans[$data['id']])) {
            return $humanType;
        }
        if (isset($droids[$data['id']])) {
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
        return StarWarsData::getHero(isset($args['episode']) ? $args['episode'] : null);
    }
    
    public function resolveHuman($args)
    {
        $humans = StarWarsData::humans();
        return isset($humans[$args['id']]) ? $humans[$args['id']] : null;
    }
    
    public function resolveDroid($args)
    {
        $droids = StarWarsData::droids();
        return isset($droids[$args['id']]) ? $droids[$args['id']] : null;
    }
}
```
