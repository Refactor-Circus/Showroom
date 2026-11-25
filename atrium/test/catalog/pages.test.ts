import { readdirSync, readFileSync, statSync } from 'node:fs'
import { join, relative } from 'node:path'
import { fileURLToPath } from 'node:url'
import { describe, expect, it } from 'vitest'
import { screens } from '../../src/atrium'
import fixture from '@refactor-circus/atrium/fixtures/integrations/showroom.json'
import { hasI18nKey, i18nKeysIn, localeFilesIn } from '@refactor-circus/atrium/testing'
import { useRouter } from '@refactor-circus/atrium/testing'

/*
 * The products, product models and families area of Showroom (AC 49, 53, 54,
 * 71): each of its screen keys has a layer page at the path the PHP
 * integration declares (`fixtures/integrations/showroom.json`), framed with
 * its own screen key so it calls `atrium.screens.show` with it; the area's
 * index page lists the v1 actions it does not port (D-032); and every `t()`
 * key its pages and catalog components use exists.
 */

/** Each screen of this area to its page file, under `src/pages/`. */
const PAGES: Record<string, string> = {
  'showroom.products': 'products/index.vue',
  'showroom.product-create': 'products/create.vue',
  'showroom.product': 'products/[product].vue',
  'showroom.product-models': 'product-models/index.vue',
  'showroom.product-model': 'product-models/[productModel].vue',
  'showroom.families': 'families/index.vue',
  'showroom.family': 'families/[family].vue',
  'showroom.family-variant': 'family-variants/[familyVariant].vue'
}

// Read through a variable: Vite rewrites `new URL('…', import.meta.url)`
// into a served asset URL, which `fileURLToPath()` refuses.
const here = import.meta.url
const srcRoot = fileURLToPath(new URL('../../src/', here))

function source(file: string): string {
  return readFileSync(join(srcRoot, 'pages', file), 'utf8')
}

function filesUnder(directory: string): string[] {
  return readdirSync(directory).flatMap((entry) => {
    const path = join(directory, entry)

    return statSync(path).isDirectory() ? filesUnder(path) : /\.(vue|ts)$/.test(entry) ? [path] : []
  })
}

describe('the showroom products, product models and families pages', () => {
  it.each(Object.keys(PAGES))('has a page for %s at its declared path, named in the screens map', (key) => {
    const declared = fixture.screens.find(screen => screen.key === key)
    const name = screens[key]
    const route = useRouter().getRoutes().find(candidate => candidate.name === name)

    expect(declared, key).toBeDefined()
    expect(route, `${key} -> ${name}`).toBeDefined()
    expect(route!.path.replace(/\(\)/g, '')).toBe(declared!.path)
  })

  it.each(Object.entries(PAGES))('frames the %s page with its own screen key', (key, file) => {
    expect(source(file)).toContain(`screen-key="${key}"`)
  })

  it('lists the v1 actions it does not port in a Not ported (D-032) comment at the top of the products index page', () => {
    const lines = source('products/index.vue').split('\n')

    expect(lines[0]).toBe('<script setup lang="ts">')
    expect(lines[1]).toBe('// Not ported (D-032)')
  })

  it('has every t() key its pages and catalog components use in its locale files', () => {
    const messages = localeFilesIn(join(srcRoot, 'locales')).map(file => JSON.parse(readFileSync(file, 'utf8')) as unknown)
    const files = [
      ...Object.values(PAGES).map(file => join(srcRoot, 'pages', file)),
      ...filesUnder(join(srcRoot, 'components', 'catalog'))
    ]

    const missing = files.flatMap(file => i18nKeysIn(readFileSync(file, 'utf8'))
      .filter(key => !messages.some(set => hasI18nKey(set, key)))
      .map(key => `${relative(srcRoot, file)}: ${key}`))

    expect(missing).toEqual([])
  })
})
