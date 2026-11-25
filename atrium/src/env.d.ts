/// <reference types="vite/client" />
import type { paths as ImpexPaths } from '../types/impex-schema'
import type { paths } from '../../client/src/schema'

// Types `useAtriumIntegration('showroom')`, `useIntegrationQuery('showroom', …)` and
// `mockPackageApi('showroom', …)` by Showroom's API.
declare module '@refactor-circus/atrium' {
  interface AtriumIntegrationPaths {
    showroom: paths
    // Showroom's transfers screen calls Impex's API.
    impex: ImpexPaths
  }
}
