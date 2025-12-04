<?php

declare(strict_types=1);

// TODO Remove this when PHP 8.3 support is dropped.
return [
    'parameters' => [
        'ignoreErrors' => PHP_VERSION_ID < 80400
            ? [
                [
                    'message' => '#Call to an undefined method .*::getMicrosecond\(\)#',
                ],
            ]
            : [],
    ],
];
