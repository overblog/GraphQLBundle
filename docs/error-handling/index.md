Errors handling
===============

By default in no debug mode all errors will be logged and replace by a generic error message.
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

If you want to map your own exceptions to warnings and errors you can
define a custom exception mapping:

```yaml
overblog_graphql:
    #... 
    errors_handler:
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

The mapping is handled inside the `Overblog\GraphQLBundle\Error\ErrorHandler`
class using an instance of the `Overblog\GraphQLBundle\Error\ExceptionConverter`
class. Since this class implements an interface and is registered as a service
in the dependency container, you can easily swap it and customize the logic.

```php
namespace App\Error;

use Overblog\GraphQLBundle\Error\ExceptionConverterInterface;
use Overblog\GraphQLBundle\Error\UserError;

final class ExceptionConverter implements ExceptionConverterInterface
{
    public function convertException(\Throwable $exception): \Throwable
    {
        return new UserError($exception->getMessage(), $exception->getCode(), $exception->getPrevious());
    }
}
```

```yaml
App\Error\ExceptionConverter: ~

Overblog\GraphQLBundle\Error\ExceptionConverterInterface:
    alias: '@App\Error\ExceptionConverter'
```

You can custom the default errors handler using configuration:

```yaml
overblog_graphql:
    errors_handler:
        enabled: true # false will totally disabled errors handling
        internal_error_message: ~ # custom generic error message
        rethrow_internal_exceptions: false # re-throw internal exception
        debug: false # will add trace stack and debugMessage to error
        log: true # false will disabled the default logging behavior
        logger_service: logger # the service to use to log
```

The message of those exceptions are then shown to the user like other 
`UserError`s or `UserWarning`s.

Custom error Formatting
-------------------------

see [error formatting Event](../events/index.md#error-formatting)

Custom error handling / formatting
-----------------------------------

This can also be done by using events.
* First totally disabled default errors handler:
    ```yaml
    overblog_graphql:
        errors_handler: false
    ```
* Listen to [executor result event](../events/index.md#executor-result)
    ```yaml
    App\EventListener\MyErrorHandler:
        tags:
            - { name: kernel.event_listener, event: graphql.post_executor, method: onPostExecutor }
    ```

    ```php
  <?php

  namespace App\EventListener;

  use GraphQL\Error\Error;
  use GraphQL\Error\FormattedError;
  use Overblog\GraphQLBundle\Event\ExecutorResultEvent;

  class MyErrorHandler
  {
      public function onPostExecutor(ExecutorResultEvent $event)
      {
          $myErrorFormatter = function(Error $error) {
              return FormattedError::createFromException($error);
          };

          $myErrorHandler = function(array $errors, callable $formatter) {
              return array_map($formatter, $errors);
          };

          $event->getResult()
              ->setErrorFormatter($myErrorFormatter)
              ->setErrorsHandler($myErrorHandler);
      }
  }
  ```
