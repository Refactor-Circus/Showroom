import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import Page from '../../src/pages/categories/index.vue'
import { settle } from '@refactor-circus/atrium/testing'
import { mockPackageApi, reply, resetPackageApis, type PackageRoutes } from '@refactor-circus/atrium/testing'
import { cursorPage, invalid, mountShowroomPage, ok, resetShowroomPages, screensApi, showroomCalls } from '../catalog/support'
import { master, web } from './support'
import { clearAtriumData, mockAtriumApi, useRouter } from '@refactor-circus/atrium/testing'
import { tableRow } from '@refactor-circus/atrium/testing'

/*
 * `showroom.categories` (AC 54, 71): the category trees, each root category a
 * tree (`categories.index?roots=1`), a cursor page at a time, with how many
 * children each root has. A new tree is created with its code and its label
 * in the current locale (`categories.store`, no parent). A 422 shows the
 * field errors, a 403 no access.
 */

const { api } = vi.hoisted(() => ({ api: vi.fn() }))

mockAtriumApi(api)

const PATH = '/showroom/categories'

function showroomWith(overrides: PackageRoutes<'showroom'> = {}): void {
  mockPackageApi('showroom', {
    'GET /categories': ok(cursorPage([master, web], 'next-cursor')),
    'POST /categories': ok({ data: master }, 201),
    ...overrides
  })
}

type Wrapper = Awaited<ReturnType<typeof mountShowroomPage>>

async function submit(wrapper: Wrapper): Promise<void> {
  await wrapper.find('[data-testid="showroom-tree-create-form"]').trigger('submit')
  await settle()
  await settle()
}

describe('showroom.categories', () => {
  beforeEach(() => {
    clearAtriumData()
    api.mockReset()
    api.mockImplementation(screensApi)
  })

  afterEach(async () => {
    resetShowroomPages()
    resetPackageApis()
    vi.restoreAllMocks()
    await useRouter().replace('/')
  })

  it('loads the tree roots and calls atrium.screens.show with its key', async () => {
    showroomWith()

    const wrapper = await mountShowroomPage(Page, PATH)

    expect(showroomCalls('GET', '/categories')[0]?.query).toEqual({ roots: '1' })
    expect(api).toHaveBeenCalledWith('/screens/showroom.categories', expect.anything())

    const row = tableRow(wrapper, 'showroom-category-row-master')
    expect(row.find('a').attributes('href')).toBe('/showroom/categories/master')
    expect(row.text()).toContain('Master')
    expect(row.text()).toContain('2')
  })

  it('pages with the endpoint\'s cursor', async () => {
    showroomWith()

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-categories-next"]').trigger('click')
    await settle()
    await settle()

    expect(showroomCalls('GET', '/categories').at(-1)?.query).toEqual({ roots: '1', cursor: 'next-cursor' })
  })

  it('creates a tree with its code and label, then reloads', async () => {
    showroomWith()

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-tree-create-code"]').setValue('outlet')
    await wrapper.find('[data-testid="showroom-tree-create-label"]').setValue('Outlet')
    await submit(wrapper)

    const [call] = showroomCalls('POST', '/categories')
    expect(call?.path).toBe('/categories')
    expect(call?.body).toEqual({ code: 'outlet', labels: { en: 'Outlet' } })
    expect(showroomCalls('GET', '/categories')).toHaveLength(2)
  })

  it('shows the field errors of a 422 on create', async () => {
    showroomWith({ 'POST /categories': invalid({ code: ['The code has already been taken.'] }) })

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-tree-create-code"]').setValue('master')
    await submit(wrapper)

    expect(wrapper.text()).toContain('The code has already been taken.')
  })

  it('shows the no-access notice when create answers 403', async () => {
    showroomWith({ 'POST /categories': reply(403) })

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-tree-create-code"]').setValue('outlet')
    await submit(wrapper)

    expect(wrapper.find('[data-testid="atrium-no-access"]').exists()).toBe(true)
  })

  it('shows the empty state when there is no tree', async () => {
    showroomWith({ 'GET /categories': ok(cursorPage([])) })

    const wrapper = await mountShowroomPage(Page, PATH)

    expect(wrapper.find('[data-testid="showroom-categories-empty"]').exists()).toBe(true)
  })

  it('shows the no-access state on a 403', async () => {
    showroomWith({ 'GET /categories': reply(403) })

    const wrapper = await mountShowroomPage(Page, PATH)

    expect(wrapper.find('[data-testid="atrium-no-access"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="showroom-categories-table"]').exists()).toBe(false)
  })
})
