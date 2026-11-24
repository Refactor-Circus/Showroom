import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import Page from '../../src/pages/attributes/create.vue'
import { settle } from '@refactor-circus/atrium/testing'
import { mockPackageApi, reply, resetPackageApis, type PackageRoutes } from '@refactor-circus/atrium/testing'
import { cursorPage, invalid, mountShowroomPage, ok, resetShowroomPages, screensApi, showroomCalls } from '../catalog/support'
import { color, looks, specs } from './support'
import { clearAtriumData, mockAtriumApi, useRouter } from '@refactor-circus/atrium/testing'
import { choices, pick } from '../ui'

/*
 * `showroom.attribute-create` (AC 54, 71): v1's new-attribute form. It sends
 * the code, type, group, the label in the current locale, the three switches,
 * the settings (a JSON object) and the sort order to `attributes.store`, then
 * opens the new attribute. Settings that are not a JSON object are refused
 * before sending, as v1 refused them. A 422 shows the field errors, a 403 no
 * access.
 */

const { api } = vi.hoisted(() => ({ api: vi.fn() }))

mockAtriumApi(api)

const PATH = '/showroom/attributes/create'

function showroomWith(overrides: PackageRoutes<'showroom'> = {}): void {
  mockPackageApi('showroom', {
    'GET /attribute-groups': ok(cursorPage([looks, specs])),
    'POST /attributes': ok({ data: { ...color, code: 'material' } }, 201),
    ...overrides
  })
}

async function fill(wrapper: Awaited<ReturnType<typeof mountShowroomPage>>): Promise<void> {
  await wrapper.find('[data-testid="showroom-attribute-create-code"]').setValue('material')
  await pick(wrapper, 'showroom-attribute-create-type', 'select')
}

async function submit(wrapper: Awaited<ReturnType<typeof mountShowroomPage>>): Promise<void> {
  await wrapper.find('[data-testid="showroom-attribute-create-form"]').trigger('submit')
  await settle()
  await settle()
}

describe('showroom.attribute-create', () => {
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

  it('offers every type and the groups, and calls atrium.screens.show with its key', async () => {
    showroomWith()

    const wrapper = await mountShowroomPage(Page, PATH)
    const types = choices(wrapper, 'showroom-attribute-create-type')
    const groups = choices(wrapper, 'showroom-attribute-create-group')

    expect(types).toEqual([null, 'text', 'textarea', 'number', 'decimal', 'boolean', 'date', 'select', 'multiselect', 'price', 'metric'])
    expect(groups).toEqual([null, 'looks', 'specs'])
    expect(api).toHaveBeenCalledWith('/screens/showroom.attribute-create', expect.anything())
  })

  it('creates the attribute with every field, then opens it', async () => {
    showroomWith()

    const wrapper = await mountShowroomPage(Page, PATH)
    await fill(wrapper)
    await pick(wrapper, 'showroom-attribute-create-group', 'looks')
    await wrapper.find('[data-testid="showroom-attribute-create-label"]').setValue('Material')
    await pick(wrapper, 'showroom-attribute-create-flag-is_localizable', true)
    await wrapper.find('[data-testid="showroom-attribute-create-settings"]').setValue('{"max_length": 40}')
    await wrapper.find('[data-testid="showroom-attribute-create-sort"]').setValue('7')
    await submit(wrapper)

    const [call] = showroomCalls('POST', '/attributes')
    expect(call?.path).toBe('/attributes')
    expect(call?.body).toEqual({
      code: 'material',
      type: 'select',
      group: 'looks',
      labels: { en: 'Material' },
      is_unique: false,
      is_localizable: true,
      is_scopable: false,
      settings: { max_length: 40 },
      sort_order: 7
    })
    await vi.waitFor(() => expect(useRouter().currentRoute.value.path).toBe('/showroom/attributes/material'))
  })

  it('sends no group, no labels, empty settings and sort order 0 when left blank', async () => {
    showroomWith()

    const wrapper = await mountShowroomPage(Page, PATH)
    await fill(wrapper)
    await submit(wrapper)

    expect(showroomCalls('POST', '/attributes')[0]?.body).toEqual({
      code: 'material',
      type: 'select',
      group: null,
      is_unique: false,
      is_localizable: false,
      is_scopable: false,
      settings: {},
      sort_order: 0
    })
  })

  it('refuses settings that are not a JSON object, without calling Showroom', async () => {
    showroomWith()

    const wrapper = await mountShowroomPage(Page, PATH)
    await fill(wrapper)
    await wrapper.find('[data-testid="showroom-attribute-create-settings"]').setValue('[1, 2]')
    await submit(wrapper)

    expect(showroomCalls('POST', '/attributes')).toHaveLength(0)
    expect(wrapper.text()).toContain('Settings must be a JSON object.')
  })

  it('shows the field errors of a 422', async () => {
    showroomWith({ 'POST /attributes': invalid({ code: ['The code has already been taken.'] }) })

    const wrapper = await mountShowroomPage(Page, PATH)
    await fill(wrapper)
    await submit(wrapper)

    expect(wrapper.text()).toContain('The code has already been taken.')
    expect(useRouter().currentRoute.value.path).toBe(PATH)
  })

  it('shows the no-access notice when create answers 403', async () => {
    showroomWith({ 'POST /attributes': reply(403) })

    const wrapper = await mountShowroomPage(Page, PATH)
    await fill(wrapper)
    await submit(wrapper)

    expect(wrapper.find('[data-testid="atrium-no-access"]').exists()).toBe(true)
    expect(useRouter().currentRoute.value.path).toBe(PATH)
  })
})
