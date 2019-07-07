# Mutation

Here an example of mutation without using [relay](https://facebook.github.io/relay/):

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

To implement the logic behind your mutation, you should create a new class that
implements `MutationInterface` and `AliasedInterface` interfaces.

```php
<?php
# src/GraphQL/Mutation/ShipMutation.php
namespace App\GraphQL\Mutation;

use Overblog\GraphQLBundle\Definition\Resolver\AliasedInterface;
use Overblog\GraphQLBundle\Definition\Resolver\MutationInterface;

class ShipMutation implements MutationInterface, AliasedInterface
{
    private $factionRepository;

    public function __construct(FactionRepository $factionRepository) {
        $this->factionRepository = $factionRepository;
    }

    public function createShip(string $shipName, int $factionId): array
    {
        // `$shipName` has the value of `args['input']['shipName']`
        // `$factionId` has the value of `args['input']['factionId']`

        // Do something with `$shipName` and `$factionId` ...
        $ship    = new Ship($shipName);
        $faction = $this->factionRepository->find($factionId);
        $faction->addShip($ship);
        // ...


        // Then returns our payload, it should fits `IntroduceShipPayload` type
        return [
            'ship'    => $ship,
            'faction' => $faction,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function getAliases(): array
    {
        return [
            // `create_ship` is the name of the mutation that you SHOULD use inside of your types definition
            // `createShip` is the method that will be executed when you call `@=resolver('create_ship')`
            'createShip' => 'create_ship'
        ];
    }
}
```

Here the same example [using relay mutation](relay/mutation.md).
