Connection
===========

```yaml
Query:
    type: object
    config:
        fields:
            user:
                type: User
                resolve: '@=query("query")'

User:
    type: object
    config:
        fields:
            name:
                type: String
            friends:
                type: friendConnection
                argsBuilder: "Relay::Connection"
                resolve: '@=query("friends", value, args)'
            friendsForward:
                type: userConnection
                argsBuilder: "Relay::ForwardConnection"
                resolve: '@=query("friends", value, args)'
            friendsBackward:
                type: userConnection
                argsBuilder: "Relay::BackwardConnection"
                resolve: '@=query("friends", value, args)'

friendConnection:
    type: relay-connection
    config:
        nodeType: User
        resolveNode: '@=query("node", value)'
        edgeFields:
            friendshipTime:
                type: String
                resolve: "Yesterday"
        connectionFields:
            totalCount:
                type: Int
                resolve: '@=query("connection")'

userConnection:
    type: relay-connection
    config:
        nodeType: User
        resolveNode: '@=query("node", value)'
```

To ease relay connection pagination you can use the [pagination helper](../../helpers/relay-paginator.md).
