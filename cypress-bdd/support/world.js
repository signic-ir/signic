// cypress-bdd/support/world.js
//
// A single World object shared by every step definition. Cypress bundles the
// support file and each spec into SEPARATE esbuild bundles, so a plain
// module-level object would be a DIFFERENT instance in each. Anchoring it on
// `window` (one browser context per spec run) guarantees a shared instance.
//
// Mirrors pytest-bdd's `api_client` session object.
const KEY = '__signicBddWorld'

const DEFAULTS = {
  authToken: null,
  otpPhone: null,
  otpCode: null,
  eventId: null,
  eventName: null,
  attendeeId: null,
  attendeeName: null,
  qrToken: null,
  exhibitorId: null,
  exhibitorName: null,
  boothId: null,
  boothName: null,
  turnstileId: null,
  turnstileName: null,
  leadId: null,
  lead: null,
  userId: null,
  roleNames: [],
  lastResponse: null,
  raceResponses: [],
  outsiderToken: null,
  pendingAttendee: null,
  scanEventCount: 0,
  invalidQrToken: null,
  duplicateEmail: null,
}

function fresh() {
  const state = {}
  for (const key of Object.keys(DEFAULTS)) {
    const value = DEFAULTS[key]
    state[key] = Array.isArray(value) ? value.slice() : value
  }
  return state
}

const world = (typeof window !== 'undefined')
  ? (window[KEY] || (window[KEY] = fresh()))
  : fresh()

if (typeof window !== 'undefined') {
  window[KEY] = world
}

// Restore the World to its per-scenario defaults.
world.reset = function reset() {
  const state = fresh()
  for (const key of Object.keys(state)) {
    world[key] = state[key]
  }
}

module.exports = world
