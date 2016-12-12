Fields access Control
======================

An access control can be add on each field using `config.fields.*.access` or globally with `config.fieldsDefaultAccess`.
If `config.fields.*.access` value is true field will be normally resolved but will be `null` otherwise.
Act like access is`true` if not set.

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
