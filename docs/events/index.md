Events
=========

[Events](http://symfony.com/doc/master/event_dispatcher.html) are a way to hook
into the execution flow. This bundle provides various events from executor
and context value initialization, executor result formatting (data, errors, extensions),
errors/warnings formatting.

With this in mind we now turn to explain each one of them.

Executor context value
----------------------

*Event:* `graphql.executor.context`

This event can be listen to initialize executor `contextValue` argument.


Executor initialisation
-----------------------

*Event:* `graphql.pre_executor`

This event can be listen to initialize executor execute arguments
(`schema`, `requestString`, `rootValue`, `contextValue`, `variableValue`, `operationName`).

For example:

* Initializing `rootValue` with the current user (we assume that user is fully authenticated)

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

This event can be listen to set custom error handling and formatting, it can also be use to
add information to `extensions` section.

For example:

* How to add `credits` entry in `extensions` section

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

This event can be listen to add or remove fields from result `errors` and `extensions.warnings`
sections, it can also be use to log errors or exception. Each single error or warning will trigger
an event.

For example:

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
- This event is dispatch by this bundle default error handler, that the reason why, disabling
error handler will also disable this event.
