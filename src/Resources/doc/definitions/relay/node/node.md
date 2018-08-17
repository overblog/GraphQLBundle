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

In above example `Photo` and `User` can't be detected by graphql-php during
static schema analysis. That the reason why their should be explicitly declare
in schema definition:

```yaml
overblog_graphql:
    definitions:
        schema:
            query: Query
            mutation: ~
            # here how this can be done
            types: [User, Photo]
```
