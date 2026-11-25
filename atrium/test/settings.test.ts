import { afterEach, describe, expect, it } from 'vitest'
import SettingsPanel from '../src/components/settings/SettingsPanel.vue'
import { settle } from '@refactor-circus/atrium/testing'
import { mockPackageApi, mountIntegration, reply, resetPackageApis, type PackageBody } from '@refactor-circus/atrium/testing'
import fixture from '@refactor-circus/atrium/fixtures/integrations/showroom.json'

/*
 * The `showroom` settings panel (AC 61): the PHP side sends `data: {}`, and
 * the Vue panel renders `showroom.settings-summary.show` in the rows the v1
 * panel showed: the HTTP API (authorization, route prefix, page sizes) and
 * the MCP transports.
 */

const summary = {
  data: {
    authorization: true,
    routes: { enabled: true, prefix: 'showroom', middleware: ['api'] },
    mcp: {
      web: { enabled: true, route: 'mcp/showroom', middleware: ['api'] },
      local: { enabled: false, handle: 'showroom' }
    },
    pagination: { per_page: 25, max_per_page: 100 }
  }
} satisfies PackageBody<'showroom', 'GET /settings-summary'>

async function mount() {
  const wrapper = await mountIntegration(SettingsPanel, 'showroom', { props: { data: fixture.settings.data } })
  await settle()

  return wrapper
}

function rows(wrapper: Awaited<ReturnType<typeof mount>>, section: string): Record<string, string> {
  return Object.fromEntries(wrapper.find(`[data-testid="showroom-settings-${section}"]`).findAll('[data-row]').map(row => [
    row.attributes('data-row'),
    row.find('dd').text()
  ]))
}

afterEach(() => {
  resetPackageApis()
})

describe('the showroom settings panel', () => {
  it('is declared with static data only', () => {
    expect(fixture.settings.data).toEqual({})
  })

  it('renders showroom.settings-summary.show as the v1 rows', async () => {
    const calls = mockPackageApi('showroom', { 'GET /settings-summary': summary })

    const wrapper = await mount()

    expect(calls.map(call => `${call.method} ${call.route}`)).toEqual(['GET /settings-summary'])
    expect(rows(wrapper, 'api')).toEqual({
      authorization: 'On',
      routes_prefix: '/showroom',
      per_page: '25',
      max_per_page: '100'
    })
    expect(rows(wrapper, 'mcp')).toEqual({
      web_transport: '/mcp/showroom',
      local_transport: 'Off'
    })
  })

  it('shows off for what is turned off', async () => {
    mockPackageApi('showroom', {
      'GET /settings-summary': {
        data: {
          ...summary.data,
          authorization: false,
          routes: { ...summary.data.routes, enabled: false },
          mcp: { web: { ...summary.data.mcp.web, enabled: false }, local: { enabled: true, handle: 'showroom' } }
        }
      }
    })

    const wrapper = await mount()

    expect(rows(wrapper, 'api')).toMatchObject({ authorization: 'Off', routes_prefix: 'Off' })
    expect(rows(wrapper, 'mcp')).toEqual({ web_transport: 'Off', local_transport: 'showroom' })
  })

  it('renders the no-access state on a 403', async () => {
    mockPackageApi('showroom', { 'GET /settings-summary': reply(403) })

    const wrapper = await mount()

    expect(wrapper.find('[data-testid="atrium-no-access"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="showroom-settings-api"]').exists()).toBe(false)
  })

  it('renders the error card on any other failure', async () => {
    mockPackageApi('showroom', { 'GET /settings-summary': reply(500) })

    const wrapper = await mount()

    expect(wrapper.find('[data-testid="atrium-error-card"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="atrium-no-access"]').exists()).toBe(false)
  })
})
