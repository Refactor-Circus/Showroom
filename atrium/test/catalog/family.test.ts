import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import Page from '../../src/pages/families/[family].vue'
import { settle } from '@refactor-circus/atrium/testing'
import { mockPackageApi, reply, resetPackageApis, type PackageRoutes } from '@refactor-circus/atrium/testing'
import { bySize, confirmWith, cursorPage, invalid, lookups, mountShowroomPage, ok, resetShowroomPages, screensApi, shirts, showroomCalls } from './support'
import { clearAtriumData, mockAtriumApi, useRouter } from '@refactor-circus/atrium/testing'

/*
 * `showroom.family` (AC 54, 71): one family (`families.show`) and its variants
 * (`family-variants.index?family=`). One save (`families.update`) sends the
 * label in the current locale, the label attribute, the sort order and the
 * whole membership: every row kept, plus one attribute to add. A new family
 * variant (`family-variants.store`) takes its levels' axes and attributes.
 * Delete is confirmed. A 422 shows the field errors, a 403 no access and a
 * 404 not found.
 */

const { api } = vi.hoisted(() => ({ api: vi.fn() }))

mockAtriumApi(api)

const PATH = '/showroom/families/shirts'

function showroomWith(overrides: PackageRoutes<'showroom'> = {}): void {
  mockPackageApi('showroom', {
    ...lookups(),
    'GET /families/{family}': ok({ data: shirts }),
    'GET /family-variants': ok(cursorPage([bySize])),
    'PATCH /families/{family}': ok({ data: shirts }),
    ...overrides
  })
}

async function submit(wrapper: Awaited<ReturnType<typeof mountShowroomPage>>): Promise<void> {
  await wrapper.find('[data-testid="showroom-family-form"]').trigger('submit')
  await settle()
  await settle()
}

const MEMBERS = [
  { attribute: 'name', is_required: true, required_channels: null, sort_order: 0 },
  { attribute: 'color', is_required: false, required_channels: ['web'], sort_order: 1 }
]

describe('showroom.family', () => {
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

  it('loads the family and its variants, and calls atrium.screens.show with its key and record', async () => {
    showroomWith()

    const wrapper = await mountShowroomPage(Page, PATH)

    expect(showroomCalls('GET', '/families/{family}')[0]?.path).toBe('/families/shirts')
    expect(showroomCalls('GET', '/family-variants').find(call => call.query.family === 'shirts')?.query).toEqual({ family: 'shirts', per_page: '100' })
    expect(api).toHaveBeenCalledWith('/screens/showroom.family', expect.objectContaining({ query: expect.objectContaining({ record: 'shirts' }) }))
    expect(wrapper.find('[data-testid="showroom-family-title"]').text()).toBe('Shirts')
    expect(wrapper.find('[data-testid="showroom-family-attribute-color"]').text()).toContain('looks')
    expect(wrapper.find('[data-testid="showroom-family-variant-shirts_by_size"]').attributes('href')).toBe('/showroom/family-variants/shirts_by_size')
  })

  it('saves the label, label attribute, sort order and the whole membership, then reloads', async () => {
    showroomWith()

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-family-label"]').setValue('Shirts and tops')
    await wrapper.find('[data-testid="showroom-family-sort"]').setValue('5')
    await wrapper.find('[data-testid="showroom-family-required-color"]').setValue(true)
    await wrapper.find('[data-testid="showroom-family-channels-name"]').setValue('web, print')
    await submit(wrapper)

    const [call] = showroomCalls('PATCH', '/families/{family}')
    expect(call?.path).toBe('/families/shirts')
    expect(call?.body).toEqual({
      labels: { en: 'Shirts and tops' },
      label_attribute: 'name',
      sort_order: 5,
      attributes: [
        { attribute: 'name', is_required: true, required_channels: ['web', 'print'], sort_order: 0 },
        { attribute: 'color', is_required: true, required_channels: ['web'], sort_order: 1 }
      ]
    })
    expect(showroomCalls('GET', '/families/{family}')).toHaveLength(2)
  })

  it('adds an attribute and removes a member in the same save', async () => {
    showroomWith()

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-family-remove-color"]').setValue(true)
    await wrapper.find('[data-testid="showroom-family-add-attribute"]').setValue('weight')
    await wrapper.find('[data-testid="showroom-family-add-required"]').setValue(true)
    await submit(wrapper)

    expect(showroomCalls('PATCH', '/families/{family}')[0]?.body).toMatchObject({
      attributes: [MEMBERS[0], { attribute: 'weight', is_required: true, sort_order: 1 }]
    })
  })

  it('shows the field errors of a 422 on save', async () => {
    showroomWith({ 'PATCH /families/{family}': invalid({ 'label_attribute': ['The label attribute must be a text attribute.'], 'attributes.0.attribute': ['The attribute is unknown.'] }) })

    const wrapper = await mountShowroomPage(Page, PATH)
    await submit(wrapper)

    expect(wrapper.text()).toContain('The label attribute must be a text attribute.')
    expect(wrapper.find('[data-testid="showroom-family-attributes"]').text()).toContain('The attribute is unknown.')
  })

  it('creates a family variant with its levels, leaving an empty second level out, then reloads', async () => {
    showroomWith({ 'POST /family-variants': ok({ data: bySize }, 201) })

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-family-variant-code"]').setValue('shirts_by_color')
    await wrapper.find('[data-testid="showroom-level-0-axes-color"]').setValue(true)
    await wrapper.find('[data-testid="showroom-level-0-attributes-name"]').setValue(true)
    await wrapper.find('[data-testid="showroom-family-variant-create-form"]').trigger('submit')
    await settle()

    const [call] = showroomCalls('POST', '/family-variants')
    expect(call?.path).toBe('/family-variants')
    expect(call?.body).toEqual({ code: 'shirts_by_color', family: 'shirts', levels: [{ axes: ['color'], attributes: ['name'] }] })
    expect(showroomCalls('GET', '/families/{family}')).toHaveLength(2)
  })

  it('shows the field errors of a 422 on a new variant', async () => {
    showroomWith({ 'POST /family-variants': invalid({ 'levels.0.axes': ['An axis must be a select, boolean or metric attribute.'] }) })

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-family-variant-code"]').setValue('shirts_by_name')
    await wrapper.find('[data-testid="showroom-level-0-axes-name"]').setValue(true)
    await wrapper.find('[data-testid="showroom-family-variant-create-form"]').trigger('submit')
    await settle()

    expect(wrapper.find('[data-testid="showroom-family-variants"]').text()).toContain('An axis must be a select, boolean or metric attribute.')
  })

  it('deletes the family once confirmed, then returns to the list', async () => {
    showroomWith({ 'DELETE /families/{family}': reply(204) })
    confirmWith(true)

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-family-delete"]').trigger('click')
    await settle()

    const [call] = showroomCalls('DELETE', '/families/{family}')
    expect(call?.path).toBe('/families/shirts')
    expect(call?.body).toBeUndefined()
    await vi.waitFor(() => expect(useRouter().currentRoute.value.path).toBe('/showroom/families'))
  })

  it('does not delete when the confirmation is declined', async () => {
    showroomWith({ 'DELETE /families/{family}': reply(204) })
    confirmWith(false)

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-family-delete"]').trigger('click')
    await settle()

    expect(showroomCalls('DELETE', '/families/{family}')).toHaveLength(0)
  })

  it('shows the no-access notice when an action answers 403', async () => {
    showroomWith({ 'DELETE /families/{family}': reply(403) })
    confirmWith(true)

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-family-delete"]').trigger('click')
    await settle()

    expect(wrapper.find('[data-testid="atrium-no-access"]').exists()).toBe(true)
    expect(useRouter().currentRoute.value.path).toBe(PATH)
  })

  it('shows the no-access state on a 403', async () => {
    showroomWith({ 'GET /families/{family}': reply(403) })

    const wrapper = await mountShowroomPage(Page, PATH)

    expect(wrapper.find('[data-testid="atrium-no-access"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="showroom-family-form"]').exists()).toBe(false)
  })

  it('shows the not-found state on a 404', async () => {
    showroomWith({ 'GET /families/{family}': reply(404) })

    const wrapper = await mountShowroomPage(Page, PATH)

    expect(wrapper.find('[data-testid="showroom-not-found"]').exists()).toBe(true)
  })
})
