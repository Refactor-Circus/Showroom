<script setup lang="ts">
import { labelOf, type ShowroomAttribute } from './catalog'
import type { MetricInput, ValueInput } from './values'

/**
 * The axis values a new variant or sub-model needs (v1's `partials/axes`): a
 * choice for a select or boolean axis, an amount and unit for a metric one.
 * They are sent as the new record's values.
 */
const props = defineProps<{ axes: ShowroomAttribute[], testid: string, error: (field: string) => string | undefined }>()

const inputs = defineModel<Record<string, ValueInput>>({ required: true })

const { t, locale } = useI18n()

function text(code: string): string {
  const value = inputs.value[code]

  return typeof value === 'string' ? value : ''
}

function metric(code: string): MetricInput {
  const value = inputs.value[code]

  return value && typeof value === 'object' && 'amount' in value ? value as MetricInput : { amount: '', unit: '' }
}

/** What a field emitted, as the form keeps it: a string, empty for none. */
function asText(value: unknown): string {
  return value === null || value === undefined ? '' : String(value)
}

function setText(code: string, value: unknown): void {
  inputs.value[code] = asText(value)
}

function setMetric(code: string, part: keyof MetricInput, value: unknown): void {
  inputs.value[code] = { ...metric(code), [part]: asText(value) }
}

/** An axis's choices: its options, or yes and no. */
function choicesOf(axis: ShowroomAttribute): { label: string, value: string }[] {
  return axis.type === 'boolean'
    ? [{ label: t('showroom.catalog.yes'), value: '1' }, { label: t('showroom.catalog.no'), value: '0' }]
    : (axis.options ?? []).map(option => ({ label: labelOf(option.labels, locale.value, option.code), value: option.code }))
}
</script>

<template>
  <div v-for="axis in props.axes" :key="axis.code" class="flex flex-col gap-1.5" :data-axis="axis.code">
    <label class="text-sm font-medium">{{ labelOf(axis.labels, locale, axis.code) }}</label>
    <USelect
      v-if="axis.type === 'select' || axis.type === 'boolean'"
      required
      class="w-48"
      :items="choicesOf(axis)"
      :placeholder="t('showroom.catalog.none')"
      :model-value="text(axis.code)"
      :data-testid="`${props.testid}-axis-${axis.code}`"
      @update:model-value="setText(axis.code, $event)"
    />
    <div v-else-if="axis.type === 'metric'" class="grid grid-cols-2 gap-2">
      <UInput
        required
        type="number"
        step="any"
        class="w-48"
        :aria-label="t('showroom.catalog.values.amount')"
        :model-value="metric(axis.code).amount"
        :data-testid="`${props.testid}-axis-${axis.code}-amount`"
        @update:model-value="setMetric(axis.code, 'amount', $event)"
      />
      <UInput
        required
        class="w-48"
        :aria-label="t('showroom.catalog.values.unit')"
        :model-value="metric(axis.code).unit"
        :data-testid="`${props.testid}-axis-${axis.code}-unit`"
        @update:model-value="setMetric(axis.code, 'unit', $event)"
      />
    </div>
    <UInput
      v-else
      required
      class="w-48"
      :model-value="text(axis.code)"
      :data-testid="`${props.testid}-axis-${axis.code}`"
      @update:model-value="setText(axis.code, $event)"
    />
    <small v-if="props.error(`values.${axis.code}`)" class="text-xs text-error">{{ props.error(`values.${axis.code}`) }}</small>
  </div>
</template>
