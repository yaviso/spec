<?php
/*
 * Copyright 2021 Cloud Creativity Limited
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | JSON:API Specification Errors
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default translatable members
    | of JSON:API error objects. According to the JSON:API spec, the
    | `title` and `detail` members can be localized. In addition the `code`
    | member is also read from this package if you want to give the error
    | a specific code.
    |
    | Set any value to an empty string if you do not want the member to be
    | included in the error object.
    |
    | @see http://jsonapi.org/format/#errors
    */

    'member_required' => [
        'title' => 'Non-Compliant JSON:API Document',
        'detail' => 'The member :member is required.',
        'code' => '',
    ],

    'member_object_expected' => [
        'title' => 'Non-Compliant JSON:API Document',
        'detail' => 'The member :member must be an object.',
        'code' => '',
    ],

    'member_array_expected' => [
        'title' => 'Non-Compliant JSON:API Document',
        'detail' => 'The member :member must be an array.',
        'code' => '',
    ],

    'member_identifier_expected' => [
        'title' => 'Non-Compliant JSON:API Document',
        'detail' => 'The member :member must be a resource identifier.',
        'code' => '',
    ],

    'member_string_expected' => [
        'title' => 'Non-Compliant JSON:API Document',
        'detail' => 'The member :member must be a string.',
        'code' => '',
    ],

    'member_empty' => [
        'title' => 'Non-Compliant JSON:API Document',
        'detail' => 'The member :member cannot be empty.',
        'code' => '',
    ],

    'member_field_not_allowed' => [
        'title' => 'Non-Compliant JSON:API Document',
        'detail' => 'The member :member cannot have a :field field.',
        'code' => '',
    ],

    'member_field_not_supported' => [
        'title' => 'Non-Compliant JSON:API Document',
        'detail' => 'The field :field is not a supported :type.',
        'code' => '',
    ],

    'field_expects_to_one' => [
        'title' => 'Non-Compliant JSON:API Document',
        'detail' => 'The field :field must be a to-one relation.',
        'code' => '',
    ],

    'field_expects_to_many' => [
        'title' => 'Non-Compliant JSON:API Document',
        'detail' => 'The field :field must be a to-many relation.',
        'code' => '',
    ],

    'resource_type_not_supported' => [
        'title' => 'Not Supported',
        'detail' => 'Resource type :type is not supported by this endpoint.',
        'code' => '',
    ],

    'resource_type_not_supported_by_to_one_relationship' => [
        'title' => 'Unprocessable Entity',
        'detail' => 'The :field field must be a to-one relationship containing :types resources.',
        'code' => '',
    ],

    'resource_type_not_supported_by_to_many_relationship' => [
        'title' => 'Unprocessable Entity',
        'detail' => 'The :field field must be a to-many relationship containing :types resources.',
        'code' => '',
    ],

    'resource_type_not_recognised' => [
        'title' => 'Not Supported',
        'detail' => 'Resource type :type is not recognised.',
        'code' => '',
    ],

    'resource_id_not_supported' => [
        'title' => 'Not Supported',
        'detail' => 'Resource id :id is not supported by this endpoint.',
        'code' => '',
    ],

    'resource_client_ids_not_supported' => [
        'title' => 'Not Supported',
        'detail' => 'Resource type :type does not support client-generated IDs.',
        'code' => '',
    ],

    'resource_exists' => [
        'title' => 'Conflict',
        'detail' => 'Resource :id already exists.',
        'code' => '',
    ],

    'resource_not_found' => [
        'title' => 'Not Found',
        'detail' => 'The related resource does not exist.',
        'code' => '',
    ],

    'resource_field_exists_in_attributes_and_relationships' => [
        'title' => 'Non-Compliant JSON:API Document',
        'detail' => 'The :field field cannot exist as an attribute and a relationship.',
        'code' => '',
    ],
];
