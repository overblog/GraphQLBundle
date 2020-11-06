<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional\Validator;

use Symfony\Component\HttpKernel\Kernel;

class ExpectedErrors
{
    public const LINKED_CONSTRAINTS = [
        'message' => 'validation',
        'extensions' => [
            'category' => 'arguments_validation_error',
            'validation' => [
                '' => [
                    ['message' => 'This value is not valid.', 'code' => '6b3befbc-2f01-4ddf-be21-b57898905284'],
                ],
                'string1' => [
                    ['message' => 'This value should be equal to "Lorem Ipsum".', 'code' => '478618a7-95ba-473d-9101-cabd45e49115'],
                ],
                'string2' => [
                    ['message' => 'This value should be equal to "Dolor Sit Amet".', 'code' => '478618a7-95ba-473d-9101-cabd45e49115'],
                ],
                'string3' => [
                    ['message' => 'This value should be equal to "{"text":"Lorem Ipsum"}".', 'code' => '478618a7-95ba-473d-9101-cabd45e49115'],
                    ['message' => 'This value should be valid JSON.', 'code' => '0789c8ad-2d2b-49a4-8356-e2ce63998504'],
                ],
            ],
        ],
        'locations' => [['line' => 3, 'column' => 17]],
        'path' => ['linkedConstraintsValidation'],
    ];

    public const COLLECTION = [
        'message' => 'validation',
        'extensions' => [
            'category' => 'arguments_validation_error',
            'validation' => [
                'addresses[0].street' => [
                    [
                        'message' => 'This value is too short. It should have 10 characters or more.',
                        'code' => '9ff3fdc4-b214-49db-8718-39c315e33d45',
                    ],
                ],
                'addresses[0].zipCode' => [
                    [
                        'message' => 'This value is not valid.',
                        'code' => '6b3befbc-2f01-4ddf-be21-b57898905284',
                    ],
                ],
                'addresses[0].period.endDate' => [
                    [
                        'message' => 'This value should be greater than "2020-01-01".',
                        'code' => '778b7ae0-84d3-481a-9dec-35fdb64b1d78',
                    ],
                ],
                'emails' => [
                    [
                        'message' => 'This collection should contain only unique elements.',
                        'code' => '7911c98d-b845-4da0-94b7-a8dac36bc55a',
                    ],
                    [
                        'message' => 'This collection should contain 3 elements or more.',
                        'code' => 'bef8e338-6ae5-4caf-b8e2-50e7b0579e69',
                    ],
                ],
                'emails[0]' => [
                    [
                        'message' => 'The email ""nonUniqueString"" is not a valid email.',
                        'code' => 'bd79c0ab-ddba-46cc-a703-a7a4b08de310',
                    ],
                ],
                'emails[1]' => [
                    [
                        'message' => 'The email ""nonUniqueString"" is not a valid email.',
                        'code' => 'bd79c0ab-ddba-46cc-a703-a7a4b08de310',
                    ],
                ],
            ],
        ],
        'locations' => [['line' => 3, 'column' => 17]],
        'path' => ['collectionValidation'],
    ];

    public static function simpleValidation(string $fieldName): array
    {
        return [
            'message' => 'validation',
            'extensions' => [
                'category' => 'arguments_validation_error',
                'validation' => [
                    'username' => [
                        [
                            'message' => 'This value is too short. It should have 5 characters or more.',
                            'code' => '9ff3fdc4-b214-49db-8718-39c315e33d45',
                        ],
                    ],
                ],
            ],
            'locations' => [
                [
                    'line' => 3,
                    'column' => 17,
                ],
            ],
            'path' => [$fieldName],
        ];
    }

    public static function cascadeWithGroups(string $fieldName): array
    {
        $validation = [
            'address.street' => [
                [
                    'message' => 'This value is too short. It should have 10 characters or more.',
                    'code' => '9ff3fdc4-b214-49db-8718-39c315e33d45',
                ],
            ],
            'address.zipCode' => [
                [
                    'message' => 'This value is not valid.',
                    'code' => '6b3befbc-2f01-4ddf-be21-b57898905284',
                ],
            ],
            'address.period.endDate' => [
                [
                    'message' => 'This value should be greater than "2020-01-01".',
                    'code' => '778b7ae0-84d3-481a-9dec-35fdb64b1d78',
                ],
            ],
            'address.city' => [
                [
                    'message' => 'The value you selected is not a valid choice.',
                    'code' => '8e179f1b-97aa-4560-a02f-2a8b42e49df7',
                ],
            ],
            'birthdate.day' => [
                [
                    'message' => 'This value should be 31 or less.',
                    'code' => '2d28afcb-e32e-45fb-a815-01c431a86a69',
                ],
            ],
        ];

        // @phpstan-ignore-next-line
        if (Kernel::VERSION_ID >= 40400) {
            $validation['birthdate.day'] = [
                [
                    'message' => 'This value should be between 1 and 31.',
                    'code' => '04b91c99-a946-4221-afc5-e65ebac401eb',
                ],
            ];
        }

        return [
            'message' => 'validation',
            'extensions' => [
                'category' => 'arguments_validation_error',
                'validation' => $validation,
            ],
            'locations' => [['line' => 3, 'column' => 17]],
            'path' => [$fieldName],
        ];
    }
}
