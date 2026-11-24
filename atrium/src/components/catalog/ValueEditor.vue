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

/** What a field emitted, as the form keeps it: a string, empty for none. */
function asText(value: unknown): string {
  return value === null || value === undefined ? '' : String(value)
}

/** A select's choices: none first, then the attribute's options or yes and no. */
function choicesOf(attribute: ShowroomAttribute): { label: string, value: string | null }[] {
  const choices = attribute.type === 'boolean'
    ? [{ label: t('showroom.catalog.yes'), value: '1' }, { label: t('showroom.catalog.no'), value: '0' }]
    : (attribute.options ?? []).map(option => ({ label: labelOf(option.labels, locale.value, option.code), value: option.code }))

  return [{ label: t('showroom.catalog.none'), value: null }, ...choices]
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
  <AtriumState v-if="props.attributes.length === 0" compact :title="t('showroom.catalog.values.none_settable')" data-testid="showroom-values-empty" />
  <div v-else class="grid gap-4 sm:grid-cols-2" data-testid="showroom-values">
    <div v-for="attribute in props.attributes" :key="attribute.code" class="flex flex-col gap-1.5" :data-attribute="attribute.code">
      <label class="text-sm font-medium" :for="`showroom-value-${attribute.code}`">
        {{ labelOf(attribute.labels, locale, attribute.code) }}
        <span class="font-mono text-xs font-normal text-muted">{{ slotNote(attribute) }}</span>
      </label>

      <p v-if="!slotAvailable(attribute, props.editing)" class="text-xs text-muted">{{ t('showroom.catalog.values.slot_unavailable') }}</p>
      <template v-else>
        <UTextarea
          v-if="attribute.type === 'textarea'"
          :id="`showroom-value-${attribute.code}`"
          class="w-full"
          :rows="3"
          :disabled="props.disabled"
          :model-value="text(attribute.code)"
          :data-testid="`showroom-value-${attribute.code}`"
          @update:model-value="setText(attribute.code, asText($event))"
        />
        <UInput
          v-else-if="attribute.type === 'number' || attribute.type === 'decimal'"
          :id="`showroom-value-${attribute.code}`"
          type="number"
          :step="attribute.type === 'number' ? '1' : 'any'"
          class="w-full"
          :disabled="props.disabled"
          :model-value="text(attribute.code)"
          :data-testid="`showroom-value-${attribute.code}`"
          @update:model-value="setText(attribute.code, asText($event))"
        />
        <UInput
          v-else-if="attribute.type === 'date'"
          :id="`showroom-value-${attribute.code}`"
          type="date"
          class="w-full"
          :disabled="props.disabled"
          :model-value="text(attribute.code)"
          :data-testid="`showroom-value-${attribute.code}`"
          @update:model-value="setText(attribute.code, asText($event))"
        />
        <USelect
          v-else-if="attribute.type === 'boolean' || attribute.type === 'select'"
          :id="`showroom-value-${attribute.code}`"
          class="w-full"
          :items="choicesOf(attribute)"
          :placeholder="t('showroom.catalog.none')"
          :disabled="props.disabled"
          :model-value="text(attribute.code)"
          :data-testid="`showroom-value-${attribute.code}`"
          @update:model-value="setText(attribute.code, asText($event))"
        />
        <div v-else-if="attribute.type === 'multiselect'" class="flex flex-wrap gap-3">
          <UCheckbox
            v-for="option in attribute.options ?? []"
            :key="option.code"
            :label="labelOf(option.labels, locale, option.code)"
            :disabled="props.disabled"
            :model-value="picked(attribute.code).includes(option.code)"
            :data-testid="`showroom-value-${attribute.code}-${option.code}`"
            @update:model-value="toggle(attribute.code, option.code, $event === true)"
          />
        </div>
        <div v-else-if="attribute.type === 'metric'" class="grid grid-cols-2 gap-2">
          <UInput
            :id="`showroom-value-${attribute.code}`"
            type="number"
            step="any"
            :aria-label="t('showroom.catalog.values.amount')"
            :disabled="props.disabled"
            :model-value="metric(attribute.code).amount"
            :data-testid="`showroom-value-${attribute.code}-amount`"
            @update:model-value="setMetric(attribute.code, 'amount', asText($event))"
          />
          <UInput
            :aria-label="t('showroom.catalog.values.unit')"
            :disabled="props.disabled"
            :model-value="metric(attribute.code).unit"
            :data-testid="`showroom-value-${attribute.code}-unit`"
            @update:model-value="setMetric(attribute.code, 'unit', asText($event))"
          />
        </div>
        <div v-else-if="attribute.type === 'price'" class="flex flex-wrap gap-2">
          <label v-for="currency in currenciesOf(attribute, inputs[attribute.code])" :key="currency" class="flex items-center gap-1.5">
            <span class="font-mono text-xs">{{ currency }}</span>
            <UInput
              type="number"
              step="any"
              class="w-32"
              :disabled="props.disabled"
              :model-value="prices(attribute.code)[currency] ?? ''"
              :data-testid="`showroom-value-${attribute.code}-${currency}`"
              @update:model-value="setPrice(attribute.code, currency, asText($event))"
            />
          </label>
        </div>
        <UInput
          v-else
          :id="`showroom-value-${attribute.code}`"
          class="w-full"
          :disabled="props.disabled"
          :model-value="text(attribute.code)"
          :data-testid="`showroom-value-${attribute.code}`"
          @update:model-value="setText(attribute.code, asText($event))"
        />

        <small v-if="props.error(`values.${attribute.code}`)" class="text-xs text-error" :data-testid="`showroom-value-error-${attribute.code}`">{{ props.error(`values.${attribute.code}`) }}</small>
      </template>
    </div>
  </div>
</template>
