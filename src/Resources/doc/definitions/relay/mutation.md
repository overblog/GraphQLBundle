Mutation
========

```yaml
Mutation:
    type: object
    config:
        fields:
            introduceShip:
                builder: "Relay::Mutation"
                builderConfig:
                    inputType: IntroduceShipInput
                    payloadType: IntroduceShipPayload
                    mutateAndGetPayload: "@=mutation('create_ship', [value['shipName'], value['factionId']])"

#   input IntroduceShipInput {
#     clientMutationId: string!
#     shipName: string!
#     factionId: ID!
#   }
IntroduceShipInput:
    type: relay-mutation-input
    config:
        fields:
            shipName:
                type: "String!"
            factionId:
                type: "String!"

#   type IntroduceShipPayload {
#     clientMutationId: string!
#     ship: Ship
#     faction: Faction
#   }
IntroduceShipPayload:
    type: relay-mutation-payload
    config:
        fields:
            ship:
                type: "Ship"
            faction:
                type: "Faction"
```

Here the same example [without using relay mutation](../mutation.md).
