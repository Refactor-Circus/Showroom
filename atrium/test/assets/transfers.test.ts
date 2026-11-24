import { defineComponent, h } from 'vue'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import Page from '../../src/pages/transfers/index.vue'
import { settle, withSetup } from '@refactor-circus/atrium/testing'
import { integrationEntry, mockPackageApi, packageCalls, reply, resetPackageApis, type PackageBody, type PackageRoutes } from '@refactor-circus/atrium/testing'
import type { TransferRun } from '../../src/components/transfers/transfers'
import { cursorPage, invalid, ok, resetShowroomPages, showroomCalls } from '../catalog/support'
import { flow, master, mountAreaPage, pickFile, resetAreaPages, ROW_SECTION, run, shirts, transfersApi, transfersScreen, webshop } from './support'
import { useAtrium } from '@refactor-circus/atrium'
import { clearAtriumData, mockAtriumApi, useRouter } from '@refactor-circus/atrium/testing'

/*
 * `showroom.transfers` (AC 54, 71; D-035, D-042): the recent transfer runs
 * come from Impex. The page reads the flow prefix from its screen
 * (`atrium.screens.show` `options.flow_prefix`), keeps the `impex.flows.index`
 * flows whose slug starts with it, calls `impex.runs.index?flow=` once per
 * flow, merges the answers and keeps the 20 newest by `created_at`. Imports
 * (multipart: file and mode) and exports (JSON: format, family, category
 * tree, channel and published) start through `showroom.imports.store` and
 * `showroom.exports.store`. Without an active Impex the runs give way to a
 * "requires Impex" notice, and import and export stay available. A 422 shows
 * the field errors, a 403 no access.
 */

const { api } = vi.hoisted(() => ({ api: vi.fn() }))

mockAtriumApi(api)

const PATH = '/showroom/transfers'

/** A day of November 2026 at noon, as Impex stamps a run. */
function day(n: number): string {
  return `2026-11-${String(n).padStart(2, '0')}T12:00:00+00:00`
}

// Fifteen import runs on days 1-15 and ten export runs on days 16-25, each
// list newest first as Impex answers: merged, the 20 newest are days 25 to 6.
const IMPORTS = Array.from({ length: 15 }, (_, index) => run(`imp-${index + 1}`, 'showroom:import', day(index + 1)))
const EXPORTS = Array.from({ length: 10 }, (_, index) => run(`exp-${index + 16}`, 'showroom:export', day(index + 16)))

/**
 * Runs as `impex.runs.index` lists them. Its schema types the inline `tags`
 * map as `Record<string, never>` where `RunResource` has string values; the
 * fixtures send null, which both allow.
 */
function listed(runs: TransferRun[]): PackageBody<'impex', 'GET /runs'>['data'] {
  return runs as PackageBody<'impex', 'GET /runs'>['data']
}

function impexWith(overrides: PackageRoutes<'impex'> = {}): void {
  mockPackageApi('impex', {
    'GET /flows': { data: [flow('showroom:import'), flow('impex:cleanup'), flow('showroom:export'), flow('catalog:feed')] },
    'GET /runs': call => ({
      data: listed(call.query.flow === 'showroom:import'
        ? [...IMPORTS].reverse()
        : call.query.flow === 'showroom:export'
          ? [...EXPORTS].reverse()
          : call.query.flow === 'catalog:feed' ? [run('feed-1', 'catalog:feed', day(26))] : [])
    }),
    ...overrides
  })
}

function showroomWith(overrides: PackageRoutes<'showroom'> = {}): void {
  mockPackageApi('showroom', {
    'GET /families': ok(cursorPage([shirts])),
    'GET /categories': ok(cursorPage([master])),
    'GET /channels': ok(cursorPage([webshop])),
    'POST /imports': ok({ data: run('imp-new', 'showroom:import', day(27), { status: 'pending' }) }, 202),
    'POST /exports': ok({ data: run('exp-new', 'showroom:export', day(27), { status: 'pending' }) }, 202),
    ...overrides
  })
}

function impexCalls(method: string, route: string) {
  return packageCalls.filter(call => call.pkg === 'impex' && call.method === method && call.route === route)
}

type Wrapper = Awaited<ReturnType<typeof mountAreaPage>>

async function submit(wrapper: Wrapper, form: string): Promise<void> {
  await wrapper.find(`[data-testid="${form}"]`).trigger('submit')
  await settle()
  await settle()
  await settle()
}

describe('showroom.transfers', () => {
  beforeEach(() => {
    clearAtriumData()
    api.mockReset()
    api.mockImplementation(transfersApi(transfersScreen()))
  })

  afterEach(async () => {
    resetAreaPages()
    resetShowroomPages()
    resetPackageApis()
    vi.restoreAllMocks()
    await useRouter().replace('/')
  })

  describe('with Impex active', () => {
    it('lists the runs of the flows under the screen\'s prefix, one runs.index call per flow, merged into the 20 newest', async () => {
      impexWith()
      showroomWith()

      const wrapper = await mountAreaPage(Page, PATH)

      expect(api).toHaveBeenCalledWith('/screens/showroom.transfers', expect.anything())
      expect(impexCalls('GET', '/flows')).toHaveLength(1)
      expect(impexCalls('GET', '/runs').map(call => call.query)).toEqual([
        { flow: 'showroom:import', per_page: '20' },
        { flow: 'showroom:export', per_page: '20' }
      ])

      const rows = wrapper.findAll('[data-testid^="showroom-transfer-run-"]')
      expect(rows).toHaveLength(20)
      expect(rows[0]!.attributes('data-testid')).toBe('showroom-transfer-run-exp-25')
      expect(rows[19]!.attributes('data-testid')).toBe('showroom-transfer-run-imp-6')
      expect(wrapper.find('[data-testid="showroom-transfer-run-imp-5"]').exists()).toBe(false)
      expect(rows[0]!.text()).toContain('showroom:export')
      expect(rows[0]!.text()).toContain('completed')
      expect(wrapper.find('[data-testid="showroom-transfers-requires-impex"]').exists()).toBe(false)
    })

    it('reads the flow prefix from the screen\'s options', async () => {
      api.mockImplementation(transfersApi(transfersScreen('catalog:')))
      impexWith()
      showroomWith()

      const wrapper = await mountAreaPage(Page, PATH)

      expect(impexCalls('GET', '/runs').map(call => call.query.flow)).toEqual(['catalog:feed'])
      expect(wrapper.findAll('[data-testid^="showroom-transfer-run-"]')).toHaveLength(1)
    })

    it('shows a failed run\'s error message', async () => {
      impexWith({ 'GET /runs': call => ({ data: listed(call.query.flow === 'showroom:import' ? [run('imp-bad', 'showroom:import', day(3), { status: 'failed', error: { message: 'Row 4 has no identifier.' } })] : []) }) })
      showroomWith()

      const wrapper = await mountAreaPage(Page, PATH)

      const row = wrapper.find('[data-testid="showroom-transfer-run-imp-bad"]')
      expect(row.text()).toContain('failed')
      expect(row.text()).toContain('Row 4 has no identifier.')
    })

    it('shows the empty state when no run matches', async () => {
      impexWith({ 'GET /flows': { data: [flow('impex:cleanup')] } })
      showroomWith()

      const wrapper = await mountAreaPage(Page, PATH)

      expect(impexCalls('GET', '/runs')).toHaveLength(0)
      expect(wrapper.find('[data-testid="showroom-transfers-no-runs"]').exists()).toBe(true)
    })

    it('renders the screen\'s row.actions sections on each run, given the run id', async () => {
      const atrium = await withSetup(() => useAtrium())
      atrium.extension(ROW_SECTION, defineComponent({
        props: { data: { type: Object, default: null }, record: { type: null, default: null } },
        setup: props => () => h('span', { 'data-testid': 'row-section' }, String(props.record))
      }))
      api.mockImplementation(transfersApi(transfersScreen('showroom:', true)))
      impexWith()
      showroomWith()

      const wrapper = await mountAreaPage(Page, PATH)

      expect(wrapper.find('[data-testid="showroom-transfer-run-exp-25"] [data-testid="row-section"]').text()).toBe('exp-25')
      expect(wrapper.find('[data-testid="showroom-transfer-run-imp-6"] [data-testid="row-section"]').text()).toBe('imp-6')
    })

    it('shows the no-access state in the runs card when Impex answers 403, and keeps import and export', async () => {
      impexWith({ 'GET /flows': reply(403) })
      showroomWith()

      const wrapper = await mountAreaPage(Page, PATH)

      expect(wrapper.find('[data-testid="showroom-transfers-runs"] [data-testid="atrium-no-access"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="showroom-transfer-import-form"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="showroom-transfer-export-form"]').exists()).toBe(true)
    })

    it('starts an import with the file and mode as multipart, then reloads the runs', async () => {
      impexWith()
      showroomWith()

      const wrapper = await mountAreaPage(Page, PATH)
      await pickFile(wrapper.find('[data-testid="showroom-transfer-import-file"]'), new File(['identifier\nTEE-1'], 'products.csv', { type: 'text/csv' }))
      await wrapper.find('[data-testid="showroom-transfer-import-mode"]').setValue('create')
      await submit(wrapper, 'showroom-transfer-import-form')

      const [call] = showroomCalls('POST', '/imports')
      const body = String(call?.body)
      expect(call?.headers.get('content-type')).toContain('multipart/form-data')
      expect(body).toContain('filename="products.csv"')
      expect(body).toMatch(/name="mode"\r?\n\r?\ncreate/)
      expect(impexCalls('GET', '/flows')).toHaveLength(2)
      expect(wrapper.find('[data-testid="showroom-transfer-started"]').exists()).toBe(true)
    })

    it('starts an upsert import by default', async () => {
      impexWith()
      showroomWith()

      const wrapper = await mountAreaPage(Page, PATH)
      await pickFile(wrapper.find('[data-testid="showroom-transfer-import-file"]'), new File(['x'], 'products.jsonl'))
      await submit(wrapper, 'showroom-transfer-import-form')

      expect(String(showroomCalls('POST', '/imports')[0]?.body)).toMatch(/name="mode"\r?\n\r?\nupsert/)
    })

    it('starts an export with its format, filters and published switch, then reloads the runs', async () => {
      impexWith()
      showroomWith()

      const wrapper = await mountAreaPage(Page, PATH)
      await wrapper.find('[data-testid="showroom-transfer-export-format"]').setValue('csv')
      await wrapper.find('[data-testid="showroom-transfer-export-family"]').setValue('shirts')
      await wrapper.find('[data-testid="showroom-transfer-export-category"]').setValue('master')
      await wrapper.find('[data-testid="showroom-transfer-export-scope"]').setValue('webshop')
      await wrapper.find('[data-testid="showroom-transfer-export-published"]').setValue(true)
      await submit(wrapper, 'showroom-transfer-export-form')

      const [call] = showroomCalls('POST', '/exports')
      expect(call?.body).toEqual({ format: 'csv', family: 'shirts', category: 'master', scope: 'webshop', published: true })
      expect(impexCalls('GET', '/flows')).toHaveLength(2)
    })

    it('exports every product as JSONL by default, its working copies', async () => {
      impexWith()
      showroomWith()

      const wrapper = await mountAreaPage(Page, PATH)
      await submit(wrapper, 'showroom-transfer-export-form')

      expect(showroomCalls('POST', '/exports')[0]?.body).toEqual({ format: 'jsonl', published: false })
    })

    it('shows the field errors of a 422 on import', async () => {
      impexWith()
      showroomWith({ 'POST /imports': invalid({ file: ['The file must be a CSV or JSON Lines file.'] }) })

      const wrapper = await mountAreaPage(Page, PATH)
      await pickFile(wrapper.find('[data-testid="showroom-transfer-import-file"]'), new File(['x'], 'products.xls'))
      await submit(wrapper, 'showroom-transfer-import-form')

      expect(wrapper.text()).toContain('The file must be a CSV or JSON Lines file.')
    })

    it('shows the field errors of a 422 on export', async () => {
      impexWith()
      showroomWith({ 'POST /exports': invalid({ family: ['The selected family is invalid.'] }) })

      const wrapper = await mountAreaPage(Page, PATH)
      await submit(wrapper, 'showroom-transfer-export-form')

      expect(wrapper.text()).toContain('The selected family is invalid.')
    })

    it('shows the no-access notice when an import answers 403', async () => {
      impexWith()
      showroomWith({ 'POST /imports': reply(403) })

      const wrapper = await mountAreaPage(Page, PATH)
      await pickFile(wrapper.find('[data-testid="showroom-transfer-import-file"]'), new File(['x'], 'products.csv'))
      await submit(wrapper, 'showroom-transfer-import-form')

      expect(wrapper.find('[data-testid="showroom-transfer-import"] [data-testid="atrium-no-access"]').exists()).toBe(true)
    })

    it('shows the no-access notice when an export answers 403', async () => {
      impexWith()
      showroomWith({ 'POST /exports': reply(403) })

      const wrapper = await mountAreaPage(Page, PATH)
      await submit(wrapper, 'showroom-transfer-export-form')

      expect(wrapper.find('[data-testid="showroom-transfer-export"] [data-testid="atrium-no-access"]').exists()).toBe(true)
    })
  })

  describe('without an active Impex', () => {
    it.each([
      ['inactive', [integrationEntry('showroom'), integrationEntry('impex', { active: false })]],
      ['absent', [integrationEntry('showroom')]]
    ])('shows the requires-Impex notice and calls no Impex route when Impex is %s', async (_, integrations) => {
      impexWith()
      showroomWith()

      const wrapper = await mountAreaPage(Page, PATH, integrations)

      expect(wrapper.find('[data-testid="showroom-transfers-requires-impex"]').exists()).toBe(true)
      expect(packageCalls.filter(call => call.pkg === 'impex')).toHaveLength(0)
      expect(wrapper.find('[data-testid="showroom-transfer-import-form"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="showroom-transfer-export-form"]').exists()).toBe(true)
    })

    it('still starts an import and an export', async () => {
      showroomWith()

      const wrapper = await mountAreaPage(Page, PATH, [integrationEntry('showroom')])
      await pickFile(wrapper.find('[data-testid="showroom-transfer-import-file"]'), new File(['x'], 'products.csv'))
      await submit(wrapper, 'showroom-transfer-import-form')
      await submit(wrapper, 'showroom-transfer-export-form')

      expect(showroomCalls('POST', '/imports')).toHaveLength(1)
      expect(showroomCalls('POST', '/exports')).toHaveLength(1)
    })
  })
})
