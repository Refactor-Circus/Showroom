import './sdk-components'
import { useTestModule } from '@refactor-circus/atrium/testing'
import Showroom from '../src/atrium'

// Showroom's registrations, strings and routes, in every test file's app.
useTestModule(Showroom)
