Mutation
========

```yaml
RootMutation:
    type: object
    config:
        fields:
            simpleMutation:
                builder: Mutation
                builderConfig:
                    inputType: simpleMutationInput
                    payloadType: simpleMutationPayload
                    mutateAndGetPayload: "@={'result': 1}"
            simpleMutationWithThunkFields:
                builder: Mutation
                builderConfig:
                    inputType: simpleMutationWithThunkFieldsInput
                    payloadType: simpleMutationWithThunkFieldsPayload
                    mutateAndGetPayload: "@={'result': value['inputData'] }"

simpleMutationInput:
    type: relay-mutation-input
    config:
        fields: []

simpleMutationWithThunkFieldsInput:
    type: relay-mutation-input
    config:
        fields:
            inputData : { type: "Int" }
            
simpleMutationPayload:
    type: relay-mutation-payload
    config:
        fields:
            result: { type: "Int" }

simpleMutationWithThunkFieldsPayload:
    type: relay-mutation-payload
    config:
        fields:
            result: { type: "Int" }
```
