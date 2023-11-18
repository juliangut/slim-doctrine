<?php

/*
 * (c) 2016-2023 Julián Gutiérrez <juliangut@gmail.com>
 *
 * @license BSD-3-Clause
 * @link https://github.com/juliangut/slim-doctrine
 */

declare(strict_types=1);

use Jgut\ECS\Config\ConfigSet80;
use Symplify\EasyCodingStandard\Config\ECSConfig;

return static function (ECSConfig $ecsConfig): void {
    $header = <<<'HEADER'
    (c) 2016-{{year}} Julián Gutiérrez <juliangut@gmail.com>

    @license BSD-3-Clause
    @link https://github.com/juliangut/slim-doctrine
    HEADER;

    $ecsConfig->paths([
        __FILE__,
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ]);

    (new ConfigSet80())
        ->setHeader($header)
        ->enablePhpUnitRules()
        ->configure($ecsConfig);
};
