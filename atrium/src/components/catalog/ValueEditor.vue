<script setup lang="ts">
import { labelOf, type ShowroomAttribute } from './catalog'
import { currenciesOf, slotAvailable, type EditingSlot, type MetricInput, type ValueInput } from './values'

/**
 * One input per attribute, for the slot the form edits (v1's
 * `partials/values`): the picked locale for localizable attributes and the
 * picked channel for scopable ones. The input fits the attribute's type, and
 * a 422 on `values.{code}` shows under it.
 */
const props = defineProps<{
  attributes: ShowroomAttribute[]
  editing: EditingSlot
  error: (field: string) => string | undefined
  disabled?: boolean
}>()

const inputs = defineModel<Record<string, ValueInput>>({ required: true })

const { t, locale } = useI18n()

const control = 'w-full rounded-md border border-default bg-default px-2.5 py-1.5 text-sm'

function text(code: string): string {
  const value = inputs.value[code]

  return typeof value === 'string' ? value : ''
}

function setText(code: string, value: string): void {
  inputs.value[code] = value
}

function picked(code: string): string[] {
  const value = inputs.value[code]

  return Array.isArray(value) ? value : []
}

function toggle(code: string, option: string, on: boolean): void {
  const current = picked(code).filter(entry => entry !== option)

  inputs.value[code] = on ? [...current, option] : current
}

function metric(code: string): MetricInput {
  const value = inputs.value[code]

  return value && typeof value === 'object' && !Array.isArray(value) && 'amount' in value ? value as MetricInput : { amount: '', unit: '' }
}

function setMetric(code: string, part: keyof MetricInput, value: string): void {
  inputs.value[code] = { ...metric(code), [part]: value }
}

function prices(code: string): Record<string, string> {
  const value = inputs.value[code]

  return value && typeof value === 'object' && !Array.isArray(value) && !('amount' in value) ? value as Record<string, string> : {}
}

function setPrice(code: string, currency: string, value: string): void {
  inputs.value[code] = { ...prices(code), [currency]: value }
}

function valueOf(event: Event): string {
  return (event.target as HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement).value
}

function checkedOf(event: Event): boolean {
  return (event.target as HTMLInputElement).checked
}

function slotNote(attribute: ShowroomAttribute): string {
  return [
    attribute.code,
    attribute.type,
    attribute.is_localizable ? props.editing.locale : null,
    attribute.is_scopable ? props.editing.channel : null
  ].filter(part => part).join(' · ')
}
</script>

<template>
  <p v-if="props.attributes.length === 0" class="atrium-empty" data-testid="showroom-values-empty">{{ t('showroom.catalog.values.none_settable') }}</p>
  <div v-else class="grid gap-4 sm:grid-cols-2" data-testid="showroom-values">
    <div v-for="attribute in props.attributes" :key="attribute.code" class="flex flex-col gap-1.5" :data-attribute="attribute.code">
      <label class="text-sm font-medium" :for="`showroom-value-${attribute.code}`">
        {{ labelOf(attribute.labels, locale, attribute.code) }}
        <span class="font-mono text-xs font-normal text-muted">{{ slotNote(attribute) }}</span>
      </label>

      <p v-if="!slotAvailable(attribute, props.editing)" class="text-xs text-muted">{{ t('showroom.catalog.values.slot_unavailable') }}</p>
      <template v-else>
        <textarea
          v-if="attribute.type === 'textarea'"
          :id="`showroom-value-${attribute.code}`"
          :class="control"
          rows="3"
          :disabled="props.disabled"
          :value="text(attribute.code)"
          :data-testid="`showroom-value-${attribute.code}`"
          @input="setText(attribute.code, valueOf($event))"
        />
        <input
          v-else-if="attribute.type === 'number' || attribute.type === 'decimal'"
          :id="`showroom-value-${attribute.code}`"
          type="number"
          :step="attribute.type === 'number' ? '1' : 'any'"
          :class="control"
          :disabled="props.disabled"
          :value="text(attribute.code)"
          :data-testid="`showroom-value-${attribute.code}`"
          @input="setText(attribute.code, valueOf($event))"
        >
        <input
          v-else-if="attribute.type === 'date'"
          :id="`showroom-value-${attribute.code}`"
          type="date"
          :class="control"
          :disabled="props.disabled"
          :value="text(attribute.code)"
          :data-testid="`showroom-value-${attribute.code}`"
          @input="setText(attribute.code, valueOf($event))"
        >
        <select
          v-else-if="attribute.type === 'boolean'"
          :id="`showroom-value-${attribute.code}`"
          :class="control"
          :disabled="props.disabled"
          :value="text(attribute.code)"
          :data-testid="`showroom-value-${attribute.code}`"
          @change="setText(attribute.code, valueOf($event))"
        >
          <option value="">{{ t('showroom.catalog.none') }}</option>
          <option value="1">{{ t('showroom.catalog.yes') }}</option>
          <option value="0">{{ t('showroom.catalog.no') }}</option>
        </select>
        <select
          v-else-if="attribute.type === 'select'"
          :id="`showroom-value-${attribute.code}`"
          :class="control"
          :disabled="props.disabled"
          :value="text(attribute.code)"
          :data-testid="`showroom-value-${attribute.code}`"
          @change="setText(attribute.code, valueOf($event))"
        >
          <option value="">{{ t('showroom.catalog.none') }}</option>
          <option v-for="option in attribute.options ?? []" :key="option.code" :value="option.code">{{ labelOf(option.labels, locale, option.code) }}</option>
        </select>
        <div v-else-if="attribute.type === 'multiselect'" class="flex flex-wrap gap-3">
          <label v-for="option in attribute.options ?? []" :key="option.code" class="flex items-center gap-1.5 text-sm">
            <input
              type="checkbox"
              :value="option.code"
              :disabled="props.disabled"
              :checked="picked(attribute.code).includes(option.code)"
              :data-testid="`showroom-value-${attribute.code}-${option.code}`"
              @change="toggle(attribute.code, option.code, checkedOf($event))"
            >
            {{ labelOf(option.labels, locale, option.code) }}
          </label>
        </div>
        <div v-else-if="attribute.type === 'metric'" class="grid grid-cols-2 gap-2">
          <input
            :id="`showroom-value-${attribute.code}`"
            type="number"
            step="any"
            :class="control"
            :aria-label="t('showroom.catalog.values.amount')"
            :disabled="props.disabled"
            :value="metric(attribute.code).amount"
            :data-testid="`showroom-value-${attribute.code}-amount`"
            @input="setMetric(attribute.code, 'amount', valueOf($event))"
          >
          <input
            :class="control"
            :aria-label="t('showroom.catalog.values.unit')"
            :disabled="props.disabled"
            :value="metric(attribute.code).unit"
            :data-testid="`showroom-value-${attribute.code}-unit`"
            @input="setMetric(attribute.code, 'unit', valueOf($event))"
          >
        </div>
        <div v-else-if="attribute.type === 'price'" class="flex flex-wrap gap-2">
          <label v-for="currency in currenciesOf(attribute, inputs[attribute.code])" :key="currency" class="flex items-center gap-1.5">
            <span class="font-mono text-xs">{{ currency }}</span>
            <input
              type="number"
              step="any"
              class="w-32 rounded-md border border-default bg-default px-2.5 py-1.5 text-sm"
              :disabled="props.disabled"
              :value="prices(attribute.code)[currency] ?? ''"
              :data-testid="`showroom-value-${attribute.code}-${currency}`"
              @input="setPrice(attribute.code, currency, valueOf($event))"
            >
          </label>
        </div>
        <input
          v-else
          :id="`showroom-value-${attribute.code}`"
          :class="control"
          :disabled="props.disabled"
          :value="text(attribute.code)"
          :data-testid="`showroom-value-${attribute.code}`"
          @input="setText(attribute.code, valueOf($event))"
        >

        <small v-if="props.error(`values.${attribute.code}`)" class="text-xs text-error" :data-testid="`showroom-value-error-${attribute.code}`">{{ props.error(`values.${attribute.code}`) }}</small>
      </template>
    </div>
  </div>
</template>
