import { describe, expect, it } from 'vitest'
import { useAtrium } from '@refactor-circus/atrium'
import { screens } from '../src/atrium'
import { searchShowroom } from '../src/components/search/source'
import SettingsPanel from '../src/components/settings/SettingsPanel.vue'
import CompletenessWidget from '../src/components/widgets/CompletenessWidget.vue'
import ProductStatusWidget from '../src/components/widgets/ProductStatusWidget.vue'
import RecentChangesWidget from '../src/components/widgets/RecentChangesWidget.vue'
import ReviewQueueWidget from '../src/components/widgets/ReviewQueueWidget.vue'
import { expectIntegrationManifest, missingI18nKeys } from '@refactor-circus/atrium/testing'
import fixture from '@refactor-circus/atrium/fixtures/integrations/showroom.json'

/*
 * Showroom's module (AC 49, 60, 61, 62, 71) against the integration it
 * declares in `src/integration.json` (D-090): a valid manifest, a route for
 * every one of its 27 screens, and a registration for every widget, the
 * settings panel and the search source. Atrium's shell fixture
 * (`fixtures/integrations/showroom.json`) is what Atrium makes of it.
 */

const manifest = expectIntegrationManifest('src/integration.json', 'showroom')
const declaredScreens = manifest.screens ?? []
const declaredWidgets = (manifest.widgets ?? []) as { key: string, component?: string }[]
const declaredSettings = manifest.settings as { key: string, component?: string } | null | undefined
const declaredSources = (manifest.search_sources ?? []) as { key: string }[]
const declaredNavigation = (manifest.navigation ?? []) as { label: string, path: string }[]

/** The name Nuxt gives the page at a declared path: `/showroom/products/:product` is `showroom-products-product`. */
function routeNameFor(path: string): string {
  return path.slice(1).split('/').map(part => part.replace(/^:/, '')).join('-')
}

describe('the showroom integration manifest', () => {
  it('is valid, under the showroom key, at version 1', () => {
    expect(manifest).toMatchObject({ version: 1, key: 'showroom', label: 'Showroom', features: ['showroom.atrium'] })
  })

  it('declares no hooks on other packages\' screens', () => {
    expect(manifest.hooks ?? []).toEqual([])
  })

  it('is what Atrium\'s shell fixture shows: the same screens, widgets and nav paths', () => {
    expect(fixture.screens.map(screen => screen.key)).toEqual(declaredScreens.map(screen => screen.key))
    expect(fixture.widgets.map(widget => widget.key)).toEqual(declaredWidgets.map(widget => widget.key))
    expect(fixture.navigation.map(item => item.link?.path)).toEqual(declaredNavigation.map(item => item.path))
  })
})

describe('the showroom screens map', () => {
  it('has an entry for every screen key in the manifest, and no other', () => {
    const declared = declaredScreens.map(screen => screen.key)

    expect(Object.keys(screens).sort()).toEqual([...declared].sort())
    expect(declared).toHaveLength(27)
  })

  it('names, for each screen, the page at its declared path', () => {
    for (const screen of declaredScreens) {
      expect(screens[screen.key], screen.key).toBe(routeNameFor(screen.path))
    }
  })

  it('keeps every v1 screen key of AC 71, at /showroom/…', () => {
    for (const area of ['product', 'product-model', 'family', 'family-variant', 'attribute', 'attribute-group', 'category', 'channel', 'owner', 'owner-type', 'flag', 'asset']) {
      expect(declaredScreens.some(screen => screen.key === `showroom.${area}`), area).toBe(true)
    }

    for (const screen of declaredScreens) {
      expect(screen.path.startsWith('/showroom/'), screen.key).toBe(true)
    }

    expect(screens).toMatchObject({
      'showroom.products': 'showroom-products',
      'showroom.product-create': 'showroom-products-create',
      'showroom.product': 'showroom-products-product',
      'showroom.transfers': 'showroom-transfers'
    })
  })

  it('sends the transfers screen its flow prefix from config (D-035, D-042), and no other screen any option', () => {
    for (const screen of declaredScreens) {
      expect(screen.options, screen.key).toEqual(screen.key === 'showroom.transfers'
        ? { flow_prefix: { $config: 'atrium.integrations.showroom.transfers.flow_prefix', type: 'string', default: 'showroom:' } }
        : undefined)
    }

    for (const screen of fixture.screens) {
      expect(screen.options, screen.key).toEqual(screen.key === 'showroom.transfers' ? { flow_prefix: 'showroom:' } : {})
    }
  })
})

describe('the showroom registrations', () => {
  // The app's registry, which test/setup.ts filled from Showroom's module alone.
  const atrium = useAtrium()

  it('registers a component for every widget the manifest declares', () => {
    expect(declaredWidgets.map(widget => widget.component ?? widget.key)).toEqual([
      'showroom.product-status',
      'showroom.completeness',
      'showroom.review-queue',
      'showroom.recent-changes'
    ])

    for (const widget of declaredWidgets) {
      expect(atrium.widgets.has(widget.component ?? widget.key), widget.key).toBe(true)
    }

    expect(atrium.widgets.get('showroom.product-status')).toBe(ProductStatusWidget)
    expect(atrium.widgets.get('showroom.completeness')).toBe(CompletenessWidget)
    expect(atrium.widgets.get('showroom.review-queue')).toBe(ReviewQueueWidget)
    expect(atrium.widgets.get('showroom.recent-changes')).toBe(RecentChangesWidget)
  })

  it('registers the settings panel the manifest declares', () => {
    expect(declaredSettings?.key).toBe('showroom')
    expect(atrium.panels.get(declaredSettings?.component ?? declaredSettings?.key ?? '')).toBe(SettingsPanel)
  })

  it('registers a resolver for the search source the manifest declares', () => {
    expect(declaredSources.map(source => source.key)).toEqual(['showroom'])
    expect(atrium.searchSources.get('showroom')).toBe(searchShowroom)
  })

  it('registers no extension or live resolver', () => {
    expect(atrium.extensions.size).toBe(0)
    expect(atrium.liveResolvers.size).toBe(0)
  })

  it('declares the eleven nav items with layer paths under /showroom/ and no badge', () => {
    expect(declaredNavigation.map(item => item.path)).toEqual([
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
