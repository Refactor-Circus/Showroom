import { computed, shallowRef, watch } from 'vue'
import type { ComputedRef, ShallowRef } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import {
  ALL,
  showroomData,
  slotData,
  useShowroom,
  type CatalogList,
  type CatalogValues,
  type ShowroomAttribute,
  type ShowroomAttributeOption,
  type ShowroomChannel,
  type ShowroomFamilyVariant,
  type ShowroomLocale
} from './catalog'

/*
 * The value form of products and product models, ported from v1's
 * `partials/values`, `ValueForm` and `EditingSlot` (AC 71). The form edits
 * one slot per attribute: the picked locale for localizable attributes and
 * the picked channel for scopable ones, both kept in the page's query
 * (`?locale=&channel=`). An attribute whose locale or channel cannot be
 * picked (none exists yet) is left alone, and an empty input clears its slot.
 */

/** Which locale and channel the value form edits. */
export interface EditingSlot {
  locale: string | null
  channel: string | null
}

/** A metric's two inputs. */
export interface MetricInput {
  amount: string
  unit: string
}

/** What one attribute's inputs hold, by type. */
export type ValueInput = string | string[] | MetricInput | Record<string, string>

/** Whether the slot names everything `attribute` needs (a locale, a channel). */
export function slotAvailable(attribute: ShowroomAttribute, slot: EditingSlot): boolean {
  return !((attribute.is_localizable && slot.locale === null) || (attribute.is_scopable && slot.channel === null))
}

/** The slot `attribute` is edited in: its locale and channel, or null where it varies by neither. */
export function slotOf(attribute: ShowroomAttribute, slot: EditingSlot): { locale: string | null, scope: string | null } {
  return {
    locale: attribute.is_localizable ? slot.locale : null,
    scope: attribute.is_scopable ? slot.channel : null
  }
}

/** The currencies a price attribute takes: its settings', else those it holds, else USD. */
export function currenciesOf(attribute: ShowroomAttribute, input: ValueInput | undefined): string[] {
  const configured = attribute.settings.currencies

  if (Array.isArray(configured) && configured.length > 0) {
    return configured.filter((currency): currency is string => typeof currency === 'string')
  }

  const held = input && typeof input === 'object' && !Array.isArray(input) ? Object.keys(input).filter(key => key !== 'amount' && key !== 'unit') : []

  return held.length > 0 ? held : ['USD']
}

function asText(data: unknown): string {
  return data === null || data === undefined ? '' : typeof data === 'object' ? JSON.stringify(data) : String(data)
}

/** The inputs of `attribute` for what its slot holds. */
export function inputOf(attribute: ShowroomAttribute, data: unknown): ValueInput {
  switch (attribute.type) {
    case 'boolean':
      return data === true ? '1' : data === false ? '0' : ''
    case 'multiselect':
      return Array.isArray(data) ? data.filter((code): code is string => typeof code === 'string') : []
    case 'metric': {
      const metric = (data ?? {}) as { amount?: unknown, unit?: unknown }
      const unit = typeof attribute.settings.default_unit === 'string' ? attribute.settings.default_unit : ''

      return { amount: asText(metric.amount), unit: metric.unit === undefined || metric.unit === null ? unit : asText(metric.unit) }
    }
    case 'price': {
      const amounts: Record<string, string> = {}

      for (const price of Array.isArray(data) ? data as { amount?: unknown, currency?: unknown }[] : []) {
        if (typeof price.currency === 'string') {
          amounts[price.currency] = asText(price.amount)
        }
      }

      return amounts
    }
    default:
      return asText(data)
  }
}

/** The data `attribute`'s inputs send, as Showroom validates it; null clears the slot. */
export function dataOf(attribute: ShowroomAttribute, input: ValueInput | undefined): unknown {
  if (input === undefined || input === '' || (Array.isArray(input) && input.length === 0)) {
    return null
  }

  switch (attribute.type) {
    case 'boolean':
      return input === '1'
    case 'number':
      return typeof input === 'string' && Number.isFinite(Number(input)) ? Number(input) : input
    case 'multiselect':
      return Array.isArray(input) ? input.filter(code => code !== '') : null
    case 'metric': {
      const metric = input as MetricInput

      return metric.amount === '' ? null : { amount: metric.amount, unit: metric.unit }
    }
    case 'price': {
      const prices = Object.entries(input as Record<string, string>)
        .filter(([, amount]) => amount !== '')
        .map(([currency, amount]) => ({ amount, currency }))

      return prices.length > 0 ? prices : null
    }
    default:
      return input
  }
}

/** The inputs of every attribute the form shows, from the record's own values. */
export function valueInputs(attributes: readonly ShowroomAttribute[], values: CatalogValues | null | undefined, slot: EditingSlot): Record<string, ValueInput> {
  return Object.fromEntries(attributes.map((attribute) => {
    const { locale, scope } = slotOf(attribute, slot)

    return [attribute.code, inputOf(attribute, slotData(values, attribute.code, scope, locale))]
  }))
}

/** The `values` a save sends: one slot per attribute the form showed and the slot allows. */
export function valuesBody(attributes: readonly ShowroomAttribute[], inputs: Record<string, ValueInput>, slot: EditingSlot): CatalogValues {
  const values: CatalogValues = {}

  for (const attribute of attributes) {
    if (slotAvailable(attribute, slot)) {
      values[attribute.code] = [{ ...slotOf(attribute, slot), data: dataOf(attribute, inputs[attribute.code]) }]
    }
  }

  return values
}

/** The axis values a new variant or sub-model is made with, unlocalized and unscoped (v1's `partials/axes`). */
export function axesBody(axes: readonly ShowroomAttribute[], inputs: Record<string, ValueInput>): CatalogValues {
  return Object.fromEntries(axes.map(axis => [axis.code, [{ locale: null, scope: null, data: dataOf(axis, inputs[axis.code]) }]]))
}

/** Empty inputs for the axes of a new record. */
export function axesInputs(axes: readonly ShowroomAttribute[]): Record<string, ValueInput> {
  return Object.fromEntries(axes.map(axis => [axis.code, inputOf(axis, null)]))
}

/**
 * The attributes set at `depth` of a family variant: its common attributes on
 * a root model (0), else that level's attributes, axes left out (they are set
 * when the record is made).
 */
export function levelAttributes(variant: ShowroomFamilyVariant | null | undefined, depth: number): string[] {
  if (!variant) {
    return []
  }

  return depth === 0 ? variant.common_attributes ?? [] : variant.levels?.find(level => level.level === depth)?.attributes ?? []
}

/** The axes a new record at `depth` (1 for a sub-model, the last level for a variant) is told apart by. */
export function levelAxes(variant: ShowroomFamilyVariant | null | undefined, depth: number): string[] {
  return variant?.levels?.find(level => level.level === depth)?.axes ?? []
}

export interface AttributeCatalog {
  /** Every attribute, by code */
  attributes: ShallowRef<Map<string, ShowroomAttribute>>
  /** The attributes of `codes` that exist, in that order, with their options */
  pick: (codes: readonly string[]) => ShowroomAttribute[]
}

async function everyAttribute(client: NonNullable<ReturnType<typeof useShowroom>>): Promise<ShowroomAttribute[]> {
  const attributes: ShowroomAttribute[] = []
  let cursor: string | undefined

  // Ten pages of a hundred: more than any catalog the form can show.
  for (let page = 0; page < 10; page++) {
    const answer = showroomData<CatalogList<ShowroomAttribute>>(await client.GET('/attributes', {
      params: { query: { per_page: ALL, ...(cursor ? { cursor } : {}) } }
    }))

    attributes.push(...answer.data)
    cursor = answer.meta?.next_cursor ?? undefined

    if (!cursor) {
      break
    }
  }

  return attributes
}

/**
 * Showroom's attributes (`attributes.index`), and the options
 * (`attributes.options.index`) of every select and multiselect among
 * `codes()`, loaded again when they change. A failure leaves the form without
 * those attributes.
 */
export function useAttributeCatalog(codes: () => readonly string[]): AttributeCatalog {
  const client = useShowroom()
  const attributes = shallowRef(new Map<string, ShowroomAttribute>())
  const options = shallowRef(new Map<string, ShowroomAttributeOption[]>())
  let loaded: Promise<void> | null = null

  async function loadAttributes(): Promise<void> {
    if (!client) {
      return
    }

    try {
      attributes.value = new Map((await everyAttribute(client)).map(attribute => [attribute.code, attribute]))
    } catch {
      attributes.value = new Map()
    }
  }

  async function loadOptions(wanted: readonly string[]): Promise<void> {
    loaded ??= loadAttributes()
    await loaded

    const missing = wanted.filter((code) => {
      const type = attributes.value.get(code)?.type

      return (type === 'select' || type === 'multiselect') && !options.value.has(code)
    })

    if (!client || missing.length === 0) {
      return
    }

    const next = new Map(options.value)

    await Promise.all(missing.map(async (code) => {
      try {
        next.set(code, showroomData<CatalogList<ShowroomAttributeOption>>(await client.GET('/attributes/{attribute}/options', {
          params: { path: { attribute: code }, query: { per_page: ALL } }
        })).data)
      } catch {
        next.set(code, [])
      }
    }))

    options.value = next
  }

  watch(() => [...codes()], wanted => void loadOptions(wanted), { immediate: true })

  function pick(wanted: readonly string[]): ShowroomAttribute[] {
    return wanted.flatMap((code) => {
      const attribute = attributes.value.get(code)

      return attribute ? [{ ...attribute, options: options.value.get(code) ?? [] }] : []
    })
  }

  return { attributes, pick }
}

export interface EditingSlots {
  locales: ShallowRef<ShowroomLocale[]>
  channels: ShallowRef<ShowroomChannel[]>
  slot: ComputedRef<EditingSlot>
  /** Picks another slot, kept in the page's query */
  pick: (slot: EditingSlot) => void
}

/**
 * The slot the value form edits, picked with `?locale=` and `?channel=` as in
 * v1: the asked one when it exists, else the app's locale (or the first) and
 * the first channel. The locales and channels come from `locales.index` and
 * `channels.index`.
 */
export function useEditingSlot(): EditingSlots {
  const client = useShowroom()
  const route = useRoute()
  const router = useRouter()
  const { locale } = useI18n()
  const locales = shallowRef<ShowroomLocale[]>([])
  const channels = shallowRef<ShowroomChannel[]>([])

  if (client) {
    void client.GET('/locales', { params: { query: { per_page: ALL } } })
      .then((answer) => {
        locales.value = showroomData<CatalogList<ShowroomLocale>>(answer).data
      })
      .catch(() => undefined)
    void client.GET('/channels', { params: { query: { per_page: ALL } } })
      .then((answer) => {
        channels.value = showroomData<CatalogList<ShowroomChannel>>(answer).data
      })
      .catch(() => undefined)
  }

  const slot = computed<EditingSlot>(() => {
    const codes = locales.value.map(entry => entry.code)
    const channelCodes = channels.value.map(entry => entry.code)
    const askedLocale = typeof route.query.locale === 'string' ? route.query.locale : null
    const askedChannel = typeof route.query.channel === 'string' ? route.query.channel : null

    return {
      locale: askedLocale && codes.includes(askedLocale) ? askedLocale : codes.includes(locale.value) ? locale.value : codes[0] ?? null,
      channel: askedChannel && channelCodes.includes(askedChannel) ? askedChannel : channelCodes[0] ?? null
    }
  })

  function pick(next: EditingSlot): void {
    void router.replace({ query: { ...route.query, locale: next.locale ?? undefined, channel: next.channel ?? undefined } })
  }

  return { locales, channels, slot, pick }
}
