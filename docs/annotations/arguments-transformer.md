# The Arguments Transformer service

When using annotation, as we use classes to describe our GraphQL objects, it is also possible to create and populate classes instances using GraphQL data.  
If a class is used to describe a GraphQL Input, this same class can be instanciated to hold the corresponding GraphQL Input data.  
This is where the `Arguments Transformer` comes into play. Knowing the matching between GraphQL types and PHP classes, the service is able to instanciate a PHP classes and populate it with data based on the corresponding GraphQL type.
To invoke the Arguments Transformer, we use the `input` expression function in our resolvers. 

## the `arguments` function in expression language

The `arguments` function take two parameters, a mapping of arguments name and their type, like `name => type`. The type is in GraphQL notation, eventually with the "[]" and "!". The data are indexed by argument name.
This function will use the `Arguments Transformer` service to transform the list of arguments into their corresponding PHP class if it has one and using a property accessor, it will populate the instance, and will use the `validator` service to validate it.  
The transformation is done recursively. If an Input include another Input as field, it will also be populated the same way.

For example:

```php
namespace App\GraphQL\Input;

use Overblog\GraphQLBundle\Annotation as GQL;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @GQL\Input
 */ 
class UserRegisterInput {
    /**
     * @GQL\Field(type="String!")
     * @Assert\NotBlank
     * @Assert\Length(min = 2, max = 50)
     */
    public $username;

    /**
     * @GQL\Field(type="String!")
     * @Assert\NotBlank
     * @Assert\Email
     */
    public $email;

    /**
     * @GQL\Field(type="String!")
     * @Assert\NotBlank
     * @Assert\Length(
     *      min = 5, 
     *      minMessage="The password must be at least 5 characters long."
     * )
     */
    public $password;

    /**
     * @GQL\Field(type="Int!")
     * @Assert\NotBlank
     * @Assert\GreaterThan(18)
     */
    public $age;
}

....

/**
 * @GQL\Provider
 */
class UserRepository {
    /**
     * @GQL\Mutation
     */
    public function createUser(UserRegisterInput $input) : User {
        // Use the validated $input here
        $user = new User();
        $user->setUsername($input->username);
        $user->setPassword($input->password);
        ...
    }
}
```

When this Input is used in a mutation, the Symfony service `overblog_graphql.arguments_transformer` is called in order to transform the received array of data into a `UserRegisterInput` instance using a property accessor.  
Then the `validator` service is used to validate this instance against the configured constraints.  
The mutation received the valid instance.  

In the above example, everything is auto-guessed and a Provider is used. But this would be the same as : 

```php
/**
 * @GQL\Type
 */
class RootMutation {
    /**
     * @GQL\Field(
     *   type="User",
     *   args={
     *     @GQL\Arg(name="input", type="UserRegisterInput")
     *   },
     *   resolve="@=call(service('UserRepository').createUser, arguments({input: 'UserRegisterInput'}, arg))"
     * )
     */
    public $createUser;
}
```

So, the resolver (the `createUser` method) will receive an instance of the class `UserRegisterInput` instead of an array of data. 
