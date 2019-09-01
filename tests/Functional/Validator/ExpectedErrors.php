<?php declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional\Validator;

/**
 * Class ExpectedErrors
 *
 * @author Timur Murtukov <murtukov@gmail.com>
 */
class ExpectedErrors
{
    const SIMPLE_VALIDATION = [
        'message' => 'Invalid data set',
        'extensions' => ['category' => 'ArgumentsValidationException'],
        'path' => ['simpleValidation'],
        'state' => [
            'username' => ['This value is too short. It should have 5 characters or more.'],
        ],
        'code' => [
            'username' => ['9ff3fdc4-b214-49db-8718-39c315e33d45'],
        ],
    ];

    const LINKED_CONSTRAINTS = [
        'message' => 'Invalid data set',
        'extensions' => ['category' => 'ArgumentsValidationException'],
        'path' =>['linkedConstraintsValidation'],
        'state' => [
            '' => ['This value is not valid.'],
            'string1' => ['This value should be equal to "Lorem Ipsum".'],
            'string2' => ['This value should be equal to "Dolor Sit Amet".'],
            'string3' => [
                'This value should be equal to "{"text":"Lorem Ipsum"}".',
                'This value should be valid JSON.',
            ]
        ],
        'code' => [
            '' => ['6b3befbc-2f01-4ddf-be21-b57898905284'],
            'string1' => ['478618a7-95ba-473d-9101-cabd45e49115'],
            'string2' => ['478618a7-95ba-473d-9101-cabd45e49115'],
            'string3' => [
                '478618a7-95ba-473d-9101-cabd45e49115',
                '0789c8ad-2d2b-49a4-8356-e2ce63998504',
            ]
        ]
    ];

    const COLLECTION = [
        'message' => 'Invalid data set',
        'extensions' => ['category' => 'ArgumentsValidationException'],
        'path' => ['collectionValidation'],
        'state' => [
            'addresses[0].street' => ['This value is too short. It should have 10 characters or more.'],
            'addresses[0].zipCode' => ['This value is not valid.'],
            'addresses[0].period.endDate' => ['This value should be greater than "2020-01-01".'],
            'emails' => ['This collection should contain only unique elements.', 'This collection should contain 3 elements or more.'],
            'emails[0]' => ['The email ""nonUniqueString"" is not a valid email.'],
            'emails[1]' => ['The email ""nonUniqueString"" is not a valid email.'],
        ],
        'code' => [
            'addresses[0].street' => ['9ff3fdc4-b214-49db-8718-39c315e33d45'],
            'addresses[0].zipCode' => ['6b3befbc-2f01-4ddf-be21-b57898905284'],
            'addresses[0].period.endDate' => ['778b7ae0-84d3-481a-9dec-35fdb64b1d78'],
            'emails' => ['7911c98d-b845-4da0-94b7-a8dac36bc55a', 'bef8e338-6ae5-4caf-b8e2-50e7b0579e69'],
            'emails[0]' => ['bd79c0ab-ddba-46cc-a703-a7a4b08de310'],
            'emails[1]' => ['bd79c0ab-ddba-46cc-a703-a7a4b08de310']
        ],
    ];

    const CASCADE_WITH_GROUPS = [
        'message' => 'Invalid data set',
        'extensions' => ['category' => 'ArgumentsValidationException'],
        'path' => ['cascadeValidationWithGroups'],
        'state' => [
            'address.street' => ['This value is too short. It should have 10 characters or more.'],
            'address.zipCode' => ['This value is not valid.'],
            'address.period.endDate' => ['This value should be greater than "2020-01-01".'],
            'address.city' => ['The value you selected is not a valid choice.'],
            'birthdate.day' => ['This value should be 31 or less.']
        ],
        'code' => [
            'address.street' => ['9ff3fdc4-b214-49db-8718-39c315e33d45'],
            'address.zipCode' => ['6b3befbc-2f01-4ddf-be21-b57898905284'],
            'address.period.endDate' => ['778b7ae0-84d3-481a-9dec-35fdb64b1d78'],
            'address.city' => ['8e179f1b-97aa-4560-a02f-2a8b42e49df7'],
            'birthdate.day' => ['2d28afcb-e32e-45fb-a815-01c431a86a69']
        ]
    ];
}
