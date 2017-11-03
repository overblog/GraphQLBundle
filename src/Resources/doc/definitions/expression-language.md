Expression language
===================

All definitions configs entries can use expression language but it must be explicitly triggered using "@=" like prefix.

**Functions description:**

Expression | Description | Usage | Alias
---------- | ----------- | ----- | -----
object **service**(string $id) | Get a service from the container | @=service('my_service').customMethod() | serv
mixed **parameter**(string $name) | Get parameter from the container | @=parameter('kernel.debug') | param
boolean **isTypeOf**(string $className) | Verified if `value` is instance of className | @=isTypeOf('AppBundle\\User\\User') |
mixed **resolver**(string $alias, array $args = []) | call the method on the tagged service "overblog_graphql.resolver" with args | @=resolver('blog_by_id', [value['blogID']] | res
mixed **mutation**(string $alias, array $args = []) | call the method on the tagged service "overblog_graphql.mutation" with args | @=mutation('remove_post_from_community', [value]) | mut
string **globalId**(string\|int id, string $typeName = null) | Relay node globalId | @=globalId(15, 'User') |
array **fromGlobalId**(string $globalId) | Relay node fromGlobalId | @=fromGlobalId('QmxvZzox') |
object **newObject**(string $className, array $args = []) | Instantiation $className object with $args | @=newObject('AppBundle\\User\\User', ['John', 15]) |
boolean **hasRole**(string $role) | Checks whether the token has a certain role. | @=hasRole('ROLE_API') |
boolean **hasAnyRole**(string $role1, string $role2, ...string $roleN) | Checks whether the token has any of the given roles. | @=hasAnyRole('ROLE_API', 'ROLE_ADMIN') |
boolean **isAnonymous**() | Checks whether the token is anonymous. | @=isAnonymous() |
boolean **isRememberMe**() | Checks whether the token is remember me. | @=isRememberMe() |
boolean **isFullyAuthenticated**() | Checks whether the token is fully authenticated. | @=isFullyAuthenticated() |
boolean **isAuthenticated**() | Checks whether the token is not anonymous. | @=isAuthenticated() |
boolean **hasPermission**(mixed $var, string $permission) | Checks whether the token has the given permission for the given object (requires the ACL system). | @=hasPermission(object, 'OWNER') |
boolean **hasAnyPermission**(mixed $var, array $permissions) | Checks whether the token has any of the given permissions for the given object | @=hasAnyPermission(object, ['OWNER', 'ADMIN']) |

**Variables description:**

Expression | Description | Scope
---------- | ----------- | --------
**container** | DI container | global
**request** | Refers to the current request. | Request
**token** | Refers to the token which is currently in the security token storage. Token can be null. | Token
**user** | Refers to the user which is currently in the security token storage. User can be null. | Valid Token
**object** | Refers to the value of the field for which access is being requested. For array `object` will be each item of the array. For Relay connection `object` will be the node of each connection edges. | only available for `config.fields.*.access` with query operation or mutation payload type.
**value** | Resolver value | only available in resolve context 
**args** | Resolver args array | only available in resolve context 
**info** | Resolver GraphQL\Type\Definition\ResolveInfo Object | only available in resolve context
**context** | context is defined by your application on the top level of query execution (useful for storing current user, environment details, etc) | only available in resolve context
**childrenComplexity** | Selection field children complexity | only available in complexity context

[For more details on expression syntax](http://symfony.com/doc/current/components/expression_language/syntax.html)

**Tips**: the expression language service can be custom using bundle configuration.
