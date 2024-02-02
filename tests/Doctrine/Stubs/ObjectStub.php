<?php

/*
 * (c) 2016-2024 Julián Gutiérrez <juliangut@gmail.com>
 *
 * @license BSD-3-Clause
 * @link https://github.com/juliangut/slim-doctrine
 */

declare(strict_types=1);

namespace Jgut\Slim\Doctrine\Tests\Stubs;

/**
 * @internal
 */
class ObjectStub
{
    public function __construct(
        public string $key = 'none',
    ) {}
}