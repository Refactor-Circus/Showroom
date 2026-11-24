<script setup lang="ts">
import type { CatalogAction } from '../catalog/catalog'
import { FLAG_SUBJECTS, type FlagForm } from './structure'

/**
 * The fields of a flag, shared by the create and edit forms (v1's
 * `flags/fields`): what it applies to and its sort order. Unticking every
 * subject is sent as an empty list, which Showroom refuses with a 422.
 */
const form = defineModel<FlagForm>({ required: true })

defineProps<{ action: CatalogAction, testid: string }>()

const { t } = useI18n()

function toggle(subject: string, event: Event): void {
  const on = (event.target as HTMLInputElement).checked

  form.value.applies_to = on
    ? FLAG_SUBJECTS.filter(entry => entry === subject || form.value.applies_to.includes(entry))
    : form.value.applies_to.filter(entry => entry !== subject)
}
</script>

<template>
  <div class="flex flex-wrap items-start gap-3">
    <div>
      <p class="mb-1 text-xs font-medium uppercase text-muted">{{ t('showroom.structure.flags.applies_to') }}</p>
      <div class="flex flex-wrap gap-3">
        <label v-for="subject in FLAG_SUBJECTS" :key="subject" class="flex items-center gap-1.5 text-sm">
          <input
            type="checkbox"
            :checked="form.applies_to.includes(subject)"
            :data-testid="`${testid}-applies-${subject}`"
            @change="toggle(subject, $event)"
          >
          {{ t(`showroom.structure.flags.subject_${subject}`) }}
        </label>
      </div>
      <small v-if="action.error('applies_to')" class="text-xs text-error">{{ action.error('applies_to') }}</small>
    </div>

    <UFormField :label="t('showroom.catalog.fields.sort_order')" :error="action.error('sort_order')">
      <UInput v-model="form.sort_order" type="number" min="0" class="w-32" :data-testid="`${testid}-sort`" />
    </UFormField>
  </div>
</template>
