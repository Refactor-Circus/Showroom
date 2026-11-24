import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import Page from '../../src/pages/attributes/[attribute].vue'
import { settle } from '@refactor-circus/atrium/testing'
import { mockPackageApi, reply, resetPackageApis, type PackageRoutes } from '@refactor-circus/atrium/testing'
import { confirmWith, cursorPage, invalid, mountShowroomPage, ok, resetShowroomPages, screensApi, showroomCalls } from '../catalog/support'
import { color, looks, name, specs } from './support'
import { clearAtriumData, mockAtriumApi, useRouter } from '@refactor-circus/atrium/testing'
import { chooseOption, fieldValue, setField, tableRow } from '@refactor-circus/atrium/testing'

/*
 * `showroom.attribute` (AC 54, 71): one attribute (`attributes.show`, with its
 * options when it is a select or multiselect). One save (`attributes.update`)
 * sends the group, the labels (the current locale's edited, the others kept),
 * the three switches, the settings and the sort order. Options are added
 * (`attributes.options.store`) and deleted once confirmed
 * (`attributes.options.destroy`); the attribute is deleted once confirmed. A
 * 422 shows the field errors, a 403 no access and a 404 not found.
 */

const { api } = vi.hoisted(() => ({ api: vi.fn() }))

mockAtriumApi(api)

const PATH = '/showroom/attributes/color'

function showroomWith(overrides: PackageRoutes<'showroom'> = {}): void {
  mockPackageApi('showroom', {
    'GET /attributes/{attribute}': ok({ data: color }),
    'GET /attribute-groups': ok(cursorPage([looks, specs])),
    'PATCH /attributes/{attribute}': ok({ data: color }),
    'POST /attributes/{attribute}/options': ok({ data: color.options[0] }, 201),
    'DELETE /attributes/{attribute}/options/{option}': reply(204),
    ...overrides
  })
}

type Wrapper = Awaited<ReturnType<typeof mountShowroomPage>>

async function submit(wrapper: Wrapper, form: string): Promise<void> {
  await wrapper.find(`[data-testid="${form}"]`).trigger('submit')
  await settle()
  await settle()
}

describe('showroom.attribute', () => {
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

  it('loads the attribute with its options, and calls atrium.screens.show with its key and record', async () => {
    showroomWith()

    const wrapper = await mountShowroomPage(Page, PATH)

    expect(showroomCalls('GET', '/attributes/{attribute}')[0]?.path).toBe('/attributes/color')
    expect(api).toHaveBeenCalledWith('/screens/showroom.attribute', expect.objectContaining({ query: expect.objectContaining({ record: 'color' }) }))
    expect(wrapper.find('[data-testid="showroom-attribute-title"] h1').text()).toBe('Color')
    expect(fieldValue(wrapper, 'showroom-attribute-group')).toBe('looks')
    expect(fieldValue(wrapper, 'showroom-attribute-flag-is_scopable')).toBe(true)
    expect(tableRow(wrapper, 'showroom-option-row-red').text()).toContain('Red')
    expect(tableRow(wrapper, 'showroom-option-row-blue').text()).toContain('Blue')
  })

  it('has no options card for an attribute without options, and shows its settings as JSON', async () => {
    showroomWith({ 'GET /attributes/{attribute}': ok({ data: name }) })

    const wrapper = await mountShowroomPage(Page, '/showroom/attributes/name')

    expect(wrapper.find('[data-testid="showroom-attribute-options"]').exists()).toBe(false)
    expect(JSON.parse((wrapper.find('[data-testid="showroom-attribute-settings"]').element as HTMLTextAreaElement).value)).toEqual({ max_length: 100 })
  })

  it('saves the group, labels, switches, settings and sort order, then reloads', async () => {
    showroomWith()

    const wrapper = await mountShowroomPage(Page, PATH)
    await setField(wrapper, 'showroom-attribute-group', 'specs')
    await wrapper.find('[data-testid="showroom-attribute-label"]').setValue('Colour')
    await setField(wrapper, 'showroom-attribute-flag-is_unique', true)
    await setField(wrapper, 'showroom-attribute-flag-is_scopable', false)
    await wrapper.find('[data-testid="showroom-attribute-settings"]').setValue('{"swatches": true}')
    await wrapper.find('[data-testid="showroom-attribute-sort"]').setValue('4')
    await submit(wrapper, 'showroom-attribute-form')

    const [call] = showroomCalls('PATCH', '/attributes/{attribute}')
    expect(call?.path).toBe('/attributes/color')
    expect(call?.body).toEqual({
      group: 'specs',
      labels: { en: 'Colour', fr: 'Couleur' },
      is_unique: true,
      is_localizable: false,
      is_scopable: false,
      settings: { swatches: true },
      sort_order: 4
    })
    expect(showroomCalls('GET', '/attributes/{attribute}')).toHaveLength(2)
  })

  it('sends no group and a null label when both are emptied', async () => {
    showroomWith()

    const wrapper = await mountShowroomPage(Page, PATH)
    await chooseOption(wrapper, 'showroom-attribute-group', null)
    await wrapper.find('[data-testid="showroom-attribute-label"]').setValue('')
    await submit(wrapper, 'showroom-attribute-form')

    expect(showroomCalls('PATCH', '/attributes/{attribute}')[0]?.body).toMatchObject({ group: null, labels: { en: null, fr: 'Couleur' }, settings: {} })
  })

  it('refuses settings that are not a JSON object, without calling Showroom', async () => {
    showroomWith()

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-attribute-settings"]').setValue('not json')
    await submit(wrapper, 'showroom-attribute-form')

    expect(showroomCalls('PATCH', '/attributes/{attribute}')).toHaveLength(0)
    expect(wrapper.text()).toContain('Settings must be a JSON object.')
  })

  it('shows the field errors of a 422 on save', async () => {
    showroomWith({ 'PATCH /attributes/{attribute}': invalid({ group: ['The selected group is invalid.'] }) })

    const wrapper = await mountShowroomPage(Page, PATH)
    await submit(wrapper, 'showroom-attribute-form')

    expect(wrapper.text()).toContain('The selected group is invalid.')
  })

  it('adds an option with its code, label and sort order, then reloads', async () => {
    showroomWith()

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-option-create-code"]').setValue('green')
    await wrapper.find('[data-testid="showroom-option-create-label"]').setValue('Green')
    await wrapper.find('[data-testid="showroom-option-create-sort"]').setValue('2')
    await submit(wrapper, 'showroom-option-create-form')

    const [call] = showroomCalls('POST', '/attributes/{attribute}/options')
    expect(call?.path).toBe('/attributes/color/options')
    expect(call?.body).toEqual({ code: 'green', labels: { en: 'Green' }, sort_order: 2 })
    expect(showroomCalls('GET', '/attributes/{attribute}')).toHaveLength(2)
  })

  it('shows the field errors of a 422 on a new option', async () => {
    showroomWith({ 'POST /attributes/{attribute}/options': invalid({ code: ['The code format is invalid.'] }) })

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-option-create-code"]').setValue('Green!')
    await submit(wrapper, 'showroom-option-create-form')

    expect(wrapper.find('[data-testid="showroom-attribute-options"]').text()).toContain('The code format is invalid.')
  })

  it('deletes an option once confirmed, then reloads', async () => {
    showroomWith()
    const confirm = confirmWith(true)

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-option-delete-blue"]').trigger('click')
    await settle()
    await settle()

    const [call] = showroomCalls('DELETE', '/attributes/{attribute}/options/{option}')
    expect(confirm).toHaveBeenCalledOnce()
    expect(call?.path).toBe('/attributes/color/options/blue')
    expect(call?.body).toBeUndefined()
    expect(showroomCalls('GET', '/attributes/{attribute}')).toHaveLength(2)
  })

  it('does not delete an option when the confirmation is declined', async () => {
    showroomWith()
    confirmWith(false)

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-option-delete-blue"]').trigger('click')
    await settle()

    expect(showroomCalls('DELETE', '/attributes/{attribute}/options/{option}')).toHaveLength(0)
  })

  it('deletes the attribute once confirmed, then returns to the list', async () => {
    showroomWith({ 'DELETE /attributes/{attribute}': reply(204) })
    confirmWith(true)

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-attribute-delete"]').trigger('click')
    await settle()

    expect(showroomCalls('DELETE', '/attributes/{attribute}')[0]?.path).toBe('/attributes/color')
    await vi.waitFor(() => expect(useRouter().currentRoute.value.path).toBe('/showroom/attributes'))
  })

  it('does not delete the attribute when the confirmation is declined', async () => {
    showroomWith({ 'DELETE /attributes/{attribute}': reply(204) })
    confirmWith(false)

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-attribute-delete"]').trigger('click')
    await settle()

    expect(showroomCalls('DELETE', '/attributes/{attribute}')).toHaveLength(0)
  })

  it('shows Showroom\'s message when a delete is refused with a 409', async () => {
    showroomWith({ 'DELETE /attributes/{attribute}': reply(409, { message: 'The attribute is still used by a family.' }) })
    confirmWith(true)

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-attribute-delete"]').trigger('click')
    await settle()

    expect(wrapper.find('[data-testid="showroom-action-error"]').text()).toContain('The attribute is still used by a family.')
    expect(useRouter().currentRoute.value.path).toBe(PATH)
  })

  it('shows the no-access notice when an action answers 403', async () => {
    showroomWith({ 'PATCH /attributes/{attribute}': reply(403) })

    const wrapper = await mountShowroomPage(Page, PATH)
    await submit(wrapper, 'showroom-attribute-form')

    expect(wrapper.find('[data-testid="atrium-no-access"]').exists()).toBe(true)
  })

  it('shows the no-access state on a 403', async () => {
    showroomWith({ 'GET /attributes/{attribute}': reply(403) })

    const wrapper = await mountShowroomPage(Page, PATH)

    expect(wrapper.find('[data-testid="atrium-no-access"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="showroom-attribute-form"]').exists()).toBe(false)
  })

  it('shows the not-found state on a 404', async () => {
    showroomWith({ 'GET /attributes/{attribute}': reply(404, { message: 'Not found.' }) })

    const wrapper = await mountShowroomPage(Page, PATH)

    expect(wrapper.find('[data-testid="showroom-not-found"]').exists()).toBe(true)
  })
})
