Node
=====

```yaml
Query:
    type: object
    config:
        fields:
            node:
                builder: "Relay::Node"
                builderConfig:
                    nodeInterfaceType: Node
                    idFetcher: '@=resolver("node_id_fetcher", [value])'
                    
Node:
    type: relay-node
    config:
        resolveType: '@=resolver("node_type", [value])'

Photo:
    type: object
    config:
        fields:
            id:
                type: ID!
            width:
                type: Int
        interfaces: [Node]
        
User:
    type: object
    config:
        fields:
            id:
                type: ID!
            name:
                type: String
        interfaces: [Node]
```
