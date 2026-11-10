<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Atrium;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use RefactorCircus\Atrium\Domains\Screens\Data\Screen;
use RefactorCircus\Atrium\Support\ScreenAccess as AtriumScreenAccess;
use RefactorCircus\Showroom\Atrium\Support\Screens;
use RefactorCircus\Showroom\Domains\Flag\Enums\FlagSubject;
use RefactorCircus\Showroom\Domains\Flag\Models\FlagModel;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerModel;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;

/**
 * Flags in the dashboard: the flag-definitions screens, declared on Atrium's
 * screen extensions registry over the flag Actions, and the own-flags field
 * of the product and owner screens.
 *
 * The own-flags field offers only the flags that apply to the record, and is
 * shown only to those the flag policy's `assign` ability allows, asked with
 * the record as the Actions ask it. The form marks that it sent the field, so
 * unticking every flag clears them, and a page without the field leaves the
 * record's flags alone.
 */
final class FlagScreen
{
    /**
     * The list of flag definitions, with the create form.
     */
    public const string LIST = 'showroom.flags';

    /**
     * One flag definition, with its edit form.
     */
    public const string DETAIL = 'showroom.flag';

    /**
     * The hidden input the own-flags field sends along with its boxes.
     */
    public const string SENT = 'flags_sent';

    public static function list(): Screen
    {
        return Screens::list(self::LIST, FlagModel::class);
    }

    public static function detail(FlagModel $flag): Screen
    {
        return Screens::detail(self::DETAIL, $flag, FlagModel::class);
    }

    /**
     * The flags that may be assigned to this kind of record, in flag order.
     *
     * @return Collection<int, FlagModel>
     */
    public static function options(FlagSubject $subject): Collection
    {
        return FlagModel::query()
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get()
            ->filter(fn (FlagModel $flag): bool => $flag->appliesTo($subject))
            ->values()
            ->toBase();
    }

    /**
     * Whether the user may change this record's own flags.
     */
    public static function mayAssign(ProductModel|OwnerModel $record, ?Request $request = null): bool
    {
        return AtriumScreenAccess::allows('showroom', 'assign', FlagModel::class, $request, [$record]);
    }

    /**
     * The codes the own-flags field sent, replacing the record's set, or
     * null when the page did not show the field.
     *
     * @return list<string>|null
     */
    public static function fromForm(Request $request): ?array
    {
        if (! $request->boolean(self::SENT)) {
            return null;
        }

        /** @var list<string> $codes */
        $codes = array_values(array_filter(
            (array) $request->input('flags', []),
            fn (mixed $code): bool => is_string($code) && $code !== '',
        ));

        return $codes;
    }
}
