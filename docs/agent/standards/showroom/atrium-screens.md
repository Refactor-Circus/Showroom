---
description: "Screens use only Atrium components and safelisted utilities, no package stylesheet; every screen declares itself on Atrium's screen extensions registry"
tags: [showroom, frontend, atrium, ui, blade, screen-extensions]
kind: convention
---

# Atrium Screens

Showroom's screens are built only from Atrium components and the utilities Atrium safelists; Showroom ships no styles of its own.

## No package styles

- Atrium owns every component and style of the suite. Showroom ships no `resources/css`, no stylesheet registration (`StyleRegistry`, `Atrium::css()`) and no Blade component namespace.
- Views under `resources/views/ui` use `x-atrium::*` components plus the layout utilities Atrium safelists. No `<style>` block and no `style` attribute.
- Domain views (`assets/tile`, `categories/branch`, `partials/slot-picker`) stay in Showroom but use only safelisted classes.

## Components

- Form controls in table cells and inline rows are `x-atrium::form.input|select|textarea|checkbox` with `bare`; amounts with a currency use the input's `prefix` slot.
- Repeated checkboxes sharing a name get an explicit `id`.
- Record details use `x-atrium::description-list`; bars use `x-atrium::progress`; the flash status and first error use `<x-atrium::flash />`.

## Screen declarations

- Every controller view declares its screen: `Atrium\Support\Screens::list($key, $subject)` or `detail($key, $record, $subject)`, keyed `showroom.{page}` (the 25 keys are in the README). The controller resolves it with `ScreenExtensions::resolve($screen, $request)` after its own `authorizeScreen()` / `refuseUnless()`, and passes `resolved` to the view.
- Slots are `Screen::standard()`'s only: list screens `header.actions`, `before`, `after`, `row.actions`; detail screens `header.actions`, `before`, `after`, `aside`. No built-in tabs, so each screen opens on `overview`.
- Views place `header.actions` in `x-atrium::page-header`'s `actions` slot and `x-atrium::screen-tabs` under the header. While a registered tab is open, `x-atrium::screen-tab-panel` replaces the body and only `header.actions` stays. Otherwise: `before`, the body, `aside` (in the side column when the page has one, else directly before `after`), `after`. `row.actions` is a trailing cell under an empty header cell, or the foot of each asset tile. No slot is wrapped in anything but an existing grid column.
- `permission()` extensions are asked through `ScreenAccess::allows()`, so they follow `showroom.authorization`; registrants needing their own check use `authorize()`. Rows `showroom.policies` does not cover (Impex runs) are never passed to the policies: the screen's subject class is asked instead.
- Showroom renders no other package's UI and never names one in its views or navigation. History (Keen) and the transfer run link (Impex) register themselves on these screens. Product versions remain the revert feature.
- A new page gets a key in the README table and a row in `tests/Feature/Ui/ScreenExtensionsTest.php`'s dataset.

## Enforcement

- `tests/Feature/Ui/StylesTest.php` asserts `AtriumStyles::missingClasses()` and `AtriumStyles::inlineStyles()` are `[]`.
- When a class is missing, use the closest safelisted one and ask for it in Atrium.
- `tests/Feature/Ui/ScreenExtensionsTest.php` covers every declared screen's slots, layout, access closure and own authorization; `tests/Feature/DocsTest.php` keeps the README's key list and rules in step and rejects any mention of the removed audit trail, History nav item or Impex run link.
