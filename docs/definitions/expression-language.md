
Expression language
===================

All definition config entries can use expression language but it must be explicitly triggered using the `@=` prefix. This bundle provides a set of registered functions and variables. For more details on expression syntax see the [official documentation](http://symfony.com/doc/current/components/expression_language/syntax.html).

## Contents
- [Registered functions](#registered-functions):
	- [service](#service)
	- [parameter](#parameter)
	- [isTypeOf](#istypeof)
	- [resolver](#resolver)
	- [mutation](#mutation)
	- [arguments](#arguments)
	- [globalId](#globalid)
	- [fromGlobalId](#fromglobalid)
	- [newObject](#newobject)
	- [call](#call)
	- [hasRole](#hasrole)
	- [hasAnyRole](#hasanyrole)
	- [isAnonymous](#isanonymous)
	- [isRememberMe](#isrememberme)
	- [isFullyAuthenticated](#isfullyauthenticated)
	- [isAuthenticated](#isauthenticated)
	- [hasPermission](#haspermission)
	- [hasAnyPermission](#hasanypermission)
	- [getUser](#getuser)
- [Registered variables](#registered-variables)
- [Private services](#private-services)
- [Custom expression functions](#custom-expression-functions)
- [Backslashes in expressions](#backslashes-in-expressions)


## Registered functions

### service
**Signature**: <code><b>service</b>(string <b>$id</b>): object|null</code> | **Alias**: `serv`

Gets a service from the [service container](https://symfony.com/doc/current/service_container.html). Private services should be explicitly tagged to be accessible, see [this](#private-services) section for more details.

Examples:
```yaml
@=service('my_service').customMethod()

# Using the 'ser' alias
@=serv('my_service').customMethod()

# Using the FQCN for the service name (only works for public services).
# Note the double quotes.
@=serv("App\\Manager\\UserManager").someMethod()

# If using single quotes, you must use 4 slashes
@=serv('App\\\\Manager\\\\UserManager').someMethod()
```

---

### parameter
**Signature**: <code><b>parameter</b>(string <b>$name</b>): mixed</code> | **Alias**: `param`

Gets a parameter from the [service container](https://symfony.com/doc/current/service_container.html). 

Examples:
```yaml
@=parameter('kernel.debug')

# Using the 'param' alias
@=param('mailer.transport')
```

---

### isTypeOf
**Signature**: <code><b>isTypeOf</b>(string <b>$className</b>): boolean</code>

Checks if the [`value`](#registered-variables) is instance of the given class name.

Example:
```yaml
@=isTypeOf("App\\User\\User")
```

---

### resolver
**Signature**: <code><b>resolver</b>(string <b>$alias</b>, array <b> $args</b> = []): mixed</code> | **Alias**: `res`

Calls a method on the tagged service `overblog_graphql.resolver` with `$args`

Examples:
```yaml
# Using aliased resolver name
@=resolver('blog_by_id', [value['blogID']])

# Using the 'res' alias and a FQCN::methodName.
# Note the double quotes.
@=res("App\\GraphQL\\Resolver\\UserResolver::findOne", [args, info, context, value])

# If using single quotes, you must use 4 slashes
@=res('App\\\\GraphQL\\\\Resolver\\\\UserResolver::findOne', [args, info, context, value])
```

---

### mutation
**Signature**: <code><b>mutation</b>(string <b>$alias</b>, array <b> $args</b> = []): mixed</code> | **Alias**: `mut`

Calls a method on the tagged service `overblog_graphql.mutation` passing `$args` as arguments.

Examples:
```yaml
# Using aliased mutation name
@=mutation('remove_post_from_community', [args['postId']])

# Using the 'mut' alias and a FQCN::methodName
# Note the double quotes.
@=mut("App\\GraphQL\\Mutation\\PostMutation::findAll", [args])

# If using single quotes, you must use 4 slashes
@=mut('App\\\\GraphQL\\\\Mutation\\\\PostMutation::findAll', [args])
```

---

### arguments
**Signature**: <code><b>arguments</b>(array <b>$mapping</b>, mixed <b> $data</b>): mixed</code>

Transforms and validates a list of arguments. See the [Arguments Transformer](https://github.com/overblog/GraphQLBundle/blob/master/docs/annotations/arguments-transformer.md) section for more details.

Example:
```yaml
@=arguments(['input' => 'MyInput'], ['input' => ['field1' => 'value1']])
```

---

### globalId
**Signature**: <code><b>globalId</b>(string|int <b>$id</b>, string <b> $typeName</b> = null): string</code>

Relay node globalId.

Example:
```yaml
@=globalId(15, 'User')
```

---

### fromGlobalId
**Signature**: <code><b>fromGlobalId</b>(string <b>$globalId</b>): array</code>

Relay node globalId.

Example:
```yaml
@=fromGlobalId(‘QmxvZzox’)
```

---

### newObject
**Signature**:  <code><b>newObject</b>(string <b>$className</b>, array <b> $args</b> = []): object</code>

Creates a new class instance from given class name and arguments. Uses the following php code under the hood: 
```php
(new ReflectionClass($className))->newInstanceArgs($args)
```
See the [official documentation](https://www.php.net/manual/en/reflectionclass.newinstanceargs.php) for more details about the `ReflectionClass::newInstanceArgs` method.

Examples:
```yaml
@=newObject("App\\Entity\\User", ["John", 15])

# Using inside another function (resolver)
@=resolver("myResolver", [newObject("App\\User\\User", [args])])
```

---

### call
**Signature**: <code><b>call</b>(callable <b> $target</b>, array <b> $args</b> = []): mixed</code>

Calls a function or a static method, passing `$args` to it as arguments.

Examples:
```yaml
# Calling a static method using a FCN string
@=call("App\\Util\\Validator::email", ["arg1", 2])

# Calling a static method using an array callable
@=call(["App\\Util\\Validator", "email"], [args["email"]])

# Calling a function
@=call('array_merge', [args['array1'], args['array2']])
```

---

### hasRole
**Signature**: <code><b>hasRole</b>(string <b>$role</b>): bool</code>

Checks whether the logged in user has a certain role.

Example:
```yaml
@=hasRole('ROLE_API')
```

---

### hasAnyRole
**Signature**: <code><b>hasAnyRole</b>(string <b> $role1</b>, string <b> $role2</b>, .\.\.string <b> $roleN</b>): bool</code>

Checks whether the logged in user has at least one of the given roles.

Example:
```yaml
@=hasAnyRole('ROLE_API', 'ROLE_ADMIN')
```

---

### isAnonymous  
**Signature**: <code><b>isAnonymous</b>(): bool</code>

Checks whether the token is anonymous. Shorthand for:
```php
AuthorizationChecker::isGranted('IS_AUTHENTICATED_ANONYMOUSLY')
```


Example:
```yaml
@=isAnonymous()
```

---

### isRememberMe
**Signature**: <code><b>isRememberMe</b>(): bool</code>

Checks whether the token is remembered. Shorthand for :
```php
AuthorizationChecker::isGranted('IS_AUTHENTICATED_REMEMBERED')
```

Example:
```yaml
@=isRememberMe()
```

---

### isFullyAuthenticated
**Signature**: <code><b>isFullyAuthenticated</b>(): bool</code>

Checks whether the token is fully authenticated. Shorthand for:
```php
AuthorizationChecker::isGranted('IS_AUTHENTICATED_FULLY')
```

Example:
```yaml
@=isFullyAuthenticated()
```

---

### isAuthenticated()  
**Signature**: <code><b>isAuthenticated</b>(): bool</code>

Checks whether the token is not anonymous. Shorthand for:
```php
AuthorizationChecker::isGranted('IS_AUTHENTICATED_REMEMBERED') || AuthorizationChecker::isGranted('IS_AUTHENTICATED_FULLY')
```

Example:
```yaml
@=isAuthenticated()
```

---

### hasPermission
**Signature**: <code><b>hasPermission</b>(object<b> $object</b>, string <b> $permission</b>): bool</code>

Checks whether logged in user has given permission for given object (requires [symfony/acl-bundle](https://github.com/symfony/acl-bundle) to be installed).

Example:
```yaml
# Using in combination with the 'service' function.
@=hasPermission(serv('user_repository').find(1), 'OWNER')
```

---

### hasAnyPermission
**Signature**: <code><b>hasAnyPermission</b>(object<b> $object</b>, array<b> $permission</b>): bool</code>

Checks whether the token has any of the given permissions for the given object

Example:
```yaml
# Using in combination with the 'service' function
@=hasAnyPermission(service('my_service').getObject(), ['OWNER', 'ADMIN'])
```

---

### getUser 
**Signature**: <code><b>getUser</b>(): Symfony\Component\Security\Core\User\UserInterface|null</code>

Returns the user which is currently in the security token storage.

Examples
```yaml
@=getUser()

# Checking if user has particular name
@=getUser().firstName === 'adam'
```

## Registered variables:

| Variable             | Description  | Scope|
|:-------------------- |:------------ |:---- |
| `typeResolver`       | An object of class `Overblog\GraphQLBundle\Resolver\TypeResolver`| global|
| `object`             | Refers to the value of the field for which access is being requested. For array `object` will be each item of the array. For Relay connection `object` will be the node of each connection edges. | only available for `config.fields.*.access` with query operation or mutation payload type. |
| `value`              | The value returned by a previous resolver | available in the `resolve` and `access` contexts |
| `args`               | An array of argument values of current resolver | available in the `resolve` and `access` contexts |
| `info`               | A `GraphQL\Type\Definition\ResolveInfo` object of current resolver | available in the `resolve` and `access` contexts |
| `context`            | context is defined by your application on the top level of query execution (useful for storing current user, environment details, etc)| available in the `resolve` and `access` contexts |
| `childrenComplexity` | Selection field children complexity | only available in `complexity` context|


Private services
----------------

It is not possible to use private services with [`service`](#service) functions since this is equivalent to call
`get` method on the container. In order to make private services accessible, they must be tagged with `overblog_graphql.global_variable`.

Yaml example:

```yaml
App\MyPrivateService:
    public: false
    tags:
        - { name: overblog_graphql.global_variable, alias: my_private_service }
```

To use a vendor private services:

```php
<?php

$vendorPrivateServiceDef = $container->findDefinition(\Vendor\PrivateService::class);
$vendorPrivateServiceDef->addTag('overblog_graphql.global_variable', ['alias' => 'vendor_private_service']);
```

Usage:

```yaml
MyType:
    type: object
    config:
        fields:
            name:
                type: String!
                resolve: '@=my_private_service.formatName(value)'
```

Custom expression functions
--------------------------

Adding custom expression function is easy since all you need to do is create a tagged service.
Expression functions can help user create simple resolver without having to leave config file,
this also improves performance by removing a useless external resolver call.

Here is an example to add a custom expression equivalent to php `json_decode`:

```php
<?php

namespace App\ExpressionLanguage;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;

class JsonDecode extends ExpressionFunction
{
    public function __construct()
    {
        parent::__construct(
            'json_encode',
            function ($json, $assoc) {
                return sprintf('json_decode(%s, %s)', $json, $assoc);
            }
        );
    }
}
```

now register your service:

```yaml
App\ExpressionLanguage\JsonDecode:
    tags: ['overblog_graphql.expression_function']
```

Now `json_decode` can be used in schema:

```yaml
Object:
    type: object
    config:
        fields:
            name:
            type: String!
            resolve: "@=json_decode(value.json_data, true)['name']"
```

**Tips**: At last if this is not an answer to all your needs, the expression language service can be customized
using bundle configuration.

## Backslashes in expressions

Backslashes in expressions must be escaped by 2 or 4 backslasehs, depending on which quotes do you use. 

When using **single quotes** as _outer_ quotes, you must use **double backslashes**. e.g.:
```yaml
...
resolve: '@=resolver("App\\GraphQL\\Resolver\\ResolverName::methodName")'
...
```
When using **double quotes** as _outer_ quotes, you must use **4 backslashes**, e.g.:
```yaml
...
resolve: "@=resolver('App\\\\GraphQL\\\\Resolver\\\\ResolverName::methodName')"
...
```
