// Not ported (D-032), for what this file registers (each screen area's index
// page lists its own):
// - Hiding the nav items, widgets and the search source by policy (D-031):
//   the widgets show their no-access card on Showroom's 403, and the search
//   source is dropped from ⌘K.
// - The recent-changes widget's author names: Showroom's v2 stats name an
//   author by type and id only, so the widget shows the id.

import { searchShowroom } from './components/search/source'
import SettingsPanel from './components/settings/SettingsPanel.vue'
import CompletenessWidget from './components/widgets/CompletenessWidget.vue'
import ProductStatusWidget from './components/widgets/ProductStatusWidget.vue'
import RecentChangesWidget from './components/widgets/RecentChangesWidget.vue'
import ReviewQueueWidget from './components/widgets/ReviewQueueWidget.vue'
import './atrium.css'
import { defineAtriumModule, mergeAtriumMessages } from '@refactor-circus/atrium'
import showroom_assets_en from './locales/showroom-assets.en.json'
import showroom_catalog_en from './locales/showroom-catalog.en.json'
import showroom_structure_en from './locales/showroom-structure.en.json'
import showroom_en from './locales/showroom.en.json'
import { routes } from './routes'

/*
 * Showroom in the layer (AC 60, 61, 62, 71): its four widgets, its settings
 * panel, its ⌘K source and the map of its 27 screens to their pages. The
 * pages live under `app/pages/showroom/`, split by area (products, product
 * models and families; attributes, categories, channels and flags; assets,
 * owners and transfers). Each area's index page lists the v1 actions it does
 * not port (D-032).
 */

/**
 * Each screen key Showroom declares (`ShowroomIntegration::screens()`) to
 * the name of the layer route that renders it: the page at the declared path.
 */
export const screens: Record<string, string> = {
  'showroom.products': 'showroom-products',
  'showroom.product-create': 'showroom-products-create',
  'showroom.product': 'showroom-products-product',
  'showroom.product-models': 'showroom-product-models',
  'showroom.product-model': 'showroom-product-models-productModel',
  'showroom.families': 'showroom-families',
  'showroom.family': 'showroom-families-family',
  'showroom.family-variant': 'showroom-family-variants-familyVariant',
  'showroom.attributes': 'showroom-attributes',
  'showroom.attribute-create': 'showroom-attributes-create',
  'showroom.attribute': 'showroom-attributes-attribute',
  'showroom.attribute-groups': 'showroom-attribute-groups',
  'showroom.attribute-group': 'showroom-attribute-groups-group',
  'showroom.association-types': 'showroom-association-types',
  'showroom.categories': 'showroom-categories',
  'showroom.category': 'showroom-categories-category',
  'showroom.channels': 'showroom-channels',
  'showroom.channel': 'showroom-channels-channel',
  'showroom.owners': 'showroom-owners',
  'showroom.owner': 'showroom-owners-owner',
  'showroom.owner-types': 'showroom-owner-types',
  'showroom.owner-type': 'showroom-owner-types-ownerType',
  'showroom.flags': 'showroom-flags',
  'showroom.flag': 'showroom-flags-flag',
  'showroom.assets': 'showroom-assets',
  'showroom.asset': 'showroom-assets-asset',
  'showroom.transfers': 'showroom-transfers'
}

/**
 * Showroom's Atrium module (D-082): everything above, registered while the
 * showroom integration is active.
 */
export default defineAtriumModule({
  key: 'showroom',
  routes,
  screens,
  widgets: {
    'showroom.product-status': ProductStatusWidget,
    'showroom.completeness': CompletenessWidget,
    'showroom.review-queue': ReviewQueueWidget,
    'showroom.recent-changes': RecentChangesWidget
  },
  settingsPanels: {
    'showroom': SettingsPanel
  },
  searchSources: {
    'showroom': searchShowroom
  },
  messages: { en: mergeAtriumMessages(showroom_assets_en, showroom_catalog_en, showroom_structure_en, showroom_en) }
})
