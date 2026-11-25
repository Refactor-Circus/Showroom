<script setup lang="ts">
/**
 * List paging, the way the Showroom endpoint pages: numbered pages for product
 * search (`?page=`, left out for the first) or an opaque cursor for every
 * other list (`?cursor=`). Either goes in the route's query, which the page
 * sends as the endpoint's.
 */
const props = defineProps<{
  testid: string
  current?: number
  last?: number
  next?: string | null
  prev?: string | null
}>()

const { t } = useI18n()
const route = useRoute()
const router = useRouter()

const numbered = computed(() => props.current !== undefined && props.last !== undefined)
const shown = computed(() => numbered.value ? (props.last ?? 1) > 1 : Boolean(props.next || props.prev))

function goPage(page: number): void {
  router.replace({ query: { ...route.query, page: page > 1 ? String(page) : undefined } })
}

function goCursor(cursor: string | null | undefined): void {
  router.replace({ query: { ...route.query, cursor: cursor || undefined } })
}

function previous(): void {
  if (numbered.value) {
    goPage((props.current ?? 1) - 1)
  } else {
    goCursor(props.prev)
  }
}

function following(): void {
  if (numbered.value) {
    goPage((props.current ?? 1) + 1)
  } else {
    goCursor(props.next)
  }
}
</script>

<template>
  <div v-if="shown" class="flex items-center gap-3 text-sm">
    <UButton
      variant="outline"
      size="sm"
      icon="i-lucide-chevron-left"
      :disabled="numbered ? (props.current ?? 1) <= 1 : !props.prev"
      :data-testid="`${testid}-prev`"
      @click="previous"
    >
      {{ t('showroom.catalog.pager.previous') }}
    </UButton>
    <span v-if="numbered" class="text-muted" :data-testid="`${testid}-page`">{{ t('showroom.catalog.pager.page', { current: props.current, last: props.last }) }}</span>
    <UButton
      variant="outline"
      size="sm"
      trailing-icon="i-lucide-chevron-right"
      :disabled="numbered ? (props.current ?? 1) >= (props.last ?? 1) : !props.next"
      :data-testid="`${testid}-next`"
      @click="following"
    >
      {{ t('showroom.catalog.pager.next') }}
    </UButton>
  </div>
</template>
