import { describe, expect, it } from 'vitest'
import { useAtrium } from '@refactor-circus/atrium'
import { screens } from '../src/atrium'
import { searchShowroom } from '../src/components/search/source'
import SettingsPanel from '../src/components/settings/SettingsPanel.vue'
import CompletenessWidget from '../src/components/widgets/CompletenessWidget.vue'
import ProductStatusWidget from '../src/components/widgets/ProductStatusWidget.vue'
import RecentChangesWidget from '../src/components/widgets/RecentChangesWidget.vue'
import ReviewQueueWidget from '../src/components/widgets/ReviewQueueWidget.vue'
import { missingI18nKeys } from '@refactor-circus/atrium/testing'
import fixture from '@refactor-circus/atrium/fixtures/integrations/showroom.json'

/*
 * Showroom's `index.ts` (AC 49, 60, 61, 62, 71) against what its PHP
 * integration declares in `fixtures/integrations/showroom.json`: a route for
 * every one of the 27 screens, and a registration for every widget, the
 * settings panel and the search source.
 */

/** The name Nuxt gives the page at a declared path: `/showroom/products/:product` is `showroom-products-product`. */
function routeNameFor(path: string): string {
  return path.slice(1).split('/').map(part => part.replace(/^:/, '')).join('-')
}

describe('the showroom screens map', () => {
  it('has an entry for every screen key in the fixture, and no other', () => {
    const declared = fixture.screens.map(screen => screen.key)

    expect(Object.keys(screens).sort()).toEqual([...declared].sort())
    expect(declared).toHaveLength(27)
  })

  it('names, for each screen, the page at its declared path', () => {
    for (const screen of fixture.screens) {
      expect(screens[screen.key], screen.key).toBe(routeNameFor(screen.path))
    }
  })

  it('keeps every v1 screen key of AC 71, at /showroom/…', () => {
    for (const area of ['product', 'product-model', 'family', 'family-variant', 'attribute', 'attribute-group', 'category', 'channel', 'owner', 'owner-type', 'flag', 'asset']) {
      expect(fixture.screens.some(screen => screen.key === `showroom.${area}`), area).toBe(true)
    }

    for (const screen of fixture.screens) {
      expect(screen.path.startsWith('/showroom/'), screen.key).toBe(true)
    }

    expect(screens).toMatchObject({
      'showroom.products': 'showroom-products',
      'showroom.product-create': 'showroom-products-create',
      'showroom.product': 'showroom-products-product',
      'showroom.transfers': 'showroom-transfers'
    })
  })

  it('sends the transfers screen its flow prefix (D-035, D-042), and no other screen any option', () => {
    for (const screen of fixture.screens) {
      expect(screen.options, screen.key).toEqual(screen.key === 'showroom.transfers' ? { flow_prefix: 'showroom:' } : {})
    }
  })
})

describe('the showroom registrations', () => {
  // The app's registry, which test/setup.ts filled from Showroom's module alone.
  const atrium = useAtrium()

  it('registers a component for every widget the fixture declares', () => {
    expect(fixture.widgets.map(widget => widget.component)).toEqual([
      'showroom.product-status',
      'showroom.completeness',
      'showroom.review-queue',
      'showroom.recent-changes'
    ])

    for (const widget of fixture.widgets) {
      expect(atrium.widgets.has(widget.component), widget.key).toBe(true)
      expect(widget.data, widget.key).toEqual({})
    }

    expect(atrium.widgets.get('showroom.product-status')).toBe(ProductStatusWidget)
    expect(atrium.widgets.get('showroom.completeness')).toBe(CompletenessWidget)
    expect(atrium.widgets.get('showroom.review-queue')).toBe(ReviewQueueWidget)
    expect(atrium.widgets.get('showroom.recent-changes')).toBe(RecentChangesWidget)
  })

  it('registers the settings panel the fixture declares', () => {
    expect(fixture.settings).toMatchObject({ key: 'showroom', component: 'showroom', data: {} })
    expect(atrium.panels.get('showroom')).toBe(SettingsPanel)
  })

  it('registers a resolver for the search source the fixture declares', () => {
    expect(fixture.search_sources.map(source => source.key)).toEqual(['showroom'])
    expect(atrium.searchSources.get('showroom')).toBe(searchShowroom)
  })

  it('registers no extension or live resolver', () => {
    expect(atrium.extensions.size).toBe(0)
    expect(atrium.liveResolvers.size).toBe(0)
  })

  it('declares the eleven nav items with layer paths under /showroom/ and no badge', () => {
    expect(fixture.navigation.map(item => item.link?.path)).toEqual([
      '/showroom/products',
      '/showroom/product-models',
      '/showroom/categories',
      '/showroom/assets',
      '/showroom/owners',
      '/showroom/families',
      '/showroom/attributes',
      '/showroom/channels',
      '/showroom/transfers',
      '/showroom/association-types',
      '/showroom/attribute-groups'
    ])

    for (const item of fixture.navigation) {
      expect(item.badge, item.label).toBeNull()
      expect(item.live, item.label).toBeNull()
    }
  })
})

describe('the showroom strings', () => {
  it('has every t() key it uses in its locale files', () => {
    expect(missingI18nKeys('showroom')).toEqual([])
  })
})
