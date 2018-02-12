### Upload files

Using apollo-upload-client
--------------------------

The bundle comes of the box with a server compatible with
[apollo-upload-client](https://github.com/jaydenseric/apollo-upload-client).

1. define upload scalar type using yaml

    ```yaml
    MyUpload:
        type: custom-scalar
        config:
            scalarType: '@=newObject("Overblog\\GraphQLBundle\\Upload\\Type\\GraphQLUploadType")'
    ```

    or with GraphQL schema language

    ```graphql
    scalar MyUpload
    ```

    ```php
    <?php

    namespace App\Resolver;

    use Overblog\GraphQLBundle\Resolver\ResolverMap;
    use Overblog\GraphQLBundle\Upload\Type\GraphQLUploadType;

    class MyResolverMap extends ResolverMap
    {
        protected function map()
        {
            return [
                'MyUpload' => [self::SCALAR_TYPE => function () { return new GraphQLUploadType(); }],
            ];
        }
    }
    ```

    You can name as you want just replace `MyUpload` in above examples.

2. Use it in your Schema
    Here an example:

    ```yaml
    Mutation:
        type: object
        config:
            fields:
                singleUpload:
                    type: String!
                    resolve: '@=args["file"].getBasename()'
                    args:
                        file: MyUpload!
                multipleUpload:
                    type: '[String!]'
                    resolve: '@=[args["files"][0].getBasename(), args["files"][1].getBasename()]'
                    args:
                        files: '[MyUpload!]!'
    ```

    **Notes:**
    - Files args are of type `Symfony\Component\HttpFoundation\File\UploadedFile`
    - Upload scalar type can be use only on inputs fields (args or InputObject)

The classic way
---------------

here an example of how uploading can be done using this bundle

* First define schema
    ```yaml
    RootMutation:
        type: object
        config:
            fields:
                uploadFile:
                    builder: "Relay::Mutation"
                    builderConfig:
                        inputType: UploadFileInput
                        payloadType: UploadFilePayload
                        mutateAndGetPayload: '@=mutation("App\\GraphQL\\Mutation\\UploadMutation", [serv("request_stack"), value["title"]])'
    
    UploadFilePayload:
        type: relay-mutation-payload
        config:
            fields:
                files: {type: "[String!]!" }
    
    UploadFileInput:
        type: relay-mutation-input
        config:
            fields:
                title: {type: "String!"}
    ```

* here what the mutation look like

    ```php
    <?php
    
    namespace App\GraphQL\Mutation;
    
    use Overblog\GraphQLBundle\Error\UserError;
    use Symfony\Component\HttpFoundation\Request;
    
    class UploadMutation
    {
        public function __invoke(Request $request, $title)
        {
            /** @var \Symfony\Component\HttpFoundation\FileBag; $requestFiles */
            $requestFiles = $request->files;
            if (!$requestFiles->has('myFile')) {
                throw new UserError('File "myFile" is required.');
            }
            /** @var \Symfony\Component\HttpFoundation\File\UploadedFile $file */
            $file = $requestFiles->get('myFile');
     
            // here do some work on your uploaded file
    
            return [$title];
        }
    }
    ```
