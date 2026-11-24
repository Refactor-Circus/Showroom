<script setup lang="ts">
import { categoryPath, labelOf } from '../catalog/catalog'
import type { CategoryNode } from './structure'

/**
 * One level of a category branch, recursing into each child's own children
 * (v1's `categories/branch`).
 */
defineProps<{ nodes: readonly CategoryNode[], nested?: boolean }>()

const { locale } = useI18n()
</script>

<template>
  <ul class="flex flex-col gap-1" :class="nested ? 'ml-5 border-l border-default pl-3' : ''">
    <li v-for="node in nodes" :key="node.category.code" :data-testid="`showroom-branch-${node.category.code}`">
      <RouterLink :to="categoryPath(node.category.code)" class="text-sm hover:underline">{{ labelOf(node.category.labels, locale, node.category.code) }}</RouterLink>
      <span class="ml-1 font-mono text-xs text-muted">{{ node.category.code }}</span>
      <CategoryBranch v-if="node.children.length > 0" :nodes="node.children" nested />
    </li>
  </ul>
</template>
