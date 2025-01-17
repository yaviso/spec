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

declare(strict_types=1);

namespace LaravelJsonApi\Spec;

use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Arr;
use JsonException;
use function array_merge;
use function is_object;
use function is_string;
use function json_decode;
use function trim;

abstract class Builder
{

    /**
     * @var Pipeline
     */
    private Pipeline $pipeline;

    /**
     * @var array
     */
    private array $pipes = [];

    /**
     * @param object $json
     * @return Document
     */
    abstract protected function create(object $json): Document;

    /**
     * @return array
     */
    abstract protected function pipes(): array;

    /**
     * Builder constructor.
     *
     * @param Pipeline $pipeline
     */
    public function __construct(Pipeline $pipeline)
    {
        $this->pipeline = $pipeline;
    }

    /**
     * @param $pipes
     * @return $this
     */
    public function using($pipes): self
    {
        $this->pipes = array_merge($this->pipes, Arr::wrap($pipes));

        return $this;
    }

    /**
     * @param string $json
     * @return Document
     * @throws UnexpectedDocumentException
     */
    public function build(string $json): Document
    {
        $document = $this->create(
            $this->decode($json)
        );

        $pipes = array_merge($this->pipes(), $this->pipes);

        return $this->pipeline
            ->send($document)
            ->through($pipes)
            ->via('validate')
            ->thenReturn();
    }

    /**
     * @param string $json
     * @return object
     * @throws UnexpectedDocumentException
     */
    private function decode(string $json): object
    {
        if (empty(trim($json))) {
            throw new UnexpectedDocumentException('Expecting JSON to decode.');
        }

        try {
            if (is_string($json)) {
                $json = json_decode($json, false, 512, JSON_THROW_ON_ERROR);
            }
        } catch (JsonException $ex) {
            throw new UnexpectedDocumentException('Invalid JSON.', 0, $ex);
        }

        if (is_object($json)) {
            return $json;
        }

        throw new UnexpectedDocumentException('Expecting JSON to decode to an object.');
    }
}
