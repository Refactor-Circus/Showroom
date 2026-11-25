import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import Page from '../../src/pages/categories/[category].vue'
import { settle } from '@refactor-circus/atrium/testing'
import { mockPackageApi, reply, resetPackageApis, type PackageCall, type PackageRoutes } from '@refactor-circus/atrium/testing'
import { confirmWith, cursorPage, invalid, mountShowroomPage, ok, resetShowroomPages, screensApi, showroomCalls } from '../catalog/support'
import { master, oxfords, shirts, tops } from './support'
import { clearAtriumData, mockAtriumApi, useRouter } from '@refactor-circus/atrium/testing'

/*
 * `showroom.category` (AC 54, 71): one category (`categories.show`), its chain
 * from the tree root (each ancestor loaded in turn, since `categories.show`
 * names only the parent), and its whole branch (`categories.index?under=`,
 * every page), drawn as a tree. A child is added under it (`categories.store`
 * with `parent`); a save (`categories.update`) sends the labels, the sort
 * order and the parent, which moves the branch or, emptied, makes it a tree of
 * its own. Delete is confirmed. A 422 shows the field errors, a 403 no access
 * and a 404 not found.
 */

const { api } = vi.hoisted(() => ({ api: vi.fn() }))

mockAtriumApi(api)

const PATH = '/showroom/categories/tops'

const BY_CODE: Record<string, unknown> = { master, tops }

function showroomWith(overrides: PackageRoutes<'showroom'> = {}): void {
  mockPackageApi('showroom', {
    'GET /categories/{category}': (call: PackageCall) => {
      const found = BY_CODE[call.path.split('/')[2] ?? '']

      return found ? ok({ data: found }) : reply(404, { message: 'Not found.' })
    },
    'GET /categories': ok(cursorPage([shirts, oxfords])),
    'POST /categories': ok({ data: shirts }, 201),
    'PATCH /categories/{category}': ok({ data: tops }),
    'DELETE /categories/{category}': reply(204),
    ...overrides
  })
}

type Wrapper = Awaited<ReturnType<typeof mountShowroomPage>>

async function submit(wrapper: Wrapper, form: string): Promise<void> {
  await wrapper.find(`[data-testid="${form}"]`).trigger('submit')
  await settle()
  await settle()
}

describe('showroom.category', () => {
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

  it('loads the category, its chain and its branch, and calls atrium.screens.show with its key and record', async () => {
    showroomWith()

    const wrapper = await mountShowroomPage(Page, PATH)

    expect(showroomCalls('GET', '/categories/{category}').map(call => call.path)).toEqual(['/categories/tops', '/categories/master'])
    expect(showroomCalls('GET', '/categories')[0]?.query).toEqual({ under: 'tops', per_page: '100' })
    expect(api).toHaveBeenCalledWith('/screens/showroom.category', expect.objectContaining({ query: expect.objectContaining({ record: 'tops' }) }))
    expect(wrapper.find('[data-testid="showroom-category-title"] h1').text()).toBe('Tops')

    const chain = wrapper.find('[data-testid="showroom-category-chain"]')
    expect(chain.findAll('a').map(link => link.attributes('href'))).toEqual(['/showroom/categories', '/showroom/categories/master'])
    expect(chain.text()).toContain('Master')
    expect(chain.text()).toContain('Tops')
  })

  it('draws the branch as a tree, each child under its parent', async () => {
    showroomWith()

    const wrapper = await mountShowroomPage(Page, PATH)
    const branch = wrapper.find('[data-testid="showroom-branch-shirts"]')

    expect(branch.find('a').attributes('href')).toBe('/showroom/categories/shirts')
    expect(branch.find('[data-testid="showroom-branch-oxfords"]').text()).toContain('Oxfords')
  })

  it('follows the branch\'s cursor until every page is loaded', async () => {
    showroomWith({
      'GET /categories': (call: PackageCall) => call.query.cursor === 'page-2'
        ? ok(cursorPage([oxfords]))
        : ok(cursorPage([shirts], 'page-2'))
    })

    const wrapper = await mountShowroomPage(Page, PATH)

    expect(showroomCalls('GET', '/categories').map(call => call.query)).toEqual([
      { under: 'tops', per_page: '100' },
      { under: 'tops', per_page: '100', cursor: 'page-2' }
    ])
    expect(wrapper.find('[data-testid="showroom-branch-shirts"] [data-testid="showroom-branch-oxfords"]').exists()).toBe(true)
  })

  it('shows the empty state for a category with nothing beneath it', async () => {
    showroomWith({ 'GET /categories': ok(cursorPage([])) })

    const wrapper = await mountShowroomPage(Page, PATH)

    expect(wrapper.find('[data-testid="showroom-branch-empty"]').exists()).toBe(true)
  })

  it('links to the products filed in it, and counts what is filed here', async () => {
    showroomWith()

    const wrapper = await mountShowroomPage(Page, PATH)

    expect(wrapper.find('[data-testid="showroom-category-products"]').attributes('href')).toBe('/showroom/products?category=tops')
    expect(wrapper.find('[data-testid="showroom-category-counts"]').text()).toContain('3 product(s) and 1 product model(s)')
  })

  it('adds a child with its code, label and sort order under this category, then reloads', async () => {
    showroomWith()

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-child-create-code"]').setValue('blouses')
    await wrapper.find('[data-testid="showroom-child-create-label"]').setValue('Blouses')
    await wrapper.find('[data-testid="showroom-child-create-sort"]').setValue('3')
    await submit(wrapper, 'showroom-child-create-form')

    const [call] = showroomCalls('POST', '/categories')
    expect(call?.path).toBe('/categories')
    expect(call?.body).toEqual({ code: 'blouses', parent: 'tops', labels: { en: 'Blouses' }, sort_order: 3 })
    expect(showroomCalls('GET', '/categories/{category}').filter(entry => entry.path === '/categories/tops')).toHaveLength(2)
  })

  it('shows the field errors of a 422 on a new child', async () => {
    showroomWith({ 'POST /categories': invalid({ code: ['The code has already been taken.'] }) })

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-child-create-code"]').setValue('shirts')
    await submit(wrapper, 'showroom-child-create-form')

    expect(wrapper.text()).toContain('The code has already been taken.')
  })

  it('saves the labels, sort order and parent, then reloads', async () => {
    showroomWith()

    const wrapper = await mountShowroomPage(Page, PATH)
    expect((wrapper.find('[data-testid="showroom-category-parent"]').element as HTMLInputElement).value).toBe('master')

    await wrapper.find('[data-testid="showroom-category-label"]').setValue('Upper wear')
    await wrapper.find('[data-testid="showroom-category-sort"]').setValue('6')
    await wrapper.find('[data-testid="showroom-category-parent"]').setValue('web_tree')
    await submit(wrapper, 'showroom-category-form')

    const [call] = showroomCalls('PATCH', '/categories/{category}')
    expect(call?.path).toBe('/categories/tops')
    expect(call?.body).toEqual({ labels: { en: 'Upper wear', fr: 'Hauts' }, sort_order: 6, parent: 'web_tree' })
  })

  it('makes it a tree of its own when the parent is emptied', async () => {
    showroomWith()

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-category-parent"]').setValue('')
    await submit(wrapper, 'showroom-category-form')

    expect(showroomCalls('PATCH', '/categories/{category}')[0]?.body).toMatchObject({ parent: null })
  })

  it('shows the field errors of a 422 on save', async () => {
    showroomWith({ 'PATCH /categories/{category}': invalid({ parent: ['A category cannot move beneath itself.'] }) })

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-category-parent"]').setValue('shirts')
    await submit(wrapper, 'showroom-category-form')

    expect(wrapper.text()).toContain('A category cannot move beneath itself.')
  })

  it('deletes the category once confirmed, then returns to the trees', async () => {
    showroomWith()
    confirmWith(true)

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-category-delete"]').trigger('click')
    await settle()

    const [call] = showroomCalls('DELETE', '/categories/{category}')
    expect(call?.path).toBe('/categories/tops')
    expect(call?.body).toBeUndefined()
    await vi.waitFor(() => expect(useRouter().currentRoute.value.path).toBe('/showroom/categories'))
  })

  it('does not delete when the confirmation is declined', async () => {
    showroomWith()
    confirmWith(false)

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-category-delete"]').trigger('click')
    await settle()

    expect(showroomCalls('DELETE', '/categories/{category}')).toHaveLength(0)
  })

  it('shows the no-access notice when an action answers 403', async () => {
    showroomWith({ 'PATCH /categories/{category}': reply(403) })

    const wrapper = await mountShowroomPage(Page, PATH)
    await submit(wrapper, 'showroom-category-form')

    expect(wrapper.find('[data-testid="atrium-no-access"]').exists()).toBe(true)
  })

  it('shows the no-access state on a 403', async () => {
    showroomWith({ 'GET /categories/{category}': reply(403) })

    const wrapper = await mountShowroomPage(Page, PATH)

    expect(wrapper.find('[data-testid="atrium-no-access"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="showroom-category-form"]').exists()).toBe(false)
  })

  it('shows the not-found state on a 404', async () => {
    showroomWith()

    const wrapper = await mountShowroomPage(Page, '/showroom/categories/missing')

    expect(wrapper.find('[data-testid="showroom-not-found"]').exists()).toBe(true)
  })
})
