Errors handling
===============

In no debug mode all errors will be logged and replace by a generic error message.
Only query parsed error won't be replaced.
If you want to send explicit error or warnings messages to your users you can use exceptions:

1- **Overblog\\GraphQLBundle\\Error\\UserError** to send unique error

```php
use Overblog\GraphQLBundle\Error\UserError;

class CharacterResolver
{
    //...
    public function resolveHuman($args)
    {
        $humans = StarWarsData::humans();

        if (!isset($humans[$args['id']])) {
            throw new UserError(sprintf('Could not find Human#%d', $args['id']));
        }

        return $humans[$args['id']];
    }
    //...
}
```

2- **Overblog\\GraphQLBundle\\Error\\UserErrors** to send multiple errors

```php
use Overblog\GraphQLBundle\Error\UserError;
use Overblog\GraphQLBundle\Error\UserErrors;

class CharacterResolver
{
    //...
    public function resolveHumanAndDroid($args)
    {
        $humans = StarWarsData::humans();
        
        $errors = [];

        if (!isset($humans[$args['human_id']])) {
            $errors[] = new UserError(sprintf('Could not find Human#%d', $args['human_id']));
        }

        $droids = StarWarsData::droids();

        if (!isset($droids[$args['droid_id']])) {
            $errors[] = sprintf('Could not find Droid#%d', $args['droid_id']);
        }

        if (!empty($errors)) {
            throw new UserErrors($errors);
        }

        return [
            'human' => $humans[$args['human_id']],
            'droid' => $droids[$args['droid_id']],
        ];
    }
    //...
}
```

3- **Overblog\\GraphQLBundle\\Error\\UserWarning** to send unique warning

```php
use Overblog\GraphQLBundle\Error\UserWarning;

class CharacterResolver
{
    //...
    public function resolveHuman($args)
    {
        $humans = StarWarsData::humans();

        if (!isset($humans[$args['id']])) {
            throw new UserWarning(sprintf('Could not find Human#%d', $args['id']));
        }

        return $humans[$args['id']];
    }
    //...
}
```

Warnings can be found in the response under `extensions.warnings` map.

You can also custom the generic error message

```yaml
#app/config/config.yml
overblog_graphql:
    #... 
    definitions:
        internal_error_message: "An error occurred, please retry later or contact us!"
```

If you want to map your own exceptions to warnings and errors you can
define a custom exception mapping:

```yaml
#app/config/config.yml
overblog_graphql:
    #... 
    definitions:
        #...
        # change to true to try to map an exception to a parent exception if the exact exception is not in 
        # the mapping
        map_exceptions_to_parent: false
        exceptions:
            warnings:
                - "Symfony\\Component\\Routing\\Exception\\ResourceNotFoundException"
            errors:
                - "InvalidArgumentException"
```

The message of those exceptions are then shown to the user like other 
`UserError`s or `UserWarning`s.
