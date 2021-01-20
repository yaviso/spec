<?php
/**
 * Copyright 2020 Cloud Creativity Limited
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

declare(strict_types=1);

namespace LaravelJsonApi\Spec\Tests\Integration;

use LaravelJsonApi\Contracts\Schema\Attribute;
use LaravelJsonApi\Contracts\Schema\Relation;
use LaravelJsonApi\Spec\Document;
use LaravelJsonApi\Spec\UnexpectedDocumentException;
use LaravelJsonApi\Spec\RelationBuilder;
use LaravelJsonApi\Spec\ResourceBuilder;
use LaravelJsonApi\Spec\Specification;
use LogicException;

class Test extends TestCase
{

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(Specification::class, $spec = $this->createMock(Specification::class));

        $spec->method('clientIds')->willReturnCallback(fn($type) => 'podcasts' === $type);
        $spec->method('exists')->willReturnCallback(fn($type, $id) => '999' !== $id);
        $spec->method('fields')->willReturnMap([
            ['posts', [
                $this->createAttribute('title'),
                $this->createAttribute('content'),
                $this->createAttribute('slug'),
                $this->createToOne('author'),
                $this->createToMany('tags'),
            ]],
            ['users', [
                $this->createAttribute('name'),
            ]],
        ]);
        $spec->method('types')->willReturn(['posts', 'users', 'comments', 'podcasts', 'tags']);
    }

    /**
     * @return array[]
     */
    public function createProvider(): array
    {
        return [
            'data:required' => [
                new \stdClass(),
                [
                    'title' => 'Non-Compliant JSON API Document',
                    'detail' => "The member data is required.",
                    'status' => '400',
                    'source' => ['pointer' => '/'],
                ],
            ],
            'data:not object' => [
                ['data' => []],
                [
                    'title' => 'Non-Compliant JSON API Document',
                    'detail' => "The member data must be an object.",
                    'status' => '400',
                    'source' => ['pointer' => '/data'],
                ],
            ],
            'data.type:required' => [
                [
                    'data' => [
                        'attributes' => ['title' => 'Hello World'],
                    ],
                ],
                [
                    'title' => 'Non-Compliant JSON API Document',
                    'detail' => "The member type is required.",
                    'status' => '400',
                    'source' => ['pointer' => '/data'],
                ],
            ],
            'data.type:not string' => [
                [
                    'data' => [
                        'type' => null,
                        'attributes' => ['title' => 'Hello World'],
                    ],
                ],
                [
                    'title' => 'Non-Compliant JSON API Document',
                    'detail' => "The member type must be a string.",
                    'status' => '400',
                    'source' => ['pointer' => '/data/type'],
                ],
            ],
            'data.type:empty' => [
                [
                    'data' => [
                        'type' => '',
                        'attributes' => ['title' => 'Hello World'],
                    ],
                ],
                [
                    'title' => 'Non-Compliant JSON API Document',
                    'detail' => "The member type cannot be empty.",
                    'status' => '400',
                    'source' => ['pointer' => '/data/type'],
                ],
            ],
            'data.type:not supported' => [
                [
                    'data' => [
                        'type' => 'users',
                        'attributes' => ['name' => 'John Doe'],
                    ],
                ],
                [
                    'title' => 'Not Supported',
                    'detail' => "Resource type users is not supported by this endpoint.",
                    'status' => '409',
                    'source' => ['pointer' => '/data/type'],
                ],
            ],
            'data.id:client id not allowed' => [
                [
                    'data' => [
                        'type' => 'posts',
                        'id' => 'foobar',
                        'attributes' => ['title' => 'Hello World'],
                    ],
                ],
                [
                    'title' => 'Not Supported',
                    'detail' => 'Resource type posts does not support client-generated IDs.',
                    'status' => '403',
                    'source' => ['pointer' => '/data/id'],
                ],
            ],
            'data.attributes:not object' => [
                [
                    'data' => [
                        'type' => 'posts',
                        'attributes' => [],
                    ],
                ],
                [
                    'title' => 'Non-Compliant JSON API Document',
                    'detail' => "The member attributes must be an object.",
                    'status' => '400',
                    'source' => ['pointer' => '/data/attributes'],
                ],
            ],
            'data.attributes:type not allowed' => [
                [
                    'data' => [
                        'type' => 'posts',
                        'attributes' => [
                            'type' => 'foo',
                        ],
                    ],
                ],
                [
                    'title' => 'Non-Compliant JSON API Document',
                    'detail' => "The member attributes cannot have a type field.",
                    'status' => '400',
                    'source' => ['pointer' => '/data/attributes'],
                ],
            ],
            'data.attributes:id not allowed' => [
                [
                    'data' => [
                        'type' => 'posts',
                        'attributes' => [
                            'id' => '123',
                        ],
                    ],
                ],
                [
                    'title' => 'Non-Compliant JSON API Document',
                    'detail' => "The member attributes cannot have a id field.",
                    'status' => '400',
                    'source' => ['pointer' => '/data/attributes'],
                ],
            ],
            'data.attributes.*:unrecognised' => [
                [
                    'data' => [
                        'type' => 'posts',
                        'attributes' => [
                            'title' => 'Hello World',
                            'content' => '...',
                            'foo' => 'bar',
                        ],
                    ],
                ],
                [
                    'title' => 'Non-Compliant JSON API Document',
                    'detail' => 'The field foo is not a supported attribute.',
                    'status' => '400',
                    'source' => ['pointer' => '/data/attributes'],
                ],
            ],
            'data.relationships:not object' => [
                [
                    'data' => [
                        'type' => 'posts',
                        'attributes' => [
                            'title' => 'Hello World',
                            'content' => '...',
                            'slug' => 'hello-world',
                        ],
                        'relationships' => [],
                    ],
                ],
                [
                    'title' => 'Non-Compliant JSON API Document',
                    'detail' => "The member relationships must be an object.",
                    'status' => '400',
                    'source' => ['pointer' => '/data/relationships'],
                ],
            ],
            'data.relationships:type not allowed' => [
                [
                    'data' => [
                        'type' => 'posts',
                        'relationships' => [
                            'type' => [
                                'data' => null,
                            ],
                        ],
                    ],
                ],
                [
                    'title' => 'Non-Compliant JSON API Document',
                    'detail' => "The member relationships cannot have a type field.",
                    'status' => '400',
                    'source' => ['pointer' => '/data/relationships'],
                ],
            ],
            'data.relationships:id not allowed' => [
                [
                    'data' => [
                        'type' => 'posts',
                        'relationships' => [
                            'id' => [
                                'data' => null,
                            ],
                        ],
                    ],
                ],
                [
                    'title' => 'Non-Compliant JSON API Document',
                    'detail' => "The member relationships cannot have a id field.",
                    'status' => '400',
                    'source' => ['pointer' => '/data/relationships'],
                ],
            ],
            'data.relationships.*:not object' => [
                [
                    'data' => [
                        'type' => 'posts',
                        'attributes' => [
                            'title' => 'Hello World',
                            'content' => '...',
                            'slug' => 'hello-world',
                        ],
                        'relationships' => [
                            'author' => [],
                        ],
                    ],
                ],
                [
                    'title' => 'Non-Compliant JSON API Document',
                    'detail' => "The member author must be an object.",
                    'status' => '400',
                    'source' => ['pointer' => '/data/relationships/author'],
                ],
            ],
            'data.relationships.*:unrecognised' => [
                [
                    'data' => [
                        'type' => 'posts',
                        'attributes' => [
                            'title' => 'Hello World',
                            'content' => '...',
                        ],
                        'relationships' => [
                            'foo' => [
                                'data' => null,
                            ],
                        ],
                    ],
                ],
                [
                    'title' => 'Non-Compliant JSON API Document',
                    'detail' => 'The field foo is not a supported relationship.',
                    'status' => '400',
                    'source' => ['pointer' => '/data/relationships'],
                ],
            ],
            'data.relationships.*.data:required' => [
                [
                    'data' => [
                        'type' => 'posts',
                        'attributes' => [
                            'title' => 'Hello World',
                            'content' => '...',
                            'slug' => 'hello-world',
                        ],
                        'relationships' => [
                            'author' => [
                                'meta' => ['foo' => 'bar'],
                            ],
                        ],
                    ],
                ],
                [
                    'title' => 'Non-Compliant JSON API Document',
                    'detail' => "The member data is required.",
                    'status' => '400',
                    'source' => ['pointer' => '/data/relationships/author'],
                ],
            ],
            'data.relationships.*.data:not object' => [
                [
                    'data' => [
                        'type' => 'posts',
                        'attributes' => [
                            'title' => 'Hello World',
                            'content' => '...',
                            'slug' => 'hello-world',
                        ],
                        'relationships' => [
                            'author' => [
                                'data' => false,
                            ],
                        ],
                    ],
                ],
                [
                    'title' => 'Non-Compliant JSON API Document',
                    'detail' => "The member data must be an object.",
                    'status' => '400',
                    'source' => ['pointer' => '/data/relationships/author/data'],
                ],
            ],
            'data.relationships.*.data.type:required' => [
                [
                    'data' => [
                        'type' => 'posts',
                        'attributes' => [
                            'title' => 'Hello World',
                            'content' => '...',
                            'slug' => 'hello-world',
                        ],
                        'relationships' => [
                            'author' => [
                                'data' => [
                                    'id' => '123',
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'title' => 'Non-Compliant JSON API Document',
                    'detail' => "The member type is required.",
                    'status' => '400',
                    'source' => ['pointer' => '/data/relationships/author/data'],
                ],
            ],
            'data.relationships.*.data.id:required' => [
                [
                    'data' => [
                        'type' => 'posts',
                        'attributes' => [
                            'title' => 'Hello World',
                            'content' => '...',
                            'slug' => 'hello-world',
                        ],
                        'relationships' => [
                            'author' => [
                                'data' => [
                                    'type' => 'users',
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'title' => 'Non-Compliant JSON API Document',
                    'detail' => "The member id is required.",
                    'status' => '400',
                    'source' => ['pointer' => '/data/relationships/author/data'],
                ],
            ],
            'data.relationships.*.data:resource does not exist' => [
                [
                    'data' => [
                        'type' => 'posts',
                        'attributes' => [
                            'title' => 'Hello World',
                            'content' => '...',
                            'slug' => 'hello-world',
                        ],
                        'relationships' => [
                            'author' => [
                                'data' => [
                                    'type' => 'users',
                                    'id' => '999',
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'title' => 'Not Found',
                    'detail' => 'The related resource does not exist.',
                    'status' => '404',
                    'source' => ['pointer' => '/data/relationships/author'],
                ],
            ],
            'data.relationships.*.data.*:not object' => [
                [
                    'data' => [
                        'type' => 'posts',
                        'attributes' => [
                            'title' => 'Hello World',
                            'content' => '...',
                            'slug' => 'hello-world',
                        ],
                        'relationships' => [
                            'tags' => [
                                'data' => [
                                    [],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'title' => 'Non-Compliant JSON API Document',
                    'detail' => "The member 0 must be an object.",
                    'status' => '400',
                    'source' => ['pointer' => '/data/relationships/tags/data/0'],
                ],
            ],
            'data.relationships.*.data.*.type:required' => [
                [
                    'data' => [
                        'type' => 'posts',
                        'attributes' => [
                            'title' => 'Hello World',
                            'content' => '...',
                            'slug' => 'hello-world',
                        ],
                        'relationships' => [
                            'author' => [
                                'data' => null,
                            ],
                            'tags' => [
                                'data' => [
                                    [
                                        'id' => '1',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'title' => 'Non-Compliant JSON API Document',
                    'detail' => "The member type is required.",
                    'status' => '400',
                    'source' => ['pointer' => '/data/relationships/tags/data/0'],
                ],
            ],
            'data.relationships.*.data.*.id:required' => [
                [
                    'data' => [
                        'type' => 'posts',
                        'attributes' => [
                            'title' => 'Hello World',
                            'content' => '...',
                            'slug' => 'hello-world',
                        ],
                        'relationships' => [
                            'author' => [
                                'data' => null,
                            ],
                            'tags' => [
                                'data' => [
                                    [
                                        'type' => 'tags',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'title' => 'Non-Compliant JSON API Document',
                    'detail' => "The member id is required.",
                    'status' => '400',
                    'source' => ['pointer' => '/data/relationships/tags/data/0'],
                ],
            ],
            'data.relationships.*.data.*:resource does not exist' => [
                [
                    'data' => [
                        'type' => 'posts',
                        'attributes' => [
                            'title' => 'Hello World',
                            'content' => '...',
                            'slug' => 'hello-world',
                        ],
                        'relationships' => [
                            'tags' => [
                                'data' => [
                                    [
                                        'type' => 'tags',
                                        'id' => '999',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'title' => 'Not Found',
                    'detail' => 'The related resource does not exist.',
                    'status' => '404',
                    'source' => ['pointer' => '/data/relationships/tags/data/0'],
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public function updateProvider(): array
    {
        return [
            'data.id:required' => [
                [
                    'data' => [
                        'type' => 'posts',
                        'attributes' => ['title' => 'Hello World'],
                    ],
                ],
                [
                    'title' => 'Non-Compliant JSON API Document',
                    'detail' => "The member id is required.",
                    'status' => '400',
                    'source' => ['pointer' => '/data'],
                ],
            ],
            'data.id:not string' => [
                [
                    'data' => [
                        'type' => 'posts',
                        'id' => null,
                        'attributes' => ['title' => 'Hello World'],
                    ],
                ],
                [
                    'title' => 'Non-Compliant JSON API Document',
                    'detail' => "The member id must be a string.",
                    'status' => '400',
                    'source' => ['pointer' => '/data/id'],
                ],
            ],
            'data.id:integer' => [
                [
                    'data' => [
                        'type' => 'posts',
                        'id' => 1,
                        'attributes' => ['title' => 'Hello World'],
                    ],
                ],
                [
                    'title' => 'Non-Compliant JSON API Document',
                    'detail' => "The member id must be a string.",
                    'status' => '400',
                    'source' => ['pointer' => '/data/id'],
                ],
            ],
            'data.id:empty' => [
                [
                    'data' => [
                        'type' => 'posts',
                        'id' => '',
                        'attributes' => ['title' => 'Hello World'],
                    ],
                ],
                [
                    'title' => 'Non-Compliant JSON API Document',
                    'detail' => "The member id cannot be empty.",
                    'status' => '400',
                    'source' => ['pointer' => '/data/id'],
                ],
            ],
            'data.id:not supported' => [
                [
                    'data' => [
                        'type' => 'posts',
                        'id' => '10',
                        'attributes' => ['title' => 'Hello World'],
                    ],
                ],
                [
                    'title' => 'Not Supported',
                    'detail' => "Resource id 10 is not supported by this endpoint.",
                    'status' => '409',
                    'source' => ['pointer' => '/data/id'],
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public function toOneProvider(): array
    {
        return [
            'data:required' => [
                new \stdClass(),
                [
                    'title' => 'Non-Compliant JSON API Document',
                    'detail' => "The member data is required.",
                    'status' => '400',
                    'source' => ['pointer' => '/'],
                ],
            ],
            'data:not object' => [
                ['data' => false],
                [
                    'title' => 'Non-Compliant JSON API Document',
                    'detail' => "The member data must be an object.",
                    'status' => '400',
                    'source' => ['pointer' => '/data'],
                ],
            ],
            'data.type:required' => [
                [
                    'data' => [
                        'id' => '1',
                    ],
                ],
                [
                    'title' => 'Non-Compliant JSON API Document',
                    'detail' => "The member type is required.",
                    'status' => '400',
                    'source' => ['pointer' => '/data'],
                ],
            ],
            'data.type:not string' => [
                [
                    'data' => [
                        'type' => null,
                        'id' => '1',
                    ],
                ],
                [
                    'title' => 'Non-Compliant JSON API Document',
                    'detail' => "The member type must be a string.",
                    'status' => '400',
                    'source' => ['pointer' => '/data/type'],
                ],
            ],
            'data.type:empty' => [
                [
                    'data' => [
                        'type' => '',
                        'id' => '1',
                    ],
                ],
                [
                    'title' => 'Non-Compliant JSON API Document',
                    'detail' => "The member type cannot be empty.",
                    'status' => '400',
                    'source' => ['pointer' => '/data/type'],
                ],
            ],
            'data.type:not recognised' => [
                [
                    'data' => [
                        'type' => 'foobar',
                        'id' => '1',
                    ],
                ],
                [
                    'title' => 'Not Supported',
                    'detail' => "Resource type foobar is not recognised.",
                    'status' => '400',
                    'source' => ['pointer' => '/data/type'],
                ],
            ],
            'data.id:required' => [
                [
                    'data' => [
                        'type' => 'users',
                    ],
                ],
                [
                    'title' => 'Non-Compliant JSON API Document',
                    'detail' => "The member id is required.",
                    'status' => '400',
                    'source' => ['pointer' => '/data'],
                ],
            ],
            'data.id:not string' => [
                [
                    'data' => [
                        'type' => 'users',
                        'id' => null,
                    ],
                ],
                [
                    'title' => 'Non-Compliant JSON API Document',
                    'detail' => "The member id must be a string.",
                    'status' => '400',
                    'source' => ['pointer' => '/data/id'],
                ],
            ],
            'data.id:integer' => [
                [
                    'data' => [
                        'type' => 'users',
                        'id' => 1,
                    ],
                ],
                [
                    'title' => 'Non-Compliant JSON API Document',
                    'detail' => "The member id must be a string.",
                    'status' => '400',
                    'source' => ['pointer' => '/data/id'],
                ],
            ],
            'data.id:empty' => [
                [
                    'data' => [
                        'type' => 'users',
                        'id' => '',
                    ],
                ],
                [
                    'title' => 'Non-Compliant JSON API Document',
                    'detail' => "The member id cannot be empty.",
                    'status' => '400',
                    'source' => ['pointer' => '/data/id'],
                ],
            ],
            'data:does not exist' => [
                [
                    'data' => [
                        'type' => 'users',
                        'id' => '999',
                    ],
                ],
                [
                    'title' => 'Not Found',
                    'detail' => 'The related resource does not exist.',
                    'status' => '404',
                    'source' => ['pointer' => '/data'],
                ],
            ],
            'data:resource object with attributes' => [
                [
                    'data' => [
                        'type' => 'users',
                        'id' => '1',
                        'attributes' => [
                            'name' => 'John Doe',
                        ],
                    ],
                ],
                [
                    'title' => 'Non-Compliant JSON API Document',
                    'detail' => 'The member data must be a resource identifier.',
                    'status' => '400',
                    'source' => ['pointer' => '/data'],
                ],
            ],
            'data:resource object with relationships' => [
                [
                    'data' => [
                        'type' => 'users',
                        'id' => '1',
                        'relationships' => [
                            'sites' => [
                                'data' => [],
                            ],
                        ],
                    ],
                ],
                [
                    'title' => 'Non-Compliant JSON API Document',
                    'detail' => 'The member data must be a resource identifier.',
                    'status' => '400',
                    'source' => ['pointer' => '/data'],
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public function toManyProvider(): array
    {
        return [
            'data:required' => [
                new \stdClass(),
                [
                    'title' => 'Non-Compliant JSON API Document',
                    'detail' => "The member data is required.",
                    'status' => '400',
                    'source' => ['pointer' => '/'],
                ],
            ],
            'data:to-one' => [
                [
                    'data' => [
                        'type' => 'posts',
                        'id' => '123',
                    ],
                ],
                [
                    'title' => 'Non-Compliant JSON API Document',
                    'detail' => "The member data must be an array.",
                    'status' => '400',
                    'source' => ['pointer' => '/data'],
                ],
            ],
            'data.type:required' => [
                [
                    'data' => [
                        ['id' => '1'],
                    ],
                ],
                [
                    'title' => 'Non-Compliant JSON API Document',
                    'detail' => "The member type is required.",
                    'status' => '400',
                    'source' => ['pointer' => '/data/0'],
                ],
            ],
            'data.type:not string' => [
                [
                    'data' => [
                        ['type' => null, 'id' => '1'],
                    ],
                ],
                [
                    'title' => 'Non-Compliant JSON API Document',
                    'detail' => "The member type must be a string.",
                    'status' => '400',
                    'source' => ['pointer' => '/data/0/type'],
                ],
            ],
            'data.type:empty' => [
                [
                    'data' => [
                        ['type' => '', 'id' => '1'],
                    ],
                ],
                [
                    'title' => 'Non-Compliant JSON API Document',
                    'detail' => "The member type cannot be empty.",
                    'status' => '400',
                    'source' => ['pointer' => '/data/0/type'],
                ],
            ],
            'data.type:not recognised' => [
                [
                    'data' => [
                        ['type' => 'foobar', 'id' => '1'],
                    ],
                ],
                [
                    'title' => 'Not Supported',
                    'detail' => "Resource type foobar is not recognised.",
                    'status' => '400',
                    'source' => ['pointer' => '/data/0/type'],
                ],
            ],
            'data.id:required' => [
                [
                    'data' => [
                        ['type' => 'tags'],
                    ],
                ],
                [
                    'title' => 'Non-Compliant JSON API Document',
                    'detail' => "The member id is required.",
                    'status' => '400',
                    'source' => ['pointer' => '/data/0'],
                ],
            ],
            'data.id:not string' => [
                [
                    'data' => [
                        ['type' => 'tags', 'id' => null],
                    ],
                ],
                [
                    'title' => 'Non-Compliant JSON API Document',
                    'detail' => "The member id must be a string.",
                    'status' => '400',
                    'source' => ['pointer' => '/data/0/id'],
                ],
            ],
            'data.id:integer' => [
                [
                    'data' => [
                        ['type' => 'tags', 'id' => 1],
                    ],
                ],
                [
                    'title' => 'Non-Compliant JSON API Document',
                    'detail' => "The member id must be a string.",
                    'status' => '400',
                    'source' => ['pointer' => '/data/0/id'],
                ],
            ],
            'data.id:empty' => [
                [
                    'data' => [
                        ['type' => 'tags', 'id' => ''],
                    ],
                ],
                [
                    'title' => 'Non-Compliant JSON API Document',
                    'detail' => "The member id cannot be empty.",
                    'status' => '400',
                    'source' => ['pointer' => '/data/0/id'],
                ],
            ],
            'data:does not exist' => [
                [
                    'data' => [
                        ['type' => 'tags', 'id' => '999'],
                    ],
                ],
                [
                    'title' => 'Not Found',
                    'detail' => 'The related resource does not exist.',
                    'status' => '404',
                    'source' => ['pointer' => '/data/0'],
                ],
            ],
            'data:resource object with attributes' => [
                [
                    'data' => [
                        [
                            'type' => 'tags',
                            'id' => '100',
                            'attributes' => [
                                'name' => 'News',
                            ],
                        ],
                    ],
                ],
                [
                    'title' => 'Non-Compliant JSON API Document',
                    'detail' => 'The member 0 must be a resource identifier.',
                    'status' => '400',
                    'source' => ['pointer' => '/data/0'],
                ],
            ],
        ];
    }

    public function testInvalidJson(): void
    {
        /** @var ResourceBuilder $builder */
        $builder = $this->app->make(ResourceBuilder::class);

        try {
            $builder->expects('posts', '1')->build(
                '{"data": {}"'
            );

            $this->fail('No exception thrown.');
        } catch (UnexpectedDocumentException $ex) {
            $this->assertInstanceOf(\JsonException::class, $ex->getPrevious());
        }
    }

    /**
     * @return array
     */
    public function nonObjectProvider(): array
    {
        return [
            ['true'],
            ['false'],
            ['""'],
            ['"foo"'],
            ['"1"'],
            ['1'],
            ['0.1'],
            ['[]'],
        ];
    }

    /**
     * @param string $json
     * @throws \JsonException
     * @dataProvider nonObjectProvider
     */
    public function testNonObject(string $json): void
    {
        $this->expectException(UnexpectedDocumentException::class);

        /** @var ResourceBuilder $builder */
        $builder = $this->app->make(ResourceBuilder::class);

        $builder->expects('posts', '1')->build($json);
    }

    public function testCustomPipe(): void
    {
        $ex = new LogicException('Boom!');

        $this->expectExceptionObject($ex);

        /** @var ResourceBuilder $builder */
        $builder = $this->app->make(ResourceBuilder::class);

        $builder->expects('posts', null)->using(function () use ($ex) {
            throw $ex;
        })->build(json_encode([
            'data' => [
                'type' => 'posts',
                'attributes' => [
                    'title' => 'Hello World',
                ],
            ],
        ]));
    }

    /**
     * @param $json
     * @param array $expected
     * @dataProvider createProvider
     */
    public function testCreate($json, array $expected): void
    {
        ksort($expected);

        /** @var ResourceBuilder $builder */
        $builder = $this->app->make(ResourceBuilder::class);

        $document = $builder
            ->expects('posts', null)
            ->build(json_encode($json));

        $this->assertInvalid($document, [$expected]);
    }

    /**
     * @param $json
     * @param array $expected
     * @dataProvider updateProvider
     */
    public function testUpdate($json, array $expected): void
    {
        ksort($expected);

        /** @var ResourceBuilder $builder */
        $builder = $this->app->make(ResourceBuilder::class);

        $document = $builder
            ->expects('posts', '1')
            ->build(json_encode($json));

        $this->assertInvalid($document, [$expected]);
    }

    public function testDuplicateFields(): void
    {
        $json = [
            'data' => [
                'type' => 'posts',
                'id' => '1',
                'attributes' => [
                    'author' => null,
                ],
                'relationships' => [
                    'author' => [
                        'data' => null,
                    ],
                ],
            ],
        ];

        $expected = [
            [
                'detail' => 'The author field cannot exist as an attribute and a relationship.',
                'source' => ['pointer' => '/data'],
                'status' => '400',
                'title' => 'Non-Compliant JSON API Document',
            ],
            [
                'detail' => 'The field author is not a supported attribute.',
                'source' => ['pointer' => '/data/attributes'],
                'status' => '400',
                'title' => 'Non-Compliant JSON API Document',
            ],
        ];

        /** @var ResourceBuilder $builder */
        $builder = $this->app->make(ResourceBuilder::class);

        $document = $builder
            ->expects('posts', '1')
            ->build(json_encode($json));

        $this->assertInvalid($document, $expected);
    }

    /**
     * @param $json
     * @param array $expected
     * @dataProvider toOneProvider
     */
    public function testToOne($json, array $expected): void
    {
        ksort($expected);

        /** @var RelationBuilder $builder */
        $builder = $this->app->make(RelationBuilder::class);

        $document = $builder
            ->expects('posts', 'author')
            ->build(json_encode($json));

        $this->assertInvalid($document, [$expected]);
    }

    /**
     * @param $json
     * @param array $expected
     * @dataProvider toManyProvider
     */
    public function testToMany($json, array $expected): void
    {
        ksort($expected);

        /** @var RelationBuilder $builder */
        $builder = $this->app->make(RelationBuilder::class);

        $document = $builder
            ->expects('posts', 'tags')
            ->build(json_encode($json));

        $this->assertInvalid($document, [$expected]);
    }

    /**
     * @param Document $document
     * @param array $expected
     */
    private function assertInvalid(Document $document, array $expected): void
    {
        $this->assertFalse($document->valid());
        $this->assertTrue($document->invalid());
        $this->assertSame($expected, $document->errors()->toArray());
    }

    /**
     * @param string $name
     * @return Attribute
     */
    private function createAttribute(string $name): Attribute
    {
        $attr = $this->createMock(Attribute::class);
        $attr->method('name')->willReturn($name);

        return $attr;
    }

    /**
     * @param string $name
     * @return Relation
     */
    private function createToOne(string $name): Relation
    {
        $relation = $this->createMock(Relation::class);
        $relation->method('name')->willReturn($name);
        $relation->method('toOne')->willReturn(true);
        $relation->method('toMany')->willReturn(false);

        return $relation;
    }

    /**
     * @param string $name
     * @return Relation
     */
    private function createToMany(string $name): Relation
    {
        $relation = $this->createMock(Relation::class);
        $relation->method('name')->willReturn($name);
        $relation->method('toOne')->willReturn(false);
        $relation->method('toMany')->willReturn(true);

        return $relation;
    }
}