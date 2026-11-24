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

const control = 'w-48 rounded-md border border-default bg-default px-2.5 py-1.5 text-sm'

function text(code: string): string {
  const value = inputs.value[code]

  return typeof value === 'string' ? value : ''
}

function metric(code: string): MetricInput {
  const value = inputs.value[code]

  return value && typeof value === 'object' && 'amount' in value ? value as MetricInput : { amount: '', unit: '' }
}

function valueOf(event: Event): string {
  return (event.target as HTMLInputElement | HTMLSelectElement).value
}

function setText(code: string, event: Event): void {
  inputs.value[code] = valueOf(event)
}

function setMetric(code: string, part: keyof MetricInput, event: Event): void {
  inputs.value[code] = { ...metric(code), [part]: valueOf(event) }
}
</script>

<template>
  <div v-for="axis in props.axes" :key="axis.code" class="flex flex-col gap-1.5" :data-axis="axis.code">
    <label class="text-sm font-medium">{{ labelOf(axis.labels, locale, axis.code) }}</label>
    <select
      v-if="axis.type === 'select'"
      required
      :class="control"
      :value="text(axis.code)"
      :data-testid="`${props.testid}-axis-${axis.code}`"
      @change="setText(axis.code, $event)"
    >
      <option value="" disabled>{{ t('showroom.catalog.none') }}</option>
      <option v-for="option in axis.options ?? []" :key="option.code" :value="option.code">{{ labelOf(option.labels, locale, option.code) }}</option>
    </select>
    <select
      v-else-if="axis.type === 'boolean'"
      required
      :class="control"
      :value="text(axis.code)"
      :data-testid="`${props.testid}-axis-${axis.code}`"
      @change="setText(axis.code, $event)"
    >
      <option value="" disabled>{{ t('showroom.catalog.none') }}</option>
      <option value="1">{{ t('showroom.catalog.yes') }}</option>
      <option value="0">{{ t('showroom.catalog.no') }}</option>
    </select>
    <div v-else-if="axis.type === 'metric'" class="grid grid-cols-2 gap-2">
      <input
        required
        type="number"
        step="any"
        :class="control"
        :aria-label="t('showroom.catalog.values.amount')"
        :value="metric(axis.code).amount"
        :data-testid="`${props.testid}-axis-${axis.code}-amount`"
        @input="setMetric(axis.code, 'amount', $event)"
      >
      <input
        required
        :class="control"
        :aria-label="t('showroom.catalog.values.unit')"
        :value="metric(axis.code).unit"
        :data-testid="`${props.testid}-axis-${axis.code}-unit`"
        @input="setMetric(axis.code, 'unit', $event)"
      >
    </div>
    <input
      v-else
      required
      :class="control"
      :value="text(axis.code)"
      :data-testid="`${props.testid}-axis-${axis.code}`"
      @input="setText(axis.code, $event)"
    >
    <small v-if="props.error(`values.${axis.code}`)" class="text-xs text-error">{{ props.error(`values.${axis.code}`) }}</small>
  </div>
</template>
