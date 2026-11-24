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

const localeItems = computed(() => props.locales.map(entry => ({ label: labelOf(entry.labels, locale.value, entry.code), value: entry.code })))
const channelItems = computed(() => props.channels.map(entry => ({ label: labelOf(entry.labels, locale.value, entry.code), value: entry.code })))

function pick(): void {
  emit('pick', { locale: choice.locale || null, channel: choice.channel || null })
}
</script>

<template>
  <UCard v-if="props.locales.length > 0 || props.channels.length > 0">
    <form class="flex flex-wrap items-start gap-3" data-testid="showroom-slot-picker" @submit.prevent="pick">
      <UFormField v-if="props.locales.length > 0" :label="t('showroom.catalog.fields.locale')">
        <USelect v-model="choice.locale" :items="localeItems" class="w-44" data-testid="showroom-slot-locale" />
      </UFormField>
      <UFormField v-if="props.channels.length > 0" :label="t('showroom.catalog.fields.channel')">
        <USelect v-model="choice.channel" :items="channelItems" class="w-44" data-testid="showroom-slot-channel" />
      </UFormField>
      <UButton type="submit" variant="outline" icon="i-lucide-arrow-left-right" class="mt-field-label" data-testid="showroom-slot-switch">{{ t('showroom.catalog.values.switch') }}</UButton>
    </form>
  </UCard>
</template>
