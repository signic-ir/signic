// cypress-bdd/support/setup.js
//
// Per-scenario setup shared by every feature.
//
// v28 creates the step-definition registry PER SPEC, so the `Before` hook has
// to be registered from a file that the spec imports — `support/e2e.js` is
// bundled separately and has no registry. Each `features/<module>/<feature>.js`
// therefore calls `registerBeforeHooks({ authenticate })` at module scope.
import { Before } from '@badeball/cypress-cucumber-preprocessor'
import world from './world'
import api from './api'

export function registerBeforeHooks({ authenticate = true } = {}) {
  Before(() => {
    // Fresh state for every scenario — no fixture identity leaks between them.
    world.reset()

    // The app's rate limiters are keyed in Redis; clearing them means no
    // scenario inherits a 429 from the one before it.
    return api.clearRateLimits().then(() => {
      if (!authenticate) return undefined
      // A fresh bearer token for a fresh user, like pytest-bdd's `api_client`.
      return api.authenticate(world)
    })
  })
}

export default registerBeforeHooks
