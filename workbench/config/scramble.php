<?php

declare(strict_types=1);

use Dedoc\Scramble\OpenApiVersion;

/*
|--------------------------------------------------------------------------
| Scramble (workbench only)
|--------------------------------------------------------------------------
|
| Describes Showroom's JSON API as openapi.json (`composer openapi:export`)
| and at /docs/api in the workbench. dedoc/scramble is a dev dependency:
| nothing under src/ or config/ names it.
|
| Every value is fixed, never read from the environment, so the export is
| byte-stable on any machine: the version is the package's, and the server
| URL is set relative (`/showroom`) by the workbench's document transformer.
| `tests/OpenApi/OpenApiTest.php` re-exports with this file and fails when
| the committed openapi.json differs.
|
*/

return [
    'api_path' => 'showroom',

    'api_domain' => null,

    'export_path' => 'openapi.json',

    'cache' => [
        'key' => 'scramble.openapi',
        'store' => 'array',
    ],

    'openapi_version' => OpenApiVersion::V3_1,

    'info' => [
        'version' => '2.0.0',
        'description' => 'Showroom\'s JSON API: the product catalog, its workflow, and the stats, search and settings summary an admin front end reads.',
    ],

    'ui' => [
        'title' => 'Showroom',
    ],

    'dev_tools' => [
        'enabled' => false,
    ],

    'renderer' => 'elements',

    'renderers' => [
        'elements' => [
            'view' => 'scramble::docs',
            'theme' => 'light',
            'hideTryIt' => false,
            'hideSchemas' => false,
            'logo' => '',
            'tryItCredentialsPolicy' => 'include',
            'layout' => 'responsive',
            'router' => 'hash',
        ],
        'scalar' => [
            'view' => 'scramble::scalar',
            'cdn' => 'https://cdn.jsdelivr.net/npm/@scalar/api-reference',
            'theme' => 'laravel',
            'proxyUrl' => 'https://proxy.scalar.com',
            'darkMode' => false,
            'showDeveloperTools' => 'never',
            'agent' => ['disabled' => true],
            'credentials' => 'include',
        ],
        'fabric' => [
            'view' => 'scramble::fabric',
        ],
    ],

    // Replaced by the relative `/showroom` server when the document is built.
    'servers' => null,

    'enum_cases_description_strategy' => 'description',

    'enum_cases_names_strategy' => false,

    'enum_cases_auto_description_strategy' => false,

    'flatten_deep_query_parameters' => true,

    // The workbench user reads the docs at /docs/api.
    'middleware' => ['web'],

    'extensions' => [],

    'security_strategy' => null,
];
