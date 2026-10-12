<?php

declare(strict_types=1);

use Laravel\Mcp\Server\Tool;
use RefactorCircus\Showroom\Mcp\ShowroomServer;

it('declares an output schema on every tool that answers with structured content', function (): void {
    $structured = collect(ShowroomServer::TOOLS)
        ->filter(function (string $class): bool {
            $handle = (new ReflectionMethod($class, 'handle'))->getParameters()[0]->getType();
            $request = $handle instanceof ReflectionNamedType ? $handle->getName() : '';
            $source = (string) file_get_contents((string) (new ReflectionClass($request))->getFileName());

            return preg_match('/toStructuredResponse\(|Response::structured\(|structuredCollection\(/', $source) === 1;
        });

    $missing = $structured
        ->reject(function (string $class): bool {
            /** @var Tool $tool */
            $tool = app($class);

            return isset($tool->toArray()['outputSchema']);
        })
        ->values()
        ->all();

    expect($structured->count())->toBe(78)
        ->and($missing)->toBe([]);
});
