Fields access Control
======================

An access control can be added on each field using `config.fields.*.access` or globally with `config.fieldsDefaultAccess`.
If `config.fields.*.access` value is true field will be normally resolved but will be `null` otherwise.
Act like access is`true` if not set.

Note: 
- in query mode: execute resolver -> execute access -> manage result in function of access
- in mutation mode: execute access -> execute resolver if access result is true

In the example below the Human name is available only for authenticated users.

```yaml
Human:
    type: object
    config:
        description: "A humanoid creature in the Star Wars universe."
        fields:
            id:
                type: "String!"
                description: "The id of the character."
            name:
                type: "String"
                description: "The name of the character."
                access: "@=isAuthenticated()"
            friends:
                type: "[Character]"
                description: "The friends of the character."
                resolve: "@=resolver('character_friends', [value])"
            appearsIn:
                type: "[Episode]"
                description: "Which movies they appear in."
            homePlanet:
                type: "String"
                description: "The home planet of the human, or null if unknown."
        interfaces: [Character]
```

Or using annotation:

```php
<?php
/**
 * @author Thibault Colette <thibaultcolette06@hotmail.fr>
 * @copyright 2018 Thibault Colette
 */

namespace App\Entity\GraphQLType;

use Overblog\GraphQLBundle\Annotation as GQL;

/**
 * Class FormErrorType
 *
 * @GQL\GraphQLDescription(description="A humanoid creature in the Star Wars universe.")
 */
class Human implements Character
{
    /**
     * @GQL\GraphQLColumn(type="string")
     * @GQL\GraphQLDescription(description="The id of the character.")
     */
    public $id;

    /**
     * @GQL\GraphQLColumn(type="string")
     * @GQL\GraphQLDescription(description="The name of the character.")
     * @GQL\GraphQLAccessControl(method="isAuthenticated()")
     */
    public $name;
    
    /**
     * @GQL\GraphQLToMany(target="Character")
     * @GQL\GraphQLDescription(description="The friends of the character.")
     */
    public $friends;
    
    /**
     * @GQL\GraphQLToMany(target="Episode")
     * @GQL\GraphQLDescription(description="Which movies they appear in.")
     */
    public $appearsIn;
    
    /**
     * @GQL\GraphQLColumn(type="string")
     * @GQL\GraphQLDescription(description="The home planet of the human, or null if unknown.")
     */
    public $homePlanet;
}
```


Performance
-----------
Checking access on each field can be a performance issue and may be dealt with using:
- using a custom cache to do the check only once
- using [Object access control](object-access-control.md)
