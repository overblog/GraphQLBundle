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
            name:
                type: String
        interfaces: [NodeInterface]

Photo:
    type: object
    config:
        fields:
            id:
                builder: "Relay::GlobalId"
                # here the entry to custom your field builder
                builderConfig:
                    # Change the type name
                    typeName: Image
                    # custom id fetcher function
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
            text:
                type: String
        interfaces: [NodeInterface]
```
