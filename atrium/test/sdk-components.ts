import { config } from '@vue/test-utils'
import * as sdk from '@refactor-circus/atrium'
import * as ui from '@refactor-circus/atrium/ui'
import type { Component } from 'vue'

/*
 * The SDK's own components (`AtriumIntegrationScreen` and friends) use
 * `<Atrium*>` and `<U*>` without importing them and lean on the preset's
 * component resolver. Installed from GitHub the SDK sits under
 * `node_modules/@refactor-circus/atrium`, which Nuxt UI's components plugin
 * never transforms, so in tests those tags would not resolve. Register them
 * globally instead (Atrium's own workspace links the SDK from `ui/sdk`, so it
 * never meets this). Drop this once the SDK imports what its components use.
 */
const components: Record<string, Component> = {}

for (const [name, value] of Object.entries({ ...sdk, ...ui })) {
  if (/^(Atrium|U)[A-Z]/.test(name) && value && typeof value === 'object') {
    components[name] = value as Component
  }
}

config.global.components = { ...config.global.components, ...components }
