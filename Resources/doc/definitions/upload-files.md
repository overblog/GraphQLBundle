### Upload files

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
