<script setup lang="ts">
import { fileKind, isImage, type ShowroomAsset } from './assets'

/**
 * An asset tile's thumbnail and caption (v1's `assets/thumbnail`): the image
 * for an image, the file's extension otherwise.
 */
defineProps<{ asset: Pick<ShowroomAsset, 'filename' | 'mime_type' | 'url'>, caption: string }>()
</script>

<template>
  <div class="flex aspect-square items-center justify-center overflow-hidden rounded-md bg-elevated">
    <img v-if="isImage(asset)" :src="asset.url" :alt="caption" loading="lazy" class="size-full object-contain">
    <span v-else class="font-mono text-xs uppercase text-muted">{{ fileKind(asset) }}</span>
  </div>
  <span class="truncate text-xs">{{ caption }}</span>
</template>
