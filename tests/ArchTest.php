<?php

declare(strict_types=1);

use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Keystone\Testing\Arch;
use RefactorCircus\Showroom\Support\Tables;

const SHOWROOM_DOMAINS = __DIR__.'/../src/Domains';

// The domain Actions and the Impex flow steps, which keep their stored names.
const SHOWROOM_ACTIONS = [SHOWROOM_DOMAINS.'/*/Actions/*.php', __DIR__.'/../src/Impex/Actions/*.php'];

arch()->preset()->php();

arch()->preset()->security();

arch('it will not use dd(), ddd(), env(), or exit()')
    ->expect(['dd', 'ddd', 'env', 'exit'])
    ->each->not->toBeUsed();

arch('the package source declares strict types')
    ->expect('RefactorCircus\Showroom')
    ->toUseStrictTypes();

arch('models are final')
    ->expect(Arch::domainNamespaces('RefactorCircus\Showroom', SHOWROOM_DOMAINS, 'Models'))
    ->classes()
    ->toBeFinal();

arch('domain models are named for their entity and end in Model')
    ->expect(Arch::domainNamespaces('RefactorCircus\Showroom', SHOWROOM_DOMAINS, 'Models'))
    ->toHaveSuffix('Model');

arch('actions are final')
    ->expect(Arch::domainNamespaces('RefactorCircus\Showroom', SHOWROOM_DOMAINS, 'Actions'))
    ->classes()
    ->toBeFinal();

arch('actions take their input as Data objects, not arrays')
    ->expect(fn (): array => array_values(array_filter(
        array_map(function (string $action): ?string {
            foreach ((new ReflectionMethod($action, 'execute'))->getParameters() as $parameter) {
                $type = $parameter->getType();

                if ($type instanceof ReflectionNamedType && $type->getName() === 'array' && in_array($parameter->getName(), ['data', 'filters', 'input', 'attributes', 'values', 'options'], true)) {
                    return $action.'::execute($'.$parameter->getName().')';
                }
            }

            return null;
        }, Arch::domainClasses('RefactorCircus\Showroom', SHOWROOM_DOMAINS, 'Actions')),
    )))
    ->toBeEmpty();

arch('action input validates itself')
    ->expect(fn (): array => Arch::dataWithoutRules(Arch::domainClasses('RefactorCircus\Showroom', SHOWROOM_DOMAINS, 'Data')))
    ->toBeEmpty();

// Parity is the default, with declared exceptions: an Action missing from a
// surface fails unless PARITY_EXCEPTIONS lists it with a reason.
arch('every use case is reachable from the HTTP API, MCP and the dashboard')
    ->expect(fn (): array => parityGaps())
    ->toBeEmpty();

arch('every action dispatches a starting and a finished event')
    ->expect(fn (): array => Arch::actionEventGaps(SHOWROOM_ACTIONS))
    ->toBeEmpty();

arch('actions write inside a transaction')
    ->expect(fn (): array => Arch::writesOutsideTransactions(SHOWROOM_ACTIONS))
    ->toBeEmpty();

arch('every model fires an event for every lifecycle hook')
    ->expect(fn (): array => Arch::modelEventGaps(Arch::domainClasses('RefactorCircus\Showroom', SHOWROOM_DOMAINS, 'Models')))
    ->toBeEmpty();

arch('requests validate once, with the Data rules, and build from what they validated')
    ->expect(fn (): array => array_values(array_filter(
        [...(array) glob(SHOWROOM_DOMAINS.'/*/Http/Requests/*.php'), ...(array) glob(SHOWROOM_DOMAINS.'/*/Mcp/Requests/*.php')],
        fn (string $path): bool => str_contains((string) file_get_contents($path), 'Data::from('),
    )))
    ->toBeEmpty();

arch('data are final and readonly')
    ->expect(Arch::domainNamespaces('RefactorCircus\Showroom', SHOWROOM_DOMAINS, 'Data'))
    ->classes()
    ->toBeFinal()
    ->toBeReadonly();

arch('Data classes live in a domain\'s Data directory')
    ->expect(fn (): array => array_values(array_filter(
        array_map(
            fn (string $path): string => 'RefactorCircus\\Showroom\\'.str_replace(['/', '.php'], ['\\', ''], substr($path, strlen(dirname(SHOWROOM_DOMAINS)) + 1)),
            array_keys(iterator_to_array(new RegexIterator(
                new RecursiveIteratorIterator(new RecursiveDirectoryIterator(dirname(SHOWROOM_DOMAINS), FilesystemIterator::SKIP_DOTS)),
                '#/[A-Z]\w*\.php$#',
            ))),
        ),
        fn (string $class): bool => is_subclass_of($class, Data::class) && ! str_contains($class, '\\Data\\'),
    )))
    ->toBeEmpty();

arch('every model has a policy in showroom.policies')
    ->expect(fn (): array => Arch::modelsWithoutPolicies(
        Arch::domainClasses('RefactorCircus\Showroom', SHOWROOM_DOMAINS, 'Models'),
        (require __DIR__.'/../config/showroom.php')['policies'],
    ))
    ->toBeEmpty();

arch('tables are named through Tables, never as literals')
    ->expect(fn (): array => Arch::literalTableNames(
        [...array_values(array_filter(
            array_map(fn (SplFileInfo $file): string => $file->getPathname(), iterator_to_array(new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__.'/../src', FilesystemIterator::SKIP_DOTS)))),
            fn (string $path): bool => str_ends_with($path, '.php') && ! str_ends_with($path, 'Support/Tables.php'),
        )), ...(array) glob(__DIR__.'/../database/migrations/*.php')],
        array_values(Tables::DEFAULTS),
    ))
    ->toBeEmpty();
