Connection
===========

```yaml
Query:
    type: object
    config:
        fields:
            user:
                type: User
                resolve: '@=resolver("query")'

User:
    type: object
    config:
        fields:
            name:
                type: String
            friends:
                type: friendConnection
                argsBuilder: "Relay::Connection"
                resolve: '@=resolver("friends", [value, args])'
            friendsForward:
                type: userConnection
                argsBuilder: "Relay::ForwardConnection
                resolve: '@=resolver("friends", [value, args])'
            friendsBackward:
                type: userConnection
                argsBuilder: "Relay::BackwardConnection"
                resolve: '@=resolver("friends", [value, args])'

friendConnection:
    type: relay-connection
    config:
        nodeType: User
        resolveNode: '@=resolver("node", [value])'
        edgeFields:
            friendshipTime:
                type: String
                resolve: "Yesterday"
        connectionFields:
            totalCount:
                type: Int
                resolve: '@=resolver("connection")'

userConnection:
    type: relay-connection
    config:
        nodeType: User
        resolveNode: '@=resolver("node", [value])'
```

To ease relay connection pagination you can use the [pagination helper](../../helpers/relay-paginator.md).
