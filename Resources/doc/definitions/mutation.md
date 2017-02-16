# Mutation

Here an example without using relay:

```yaml
Mutation:
    type: object
    config:
        fields:
            IntroduceShip:
                type: IntroduceShipPayload!
                resolve: "@=mutation('create_ship', [args['input']['shipName'], args['input']['factionId']])"
                args:
                    #using input object type is optional, we use it here to be iso with relay mutation example.
                    input:
                        type: IntroduceShipInput!

IntroduceShipPayload:
    type: object
    config:
        fields:
            ship:
                type: "Ship"
            faction:
                type: "Faction"

IntroduceShipInput:
    type: input-object
    config:
        fields:
            shipName:
                type: "String!"
            factionId:
                type: "String!"
```

Here the same example [using relay mutation](relay/mutation.md).
