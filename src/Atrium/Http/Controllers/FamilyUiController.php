<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Atrium\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use RefactorCircus\Atrium\Http\Controllers\Concerns\AuthorizesScreens;
use RefactorCircus\Showroom\Atrium\Support\Labels;
use RefactorCircus\Showroom\Domains\Attribute\Enums\AttributeType;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeModel;
use RefactorCircus\Showroom\Domains\Family\Actions\CreateFamilyAction;
use RefactorCircus\Showroom\Domains\Family\Actions\DeleteFamilyAction;
use RefactorCircus\Showroom\Domains\Family\Actions\ListFamiliesAction;
use RefactorCircus\Showroom\Domains\Family\Actions\ShowFamilyAction;
use RefactorCircus\Showroom\Domains\Family\Actions\UpdateFamilyAction;
use RefactorCircus\Showroom\Domains\Family\Data\CreateFamilyData;
use RefactorCircus\Showroom\Domains\Family\Data\ListFamiliesData;
use RefactorCircus\Showroom\Domains\Family\Data\UpdateFamilyData;
use RefactorCircus\Showroom\Domains\Family\Models\FamilyModel;

final class FamilyUiController
{
    use AuthorizesScreens;

    public function index(Request $request): View
    {
        $this->authorizeScreen('viewAny', FamilyModel::class);

        // Filters are validated by the Data's own rules, so the page and the
        // JSON API accept exactly the same query.
        $filters = ListFamiliesData::from($request->query());

        /** @var view-string $view */
        $view = 'showroom::ui.families.index';

        return view($view, [
            'families' => app(ListFamiliesAction::class)->execute($filters)->withQueryString(),
            'filters' => $filters->toArray(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeScreen('create', FamilyModel::class);

        $data = CreateFamilyData::fromValidated(Labels::fromForm($request->validate(CreateFamilyData::rules())));

        $family = app(CreateFamilyAction::class)->execute($data);

        return redirect()
            ->route('atrium.showroom.families.show', $family)
            ->with('status', __('showroom::showroom.family_created'));
    }

    public function show(FamilyModel $family): View
    {
        $this->authorizeScreen('view', $family);

        $family = app(ShowFamilyAction::class)->execute($family)->load('variants');

        /** @var view-string $view */
        $view = 'showroom::ui.families.show';

        return view($view, [
            'family' => $family,
            'available' => AttributeModel::query()
                ->whereNotIn('id', $family->familyAttributes->modelKeys())
                ->orderBy('code')
                ->get(),
            'labelCandidates' => $family->familyAttributes
                ->filter(fn (AttributeModel $attribute): bool => $attribute->type === AttributeType::Text)
                ->mapWithKeys(fn (AttributeModel $attribute): array => [$attribute->code => $attribute->label()])
                ->all(),
        ]);
    }

    /**
     * The form posts every member row, each with a remove box, plus one
     * attribute to add; it is folded into the whole list the Action expects.
     */
    public function update(Request $request, FamilyModel $family): RedirectResponse
    {
        $this->authorizeScreen('update', $family);

        $input = $request->all();

        /** @var array<int, array<string, mixed>> $rows */
        $rows = is_array($input['attributes'] ?? null) ? $input['attributes'] : [];

        $members = array_values(array_map(
            // Required channels arrive as one comma-separated field.
            function (array $row): array {
                if (array_key_exists('required_channels', $row)) {
                    $channels = array_values(array_filter(array_map('trim', explode(',', (string) $row['required_channels']))));
                    $row['required_channels'] = $channels === [] ? null : $channels;
                }

                return $row;
            },
            array_filter($rows, fn (array $row): bool => ($row['remove'] ?? '0') !== '1'),
        ));

        if (is_string($input['add_attribute'] ?? null) && $input['add_attribute'] !== '') {
            $members[] = ['attribute' => $input['add_attribute'], 'is_required' => $input['add_required'] ?? '0', 'sort_order' => count($members)];
        }

        $input['attributes'] = $members;

        if (($input['label_attribute'] ?? null) === '') {
            $input['label_attribute'] = null;
        }

        $data = UpdateFamilyData::fromValidated(Labels::fromForm(Validator::validate($input, UpdateFamilyData::rules()), $family->labels));

        app(UpdateFamilyAction::class)->execute($family, $data);

        return redirect()
            ->route('atrium.showroom.families.show', $family)
            ->with('status', __('showroom::showroom.family_updated'));
    }

    public function destroy(FamilyModel $family): RedirectResponse
    {
        $this->authorizeScreen('delete', $family);

        app(DeleteFamilyAction::class)->execute($family);

        return redirect()
            ->route('atrium.showroom.families.index')
            ->with('status', __('showroom::showroom.family_deleted'));
    }
}
