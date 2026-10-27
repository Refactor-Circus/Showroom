---
description: "Screens use only Atrium components and safelisted utilities, no package stylesheet; show screens carry the audit trail"
tags: [showroom, frontend, atrium, ui, blade, audit-trail]
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

## Audit trail

- Each main show screen ends with `<x-atrium::audit-trail source="showroom" :subject="$record" />`; the product list carries `<x-atrium::audit-trail source="showroom" />`.
- Both render nothing until `refactor-circus/keen` is installed. Product versions remain the revert feature.

## Enforcement

- `tests/Feature/Ui/StylesTest.php` asserts `AtriumStyles::missingClasses()` and `AtriumStyles::inlineStyles()` are `[]`.
- When a class is missing, use the closest safelisted one and ask for it in Atrium.
