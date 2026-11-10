<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Atrium\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Atrium\Domains\Screens\Services\ScreenExtensions;
use RefactorCircus\Showroom\Atrium\FlagScreen;
use RefactorCircus\Showroom\Atrium\Http\Controllers\Concerns\AuthorizesScreens;
use RefactorCircus\Showroom\Atrium\Support\Labels;
use RefactorCircus\Showroom\Domains\Flag\Actions\CreateFlagAction;
use RefactorCircus\Showroom\Domains\Flag\Actions\DeleteFlagAction;
use RefactorCircus\Showroom\Domains\Flag\Actions\ListFlagsAction;
use RefactorCircus\Showroom\Domains\Flag\Actions\ShowFlagAction;
use RefactorCircus\Showroom\Domains\Flag\Actions\UpdateFlagAction;
use RefactorCircus\Showroom\Domains\Flag\Data\CreateFlagData;
use RefactorCircus\Showroom\Domains\Flag\Data\ListFlagsData;
use RefactorCircus\Showroom\Domains\Flag\Data\UpdateFlagData;
use RefactorCircus\Showroom\Domains\Flag\Enums\FlagSubject;
use RefactorCircus\Showroom\Domains\Flag\Models\FlagModel;

/**
 * The flag definitions: the commercial markers products and owners carry.
 * A flag's code never changes, and a flag still assigned to something cannot
 * be deleted; the page shows the Action's refusal.
 */
final class FlagUiController
{
    use AuthorizesScreens;

    public function index(Request $request): View
    {
        $this->authorizeScreen('viewAny', FlagModel::class);

        /** @var view-string $view */
        $view = 'showroom::ui.flags.index';

        return view($view, [
            'resolved' => app(ScreenExtensions::class)->resolve(FlagScreen::list(), $request),
            'flags' => app(ListFlagsAction::class)->execute(ListFlagsData::from($request->query()))->withQueryString(),
            'subjects' => FlagSubject::values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeScreen('create', FlagModel::class);

        $flag = app(CreateFlagAction::class)->execute(
            CreateFlagData::fromValidated(Labels::fromForm(Validator::validate($this->fromForm($request), CreateFlagData::rules()))),
        );

        return redirect()
            ->route('atrium.showroom.flags.show', $flag)
            ->with('status', __('showroom::showroom.flag_created'));
    }

    public function show(Request $request, FlagModel $flag): View
    {
        $this->authorizeScreen('view', $flag);

        $flag = app(ShowFlagAction::class)->execute($flag);

        /** @var view-string $view */
        $view = 'showroom::ui.flags.show';

        return view($view, [
            'resolved' => app(ScreenExtensions::class)->resolve(FlagScreen::detail($flag), $request),
            'flag' => $flag,
            'subjects' => FlagSubject::values(),
        ]);
    }

    public function update(Request $request, FlagModel $flag): RedirectResponse
    {
        $this->authorizeScreen('update', $flag);

        app(UpdateFlagAction::class)->execute(
            $flag,
            UpdateFlagData::fromValidated(Labels::fromForm(Validator::validate($this->fromForm($request), UpdateFlagData::rules()), $flag->labels)),
        );

        return redirect()
            ->route('atrium.showroom.flags.show', $flag)
            ->with('status', __('showroom::showroom.flag_updated'));
    }

    public function destroy(FlagModel $flag): RedirectResponse
    {
        $this->authorizeScreen('delete', $flag);

        try {
            app(DeleteFlagAction::class)->execute($flag);
        } catch (ValidationException $e) {
            // Still assigned: the page shows why.
            return back()->withErrors($e->errors());
        }

        return redirect()
            ->route('atrium.showroom.flags.index')
            ->with('status', __('showroom::showroom.flag_deleted'));
    }

    /**
     * The `applies_to` boxes arrive as a list, and an empty sort order means
     * the default.
     *
     * @return array<string, mixed>
     */
    private function fromForm(Request $request): array
    {
        $input = $request->except(['_token', '_method']);

        if (array_key_exists('applies_to', $input)) {
            $input['applies_to'] = array_values(array_filter((array) $input['applies_to']));
        }

        if (($input['sort_order'] ?? null) === null || $input['sort_order'] === '') {
            unset($input['sort_order']);
        }

        return $input;
    }
}
