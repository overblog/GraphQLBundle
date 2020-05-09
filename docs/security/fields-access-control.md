# Fields access Control

## With YAML

An access control can be added on each field using `config.fields.*.access` or globally with `config.fieldsDefaultAccess`.
If `config.fields.*.access` value is true field will be normally resolved but will be `null` otherwise.
Act like access is`true` if not set.

Note:

-   in query mode: execute resolver -> execute access -> manage result in function of access
-   in mutation mode: execute access -> execute resolver if access result is true

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

## With Annotations

```php
<?php

namespace AppBundle;

use Overblog\GraphQLBundle\Annotation as GQL;

/**
 * @GQL\Type(interfaces={"Character"}, description="A humanoid creature in the Star Wars universe.")
 */
class Human
{
    /**
     * @GQL\Field(type="String!", description="The id of the character.")
     */
    public $id;

    /**
     * @GQL\Field(type="String!", description="The name of the character.")
     * @GQL\Access("isAuthenticated()")
     */
    public $name;

    /**
     * @GQL\Field(type="[Character]", description="The friends of the character.", resolve="@=resolver('character_friends', [value])")
     */
    public $friends;

    /**
     * @GQL\Field(type="[Episode]", description="Which movies they appear in.")
     */
    public $appearsIn;

    /**
     * @GQL\Field(type="String", description="The home planet of the human, or null if unknown.")
     */
    public $homePlanet;
}
```

## Performance

Checking access on each field can be a performance issue and may be dealt with using:

-   using a custom cache to do the check only once
-   using [Object access control](object-access-control.md)
