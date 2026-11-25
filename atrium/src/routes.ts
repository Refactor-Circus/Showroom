import type { RouteRecordRaw } from 'vue-router'

/**
 * Showroom's pages, added to Atrium's router before its first navigation.
 * Names follow Nuxt's file-based ones (`pages/{pkg}/runs/[run].vue` ->
 * `{pkg}-runs-run`), which the screen declarations name.
 */
export const routes: RouteRecordRaw[] = [
  { path: '/showroom/assets/:asset', name: 'showroom-assets-asset', component: () => import('./pages/assets/[asset].vue') },
  { path: '/showroom/assets', name: 'showroom-assets', component: () => import('./pages/assets/index.vue') },
  { path: '/showroom/association-types', name: 'showroom-association-types', component: () => import('./pages/association-types/index.vue') },
  { path: '/showroom/attribute-groups/:group', name: 'showroom-attribute-groups-group', component: () => import('./pages/attribute-groups/[group].vue') },
  { path: '/showroom/attribute-groups', name: 'showroom-attribute-groups', component: () => import('./pages/attribute-groups/index.vue') },
  { path: '/showroom/attributes/:attribute', name: 'showroom-attributes-attribute', component: () => import('./pages/attributes/[attribute].vue') },
  { path: '/showroom/attributes/create', name: 'showroom-attributes-create', component: () => import('./pages/attributes/create.vue') },
  { path: '/showroom/attributes', name: 'showroom-attributes', component: () => import('./pages/attributes/index.vue') },
  { path: '/showroom/categories/:category', name: 'showroom-categories-category', component: () => import('./pages/categories/[category].vue') },
  { path: '/showroom/categories', name: 'showroom-categories', component: () => import('./pages/categories/index.vue') },
  { path: '/showroom/channels/:channel', name: 'showroom-channels-channel', component: () => import('./pages/channels/[channel].vue') },
  { path: '/showroom/channels', name: 'showroom-channels', component: () => import('./pages/channels/index.vue') },
  { path: '/showroom/families/:family', name: 'showroom-families-family', component: () => import('./pages/families/[family].vue') },
  { path: '/showroom/families', name: 'showroom-families', component: () => import('./pages/families/index.vue') },
  { path: '/showroom/family-variants/:familyVariant', name: 'showroom-family-variants-familyVariant', component: () => import('./pages/family-variants/[familyVariant].vue') },
  { path: '/showroom/flags/:flag', name: 'showroom-flags-flag', component: () => import('./pages/flags/[flag].vue') },
  { path: '/showroom/flags', name: 'showroom-flags', component: () => import('./pages/flags/index.vue') },
  { path: '/showroom/owner-types/:ownerType', name: 'showroom-owner-types-ownerType', component: () => import('./pages/owner-types/[ownerType].vue') },
  { path: '/showroom/owner-types', name: 'showroom-owner-types', component: () => import('./pages/owner-types/index.vue') },
  { path: '/showroom/owners/:owner', name: 'showroom-owners-owner', component: () => import('./pages/owners/[owner].vue') },
  { path: '/showroom/owners', name: 'showroom-owners', component: () => import('./pages/owners/index.vue') },
  { path: '/showroom/product-models/:productModel', name: 'showroom-product-models-productModel', component: () => import('./pages/product-models/[productModel].vue') },
  { path: '/showroom/product-models', name: 'showroom-product-models', component: () => import('./pages/product-models/index.vue') },
  { path: '/showroom/products/:product', name: 'showroom-products-product', component: () => import('./pages/products/[product].vue') },
  { path: '/showroom/products/create', name: 'showroom-products-create', component: () => import('./pages/products/create.vue') },
  { path: '/showroom/products', name: 'showroom-products', component: () => import('./pages/products/index.vue') },
  { path: '/showroom/transfers', name: 'showroom-transfers', component: () => import('./pages/transfers/index.vue') }
]
