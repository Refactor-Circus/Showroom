<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Support;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\DatabasePresenceVerifierInterface;
use Illuminate\Validation\PresenceVerifierInterface;

/**
 * Answers the `exists` and `unique` rules of a batch of records validated
 * against one set of rules, without a query per record.
 *
 * The values the batch names in a column are read up front, in one query
 * (prime()); a plain `exists` or `unique` check on a primed value is then
 * answered from what was read. Values compare under KeyNormalizer, as the
 * database's default collation compares them. Any other check goes to the
 * app's verifier, and its answer is kept for the rest of the batch.
 *
 * Make one per batch: it does not see what the batch itself writes.
 */
final class BatchPresenceVerifier implements DatabasePresenceVerifierInterface
{
    /**
     * The stored values read, by normalized value, per connection, table and column.
     *
     * @var array<string, array<string, string>>
     */
    private array $stored = [];

    /**
     * The normalized values each column was primed with.
     *
     * @var array<string, array<string, true>>
     */
    private array $primed = [];

    /**
     * The answers the app's verifier gave, by question.
     *
     * @var array<string, int>
     */
    private array $answers = [];

    private ?string $connection = null;

    public function __construct(
        private readonly PresenceVerifierInterface $verifier,
    ) {}

    /**
     * Read which of these values a model's column holds, in one query per
     * thousand values.
     *
     * @param  class-string<Model>  $model
     * @param  array<int, mixed>  $values  Anything not a string is left to the app's verifier.
     */
    public function prime(string $model, string $column, array $values): void
    {
        $instance = new $model;
        $key = self::key($instance->getConnectionName(), $instance->getTable(), $column);
        $strings = array_values(array_unique(array_filter($values, is_string(...))));

        foreach (array_chunk($strings, 1000) as $chunk) {
            foreach ($instance->getConnection()->table($instance->getTable())->whereIn($column, $chunk)->pluck($column) as $value) {
                $this->stored[$key][KeyNormalizer::normalize((string) $value)] = (string) $value;
            }

            foreach ($chunk as $value) {
                $this->primed[$key][KeyNormalizer::normalize($value)] = true;
            }
        }
    }

    /**
     * @param  string  $collection
     * @param  string  $column
     * @param  mixed  $value  Anything not a string is left to the app's verifier.
     * @param  int|string|null  $excludeId
     * @param  string|null  $idColumn
     * @param  array<array-key, mixed>  $extra
     */
    public function getCount(mixed $collection, mixed $column, mixed $value, mixed $excludeId = null, mixed $idColumn = null, array $extra = []): int
    {
        $key = self::key($this->connection, (string) $collection, (string) $column);

        if (($excludeId === null || $excludeId === 'NULL') && $extra === [] && is_string($value) && isset($this->primed[$key][KeyNormalizer::normalize($value)])) {
            return isset($this->stored[$key][KeyNormalizer::normalize($value)]) ? 1 : 0;
        }

        return $this->ask(
            [__FUNCTION__, $this->connection, $collection, $column, $value, $excludeId, $idColumn],
            $extra,
            fn (): int => (int) $this->verifier()->getCount($collection, $column, $value, $excludeId, $idColumn, $extra),
        );
    }

    /**
     * @param  string  $collection
     * @param  string  $column
     * @param  array<int, mixed>  $values
     * @param  array<array-key, mixed>  $extra
     */
    public function getMultiCount(mixed $collection, mixed $column, array $values, array $extra = []): int
    {
        $key = self::key($this->connection, (string) $collection, (string) $column);
        $primed = $extra === [] && array_filter(
            $values,
            fn (mixed $value): bool => ! is_string($value) || ! isset($this->primed[$key][KeyNormalizer::normalize($value)]),
        ) === [];

        if ($primed) {
            // As the database counts them: the distinct stored values matched.
            $matched = array_filter(array_map(
                fn (string $value): ?string => $this->stored[$key][KeyNormalizer::normalize($value)] ?? null,
                $values,
            ));

            return count(array_unique($matched));
        }

        return $this->ask(
            [__FUNCTION__, $this->connection, $collection, $column, $values],
            $extra,
            fn (): int => (int) $this->verifier()->getMultiCount($collection, $column, $values, $extra),
        );
    }

    /**
     * @param  string|null  $connection
     */
    public function setConnection(mixed $connection): void
    {
        $this->connection = is_string($connection) ? $connection : null;
    }

    /**
     * The app's answer to a question, asked once; a question with a query
     * callback in it is asked every time.
     *
     * @param  array<int, mixed>  $question
     * @param  array<array-key, mixed>  $extra
     * @param  Closure(): int  $ask
     */
    private function ask(array $question, array $extra, Closure $ask): int
    {
        foreach ($extra as $condition) {
            if ($condition instanceof Closure) {
                return $ask();
            }
        }

        $key = serialize([...$question, $extra]);

        return $this->answers[$key] ??= $ask();
    }

    private function verifier(): PresenceVerifierInterface
    {
        if ($this->verifier instanceof DatabasePresenceVerifierInterface) {
            $this->verifier->setConnection($this->connection);
        }

        return $this->verifier;
    }

    private static function key(?string $connection, string $table, string $column): string
    {
        return ($connection ?? '').'|'.$table.'|'.$column;
    }
}
