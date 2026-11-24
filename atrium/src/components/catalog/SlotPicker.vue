<script setup lang="ts">
import { labelOf, type ShowroomChannel, type ShowroomLocale } from './catalog'
import type { EditingSlot } from './values'

/**
 * Picks which locale and channel the value form below edits (v1's
 * `partials/slot-picker`). Shown only when there is a locale or a channel to
 * pick; the pick goes to the page's query.
 */
const props = defineProps<{ locales: ShowroomLocale[], channels: ShowroomChannel[], editing: EditingSlot }>()

const emit = defineEmits<{ pick: [slot: EditingSlot] }>()

const { t, locale } = useI18n()

const choice = reactive<{ locale: string, channel: string }>({ locale: '', channel: '' })

watch(() => props.editing, (editing) => {
  choice.locale = editing.locale ?? ''
  choice.channel = editing.channel ?? ''
}, { immediate: true })

function pick(): void {
  emit('pick', { locale: choice.locale || null, channel: choice.channel || null })
}
</script>

<template>
  <UCard v-if="props.locales.length > 0 || props.channels.length > 0">
    <form class="flex flex-wrap items-end gap-3" data-testid="showroom-slot-picker" @submit.prevent="pick">
      <UFormField v-if="props.locales.length > 0" :label="t('showroom.catalog.fields.locale')">
        <select v-model="choice.locale" class="w-44 rounded-md border border-default bg-default px-2.5 py-1.5 text-sm" data-testid="showroom-slot-locale">
          <option v-for="entry in props.locales" :key="entry.code" :value="entry.code">{{ labelOf(entry.labels, locale, entry.code) }}</option>
        </select>
      </UFormField>
      <UFormField v-if="props.channels.length > 0" :label="t('showroom.catalog.fields.channel')">
        <select v-model="choice.channel" class="w-44 rounded-md border border-default bg-default px-2.5 py-1.5 text-sm" data-testid="showroom-slot-channel">
          <option v-for="entry in props.channels" :key="entry.code" :value="entry.code">{{ labelOf(entry.labels, locale, entry.code) }}</option>
        </select>
      </UFormField>
      <UButton type="submit" variant="outline" icon="i-lucide-arrow-left-right" data-testid="showroom-slot-switch">{{ t('showroom.catalog.values.switch') }}</UButton>
    </form>
  </UCard>
</template>
