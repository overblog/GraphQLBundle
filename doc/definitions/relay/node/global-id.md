Global ID
=======

```yaml
Query:
    type: object
    config:
        fields:
            node:
                builder: "Relay::Node"
                builderConfig:
                    nodeInterfaceType: NodeInterface
                    idFetcher: '@=service("overblog_graphql.test.resolver.global").idFetcher(value)'
            allObjects:
                type: '[NodeInterface]'
                resolve: '@=service("overblog_graphql.test.resolver.global").resolveAllObjects()'

NodeInterface:
    type: relay-node
    config:
        resolveType: '@=service("overblog_graphql.test.resolver.global").typeResolver(value)'

User:
    type: object
    config:
        fields:
            id:
                builder: "Relay::GlobalId"
                builderConfig:
                    typeName: User
            name:
                type: String
        interfaces: [NodeInterface]

Photo:
    type: object
    config:
        fields:
            id:
                builder: "Relay::GlobalId"
                builderConfig:
                    typeName: Photo
                    idFetcher: '@=value["photoId"]'
            width:
                type: Int
        interfaces: [NodeInterface]

Post:
    type: object
    config:
        fields:
            id:
                builder: "Relay::GlobalId"
                builderConfig:
                    typeName: Post
            text:
                type: String
        interfaces: [NodeInterface]
```
