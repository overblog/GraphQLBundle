Object
======

```yaml
# src/MyBundle/Resources/config/graphql/Human.types.yml
# We define our human type, which implements the character interface.
#
# This implements the following type system shorthand:
#   type Human : Character {
#     id: String!
#     name: String
#     friends: [Character]
#     appearsIn: [Episode]
#   }
Human:
    type: object
    config:
        description: "A humanoid creature in the Star Wars universe."
        fields:
            id:
                type: "String!"
                description: "The id of the character."
                # to deprecate a field, only set the deprecation reason
                #deprecationReason: "A terrible reason"
            name:
                type: "String"
                description: "The name of the character."
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

```yaml
# src/MyBundle/Resources/config/graphql/Droid.types.yml
#  The other type of character in Star Wars is a droid.
# 
#  This implements the following type system shorthand:
#    type Droid : Character {
#      id: String!
#      name: String
#      friends: [Character]
#      appearsIn: [Episode]
#      primaryFunction: String
#   }
Droid:
    type: object
    config:
        description: "A mechanical creature in the Star Wars universe."
        fields:
            id:
                type: "String!"
                description: "The id of the droid."
            name:
                type: "String"
                description: "The name of the droid."
            friends:
                type: "[Character]"
                description: "The friends of the droid, or an empty list if they have none."
                resolve: "@=resolver('character_friends', [value])"
            appearsIn:
                type: "[Episode]"
                description: "Which movies they appear in."
            primaryFunction:
                type: "String"
                description: "The primary function of the droid."
        interfaces: [Character]
```
