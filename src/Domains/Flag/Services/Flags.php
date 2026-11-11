<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Flag\Services;

use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Factory as Auth;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use RefactorCircus\Keystone\Auth\Authorizer;
use RefactorCircus\Keystone\Packages\PackageRegistry;
use RefactorCircus\Showroom\Domains\Flag\Enums\FlagSubject;
use RefactorCircus\Showroom\Domains\Flag\Models\FlagModel;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerModel;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Support\Tables;

/**
 * The flags products and owners carry.
 *
 * A record's own flags are the set it was given. Its effective flags add
 * every flag of the owners above it: an owner's ancestors, read from its
 * path, and for a product its owner's chain (a variant's root model's
 * owner). Nothing beneath can drop a flag it inherits.
 *
 * The assignment rows are polymorphic, so no foreign key removes them with
 * their record; the Actions that delete products and owners call forget().
 * Who may change a record's flags is the flag policy's `assign` ability,
 * asked only when the set changes, except inside trusted().
 */
final class Flags
{
    /**
     * How many trusted() calls are running. Above zero, authorizeAssign()
     * allows every assignment. Every call restores it as it ends, so nothing
     * outlives the callback, under Octane or a queue worker either.
     */
    private static int $trustedDepth = 0;

    public function __construct(
        private readonly Auth $auth,
        private readonly PackageRegistry $packages,
    ) {}

    /**
     * The `flags` input of a create or an update: the codes of the record's
     * own flags, replacing what it had. Each must name a flag that applies
     * to this kind of record.
     *
     * Each flag is read once per set of rules, so a batch that validates many
     * records against one set reads each code once.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function rules(FlagSubject $subject): array
    {
        /** @var array<string, FlagModel|null> $read */
        $read = [];

        return [
            'flags' => ['sometimes', 'array', 'list'],
            'flags.*' => [
                'bail',
                'string',
                'distinct',
                function (string $attribute, mixed $value, Closure $fail) use ($subject, &$read): void {
                    self::assignable($subject, $attribute, $value, $fail, $read);
                },
            ],
        ];
    }

    /**
     * The `flags` filter of a listing: `flags[]={code}`, every flag carried,
     * own or inherited.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function filterRules(): array
    {
        return [
            'flags' => ['sometimes', 'array', 'list', 'max:20'],
            'flags.*' => ['string', 'max:100'],
        ];
    }

    /**
     * Refuse a code no flag has, or a flag that does not apply to the record.
     *
     * @param  array<string, FlagModel|null>  $read  The flags already read, by code.
     */
    private static function assignable(FlagSubject $subject, string $attribute, mixed $value, Closure $fail, array &$read): void
    {
        if (! is_string($value)) {
            return;
        }

        if (! array_key_exists($value, $read)) {
            $read[$value] = FlagModel::query()->where('code', $value)->first();
        }

        $flag = $read[$value];

        if ($flag === null) {
            $fail(sprintf('The %s flag "%s" does not exist.', $attribute, $value));

            return;
        }

        if (! $flag->appliesTo($subject)) {
            $fail(sprintf('The %s flag "%s" does not apply to %ss.', $attribute, $value, $subject->value));
        }
    }

    /**
     * Whether giving a record these own flags changes its set. A new record
     * has none.
     *
     * @param  array<int, string>  $codes
     */
    public function changes(ProductModel|OwnerModel $subject, array $codes): bool
    {
        $wanted = $this->ids($codes);

        $current = $subject->exists
            ? $subject->flags()->allRelatedIds()->map(fn (mixed $id): string => (string) $id)->all()
            : [];

        sort($wanted);
        sort($current);

        return $wanted !== $current;
    }

    /**
     * Run a trusted system sync, such as an import or an ERP sync, with
     * every flag assignment allowed: authorizeAssign() asks no policy until
     * the callback returns or throws. A queued import has no signed-in user
     * for the `assign` ability to allow.
     *
     * Only for code that vouches for the flags it writes, never for request
     * handling: inside it, any caller may set any record's flags. Calls nest;
     * the mode ends with the outermost one.
     *
     * @template TResult
     *
     * @param  Closure(): TResult  $callback
     * @return TResult
     */
    public static function trusted(Closure $callback): mixed
    {
        self::$trustedDepth++;

        try {
            return $callback();
        } finally {
            self::$trustedDepth--;
        }
    }

    /**
     * Refuse, unless the caller may change this record's flags: the flag
     * policy's `assign` ability, asked with the record, or a trusted() sync.
     * Call it only when the set changes, and before writing it.
     *
     * @throws AuthorizationException
     */
    public function authorizeAssign(ProductModel|OwnerModel $subject): void
    {
        if (self::$trustedDepth > 0) {
            return;
        }

        $allowed = Authorizer::for($this->packages->get('showroom'))
            ->can($this->auth->guard()->user(), 'assign', FlagModel::class, [$subject]);

        if (! $allowed) {
            throw new AuthorizationException(sprintf(
                'You may not change the flags of %s "%s".',
                $subject instanceof ProductModel ? 'product' : 'owner',
                $subject instanceof ProductModel ? $subject->identifier : $subject->code,
            ));
        }
    }

    /**
     * Replace a record's own flags with these. Call it inside the write's
     * transaction, once the caller passed authorizeAssign().
     *
     * @param  array<int, string>  $codes
     */
    public function write(ProductModel|OwnerModel $subject, array $codes): void
    {
        $subject->flags()->sync($this->ids($codes));
    }

    /**
     * Remove every flag assignment of records of one kind.
     *
     * @param  class-string<Model>  $class
     * @param  array<int, string>  $ids
     */
    public function forget(string $class, array $ids): void
    {
        if ($ids === []) {
            return;
        }

        $this->assignments()
            ->where('subject_type', (new $class)->getMorphClass())
            ->whereIn('subject_id', $ids)
            ->delete();
    }

    /**
     * How many products and owners that still exist carry a flag. Rows left
     * by a record deleted outside the Actions do not count.
     */
    public function holders(FlagModel $flag): int
    {
        $table = Tables::name('flag_assignments');

        return $flag->assignments()
            ->where(fn (QueryBuilder $live): QueryBuilder => $live
                ->where(fn (QueryBuilder $products): QueryBuilder => $products
                    ->where($table.'.subject_type', FlagSubject::Product->morphClass())
                    ->whereIn($table.'.subject_id', ProductModel::query()->select('id')))
                ->orWhere(fn (QueryBuilder $owners): QueryBuilder => $owners
                    ->where($table.'.subject_type', FlagSubject::Owner->morphClass())
                    ->whereIn($table.'.subject_id', OwnerModel::query()->select('id'))))
            ->count();
    }

    /**
     * Each record's own flag codes, by its key, in flag order.
     *
     * @param  Collection<int, covariant ProductModel|OwnerModel>  $subjects
     * @return array<string, list<string>>
     */
    public function own(Collection $subjects): array
    {
        return array_map(fn (array $flags): array => self::codes($flags['own']), $this->resolve($subjects));
    }

    /**
     * Each record's effective flag codes — its own plus every one its owner
     * chain carries — by its key, in flag order.
     *
     * @param  Collection<int, covariant ProductModel|OwnerModel>  $subjects
     * @return array<string, list<string>>
     */
    public function effective(Collection $subjects): array
    {
        return array_map(fn (array $flags): array => self::codes($flags['effective']), $this->resolve($subjects));
    }

    /**
     * Set each record's `flags` relation (own) and its effective flags
     * (HasFlags::setEffectiveFlags()), in one query for the whole batch, so a
     * resource reads them without more.
     *
     * @param  Collection<int, covariant ProductModel|OwnerModel>  $subjects
     */
    public function load(Collection $subjects): void
    {
        $resolved = $this->resolve($subjects);

        foreach ($subjects as $subject) {
            $flags = $resolved[(string) $subject->getKey()] ?? ['own' => [], 'effective' => []];

            $subject->setRelation('flags', new EloquentCollection($flags['own']));
            $subject->setEffectiveFlags(new EloquentCollection($flags['effective']));
        }
    }

    /**
     * The ids of the products carrying a flag of their own, as a subquery.
     */
    public function productsCarrying(string $code): QueryBuilder
    {
        return $this->carrying($code, FlagSubject::Product);
    }

    /**
     * The ids of the owners whose effective flags include a flag: those
     * carrying it, and every owner beneath them.
     *
     * @return list<string>
     */
    public function ownersInheriting(string $code): array
    {
        /** @var list<string> $carriers */
        $carriers = OwnerModel::query()->whereIn('id', $this->carrying($code, FlagSubject::Owner))->pluck('path')->all();

        if ($carriers === []) {
            return [];
        }

        /** @var list<string> $ids */
        $ids = OwnerModel::query()
            ->where(function (Builder $subtrees) use ($carriers): void {
                foreach ($carriers as $path) {
                    $subtrees->orWhere('path', 'like', $path.'%');
                }
            })
            ->pluck('id')
            ->all();

        return $ids;
    }

    /**
     * The ids of the records of one kind assigned a flag of their own.
     */
    private function carrying(string $code, FlagSubject $subject): QueryBuilder
    {
        $table = Tables::name('flag_assignments');
        $flags = Tables::name('flags');

        return $this->assignments()
            ->join($flags, $flags.'.id', '=', $table.'.flag_id')
            ->where($flags.'.code', $code)
            ->where($table.'.subject_type', $subject->morphClass())
            ->select($table.'.subject_id');
    }

    /**
     * Each record's own and effective flags, by its key, read with one query:
     * the assignments of the records themselves and of every owner in their
     * chains.
     *
     * @param  Collection<int, covariant ProductModel|OwnerModel>  $subjects
     * @return array<string, array{own: list<FlagModel>, effective: list<FlagModel>}>
     */
    private function resolve(Collection $subjects): array
    {
        $ownerType = FlagSubject::Owner->morphClass();

        /** @var array<string, array<string, true>> $wanted */
        $wanted = [];
        $plans = [];

        foreach ($subjects as $subject) {
            $type = $subject->getMorphClass();
            $id = (string) $subject->getKey();
            $owner = $subject instanceof ProductModel ? $subject->effectiveOwner() : $subject;
            $chain = $owner === null ? [] : self::pathIds($owner->path);

            $wanted[$type][$id] = true;

            foreach ($chain as $ownerId) {
                $wanted[$ownerType][$ownerId] = true;
            }

            $plans[$id] = [$type, $chain];
        }

        [$flags, $assigned] = $this->assigned($wanted);

        // Flags arrive in flag order; each keeps its place.
        $rank = array_flip(array_keys($flags));
        $resolved = [];

        foreach ($plans as $id => [$type, $chain]) {
            $own = $assigned[$type][$id] ?? [];
            $effective = $own;

            foreach ($chain as $ownerId) {
                array_push($effective, ...($assigned[$ownerType][$ownerId] ?? []));
            }

            $effective = array_values(array_unique($effective));
            usort($effective, fn (string $a, string $b): int => $rank[$a] <=> $rank[$b]);

            $resolved[(string) $id] = [
                'own' => array_map(fn (string $flag): FlagModel => $flags[$flag], $own),
                'effective' => array_map(fn (string $flag): FlagModel => $flags[$flag], $effective),
            ];
        }

        return $resolved;
    }

    /**
     * The flags assigned to these records, in flag order, and which record
     * holds which, by type and key.
     *
     * @param  array<string, array<string, true>>  $wanted  Keys by subject type.
     * @return array{0: array<string, FlagModel>, 1: array<string, array<string, list<string>>>}
     */
    private function assigned(array $wanted): array
    {
        if ($wanted === []) {
            return [[], []];
        }

        $table = Tables::name('flag_assignments');
        $flagsTable = Tables::name('flags');

        $rows = $this->assignments()
            ->join($flagsTable, $flagsTable.'.id', '=', $table.'.flag_id')
            ->where(function (QueryBuilder $subjects) use ($wanted, $table): void {
                foreach ($wanted as $type => $ids) {
                    $subjects->orWhere(fn (QueryBuilder $one): QueryBuilder => $one
                        ->where($table.'.subject_type', $type)
                        ->whereIn($table.'.subject_id', array_map('strval', array_keys($ids))));
                }
            })
            ->orderBy($flagsTable.'.sort_order')
            ->orderBy($flagsTable.'.code')
            ->get([$flagsTable.'.*', $table.'.subject_type as showroom_subject_type', $table.'.subject_id as showroom_subject_id']);

        $flags = [];
        $assigned = [];

        foreach ($rows as $row) {
            /** @var array<string, mixed> $attributes */
            $attributes = (array) $row;
            $type = (string) $attributes['showroom_subject_type'];
            $subjectId = (string) $attributes['showroom_subject_id'];
            $flagId = (string) $attributes['id'];

            unset($attributes['showroom_subject_type'], $attributes['showroom_subject_id']);

            $flags[$flagId] ??= (new FlagModel)->newFromBuilder($attributes);
            $assigned[$type][$subjectId][] = $flagId;
        }

        return [$flags, $assigned];
    }

    /**
     * The ids of the flags with these codes.
     *
     * @param  array<int, string>  $codes
     * @return list<string>
     */
    private function ids(array $codes): array
    {
        if ($codes === []) {
            return [];
        }

        /** @var list<string> $ids */
        $ids = FlagModel::query()->whereIn('code', array_values(array_unique($codes)))->pluck('id')->map(fn (mixed $id): string => (string) $id)->all();

        return $ids;
    }

    private function assignments(): QueryBuilder
    {
        return (new FlagModel)->getConnection()->table(Tables::name('flag_assignments'));
    }

    /**
     * @param  list<FlagModel>  $flags
     * @return list<string>
     */
    private static function codes(array $flags): array
    {
        return array_map(fn (FlagModel $flag): string => $flag->code, $flags);
    }

    /**
     * The ids of a materialized path, root first.
     *
     * @return list<string>
     */
    private static function pathIds(string $path): array
    {
        return array_values(array_filter(explode('/', $path), fn (string $id): bool => $id !== ''));
    }
}
