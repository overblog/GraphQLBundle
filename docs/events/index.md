Events
=========

[Events](http://symfony.com/doc/master/event_dispatcher.html) are a way to hook into the execution flow. This bundle provides various events around the executor's context value initialization and result formatting (data, errors, extensions).

Executor context value
----------------------

*Event:* `graphql.executor.context`

It is used to initialize the executor's `contextValue` argument.


Executor initialisation
-----------------------

*Event:* `graphql.pre_executor`

Used to initialize the executor's execute arguments: `schema`, `requestString`, `rootValue`, `contextValue`, `variableValue`, `operationName`.

Example:

* Initialize the `rootValue` with the current user (we assume that user is fully authenticated)

    ```yaml
    App\EventListener\RootValueInitialization:
        tags:
            - { name: kernel.event_listener, event: graphql.pre_executor, method: onPreExecutor }
    ```

    ```php
  <?php

  namespace App\EventListener;

  use Overblog\GraphQLBundle\Event\ExecutorArgumentsEvent;
  use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

  class RootValueInitialization
  {
      private $token;

      public function __construct(TokenInterface $token)
      {
           $this->token = $token;
      }

      public function onPreExecutor(ExecutorArgumentsEvent $event)
      {
          $event->setRootValue($this->token->getUser());
      }
  }

Executor result
---------------

*Event:* `graphql.post_executor`

Used to set custom error handling and formatting. It can also be used to
add information to the `extensions` section.

Example:

* Add a `credits` entry in the `extensions` section

    ```yaml
    App\EventListener\Credits:
        tags:
            - { name: kernel.event_listener, event: graphql.post_executor, method: onPostExecutor }
    ```

    ```php
  <?php

  namespace App\EventListener;

  use Overblog\GraphQLBundle\Event\ExecutorResultEvent;

  class Credits
  {
      public function onPostExecutor(ExecutorResultEvent $event)
      {
          $event->getResult()->extensions['credits'] = 'This api was powered by "OverblogGraphQLBundle".';
      }
  }
  ```

  result:
  ```json
  {
    "data": {"foo": "bar"},
    "extensions": {
      "credits": "This api was powered by \"OverblogGraphQLBundle\"."
    }
  }
  ```

Error formatting
----------------

*Event:* `graphql.error_formatting`

Used to add or remove fields from the result's `errors` and `extensions.warnings`
sections. It can also be used to log errors or exception. Each single error or warning will trigger
an event.

Example:

* How to add error code:

    ```yaml
    App\EventListener\ErrorCode:
        tags:
            - { name: kernel.event_listener, event: graphql.error_formatting, method: onErrorFormatting }
    ```

    ```php
  <?php

  namespace App\EventListener;

  use Overblog\GraphQLBundle\Event\ErrorFormattingEvent;

  class ErrorCode
  {
      public function onErrorFormatting(ErrorFormattingEvent $event)
      {
          $error = $event->getError();
          if ($error->getPrevious()) {
              $code = $error->getPrevious()->getCode();
          } else {
              $code = $error->getCode();
          }
          $formattedError = $event->getFormattedError();
          $formattedError->offsetSet('code', $code); // or $formattedError['code'] = $code;
      }
  }
  ```

  result:
  ```json
  {
    "data": null,
    "errors": [
      {
        "code": 123,
        "category": "internal",
        "message": "Internal server Error"
      }
    ]
  }
  ```

*Note:*
- This event is dispatched by this bundle's default error handler. Disabling it
will also disable this event.

Type loaded
----------------

*Event:* `graphql.type_loaded`

Used to modify types before schema registration.

Example:

```yaml
App\EventListener\TypeDecorator:
    tags:
        - { name: kernel.event_listener, event: graphql.type_loaded, method: onTypeLoaded }
```

```php
<?php

namespace App\EventListener;

use Overblog\GraphQLBundle\Event\TypeLoadedEvent;

class TypeDecorator
{
  public function onTypeLoaded(TypeLoadedEvent $event)
  {
      $type = $event->getType();
      // modify the type
  }
}
```
