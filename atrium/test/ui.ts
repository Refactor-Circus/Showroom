import { DOMWrapper, type VueWrapper } from '@vue/test-utils'
import { UCheckbox, UInputNumber, URadioGroup, USelect, USelectMenu, USwitch } from '@refactor-circus/atrium/ui'
import { settle } from '@refactor-circus/atrium/testing'

/*
 * Driving the module's Nuxt UI fields in tests. A <USelect>, <UCheckbox> or
 * <USwitch> renders a button (its menu opens in a portal), so a test cannot
 * `setValue()` it: these find the field by the `data-testid` it was given and
 * act through its `v-model`, as picking an item or ticking the box does.
 * A <UTable> row cannot carry a `data-testid`, so each table puts it on its
 * first cell's content and `row()` climbs from it to the row.
 */

const FIELDS = [USelect, USelectMenu, UCheckbox, USwitch, UInputNumber, URadioGroup] as const

type Wrapper = Pick<VueWrapper, 'findAllComponents' | 'find'>

/** The Nuxt UI field given `data-testid="{testid}"`. */
export function field(wrapper: Wrapper, testid: string): VueWrapper {
  for (const component of FIELDS) {
    const match = wrapper.findAllComponents(component as never).find(found => (found as VueWrapper).vm.$attrs['data-testid'] === testid)

    if (match) {
      return match as VueWrapper
    }
  }

  throw new Error(`No Nuxt UI field has data-testid="${testid}"`)
}

/** The props a field was given. */
function propsOf(wrapper: Wrapper, testid: string): Record<string, unknown> {
  return field(wrapper, testid).props() as Record<string, unknown>
}

/** Whether a Nuxt UI field with `data-testid="{testid}"` is rendered. */
export function hasField(wrapper: Wrapper, testid: string): boolean {
  try {
    field(wrapper, testid)

    return true
  } catch {
    return false
  }
}

/** Sets the field's value as the user would: picks an item, ticks a box, types a number. */
export async function pick(wrapper: Wrapper, testid: string, value: unknown): Promise<void> {
  field(wrapper, testid).vm.$emit('update:modelValue', value)
  await settle()
}

/** The field's current value. */
export function picked(wrapper: Wrapper, testid: string): unknown {
  return propsOf(wrapper, testid).modelValue
}

/** The values a select offers, in order. */
export function choices(wrapper: Wrapper, testid: string): unknown[] {
  const items = (propsOf(wrapper, testid).items ?? []) as unknown[]

  return items.map(item => item !== null && typeof item === 'object' && 'value' in item ? (item as { value: unknown }).value : item)
}

/** Whether the field is disabled. */
export function isDisabled(wrapper: Wrapper, testid: string): boolean {
  return propsOf(wrapper, testid).disabled === true
}

/** The table row holding `data-testid="{testid}"`. */
export function row(wrapper: Wrapper, testid: string): DOMWrapper<HTMLTableRowElement> {
  const marker = wrapper.find(`[data-testid="${testid}"]`)

  if (!marker.exists()) {
    throw new Error(`No row has data-testid="${testid}"`)
  }

  const tr = marker.element.closest('tr')

  if (tr === null) {
    throw new Error(`data-testid="${testid}" is not in a table row`)
  }

  return new DOMWrapper(tr)
}
