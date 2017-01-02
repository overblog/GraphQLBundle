Plural
======

```yaml
Query:
    type: object
    config:
        fields:
            usernames:
                builder: "Relay::PluralIdentifyingRoot"
                builderConfig:
                    argName: 'usernames'
                    description: 'Map from a username to the user'
                    inputType: String
                    outputType: User
                    resolveSingleInput: '@=resolver("plural_single_input", [value, info])'
                    
User:
    type: object
    config:
        fields:
            username:
                type: String
            url:
                type: String
```
