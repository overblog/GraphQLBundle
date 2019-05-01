Relay Connection and Edge Fields Builder
========================================

Relay connections & edges are just regular GraphQL type with required fields.

This bundle provide Fields builder ([@see Fields Builder](../builders/fields.md)) to generate Relay connections & edges.

## The Relay Connection Fields Builder

Defined in `RelayConnectionFieldsBuilder` you can use this fields builder as follow:

```yaml
FriendsConnection:
    type: object
    config:
        builders:
            - builder: relay-connection
              builderConfig:
                  edgeType: FriendsConnectionEdge
```

See the class definition for additionnal configuration


## The Relay Edge Fields Builder

Defined in `RelayConnectionFieldsBuilder` you can use this fields builder as follow:

```yaml
FriendsConnectionEdge:
    type: object
    config:
        builders:
            - builder: relay-edge
              builderConfig:
                  nodeType: Character
```

See the class definition for additionnal configuration