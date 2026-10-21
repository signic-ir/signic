// cypress-bdd/support/api.js
//
// Helpers that mirror `bdd_utils.py` from the green pytest-bdd harness.
// Uses `cy.request` (token attached manually) and `cy.task` for the Redis OTP
// read. Module prefixes match the app exactly:
//   IDENTITY="/api/identity"  REGISTRATION="/registration"
//   ACCESS="/access"  EXHIBITION="/exhibition"  SHARED="/shared"
const TEST_PHONE = '+989123456789'

function uniqueSuffix() { return Date.now().toString(36).slice(-8) }

// The app validates phones as /^[\+]?[0-9]{10,15}$/ — every character must be a
// digit, so the random part is generated as digits, never as base36 letters.
function uniqueDigits(length) {
  let out = ''
  while (out.length < length) {
    out += Math.floor(Math.random() * 10 ** 8).toString().padStart(8, '0')
  }
  return out.slice(0, length)
}
function uniquePhone() { return `+9895${uniqueDigits(9)}` }

// `cy.request` parses a JSON response body for us, so the body may already
// be an object. Only fall back to JSON.parse for a raw string.
function body(resp) {
  if (!resp) return {}
  if (typeof resp.body === 'object' && resp.body !== null) return resp.body
  try { return JSON.parse(resp.body) } catch (e) { return {} }
}

function post(url, data, token) {
  const headers = { Accept: 'application/json' }
  if (data !== null && data !== undefined) { headers['Content-Type'] = 'application/json' }
  if (token) headers.Authorization = `Bearer ${token}`
  return cy.request({ method: 'POST', url, headers, body: data != null ? data : {}, failOnStatusCode: false, timeout: 20000 })
}

function get(url, token, params) {
  const headers = { Accept: 'application/json' }
  if (token) headers.Authorization = `Bearer ${token}`
  // cy.request takes query string parameters in `qs` — a `params` key is
  // silently ignored, so the server would answer with the unfiltered list.
  return cy.request({ method: 'GET', url, headers, ...(params ? { qs: params } : {}), failOnStatusCode: false, timeout: 20000 })
}

function put(url, data, token) {
  const headers = { 'Content-Type': 'application/json', Accept: 'application/json' }
  if (token) headers.Authorization = `Bearer ${token}`
  return cy.request({ method: 'PUT', url, headers, body: data != null ? data : {}, failOnStatusCode: false, timeout: 20000 })
}

function del(url, token) {
  const headers = { Accept: 'application/json' }
  if (token) headers.Authorization = `Bearer ${token}`
  return cy.request({ method: 'DELETE', url, headers, failOnStatusCode: false, timeout: 20000 })
}

// Absolute URL for the app under test. A raw `fetch` resolves relative paths
// against the Cypress runner's origin, not the app's.
function absoluteUrl(url) {
  if (/^https?:\/\//i.test(url)) return url
  const base = (Cypress.config('baseUrl') || '').replace(/\/+$/, '')
  return base + url
}

// A POST that returns a REAL promise, so Promise.all actually issues the
// requests concurrently. cy.request() returns a Cypress chain — several of
// them in a Promise.all are queued and run one after another, which would
// make the "everyone scans at the same time" race meaningless.
function postParallel(url, data, token) {
  const headers = { Accept: 'application/json', 'Content-Type': 'application/json' }
  if (token) headers.Authorization = `Bearer ${token}`
  return fetch(absoluteUrl(url), {
    method: 'POST',
    headers,
    body: JSON.stringify(data == null ? {} : data),
  }).then(async (res) => ({
    status: res.status,
    body: await res.text(),
  }))
}

// POST the OTP request, return [response, otpCode from redis]
function requestOtp(phone) {
  return post('/api/identity/otp/request', { phone }).then((resp) =>
    (resp.status === 200 || resp.status === 201 ? fetchOtp(phone) : null).then((code) => [resp, code])
  )
}

function fetchOtp(phone) { return cy.task('fetchOtp', phone) }
function expireOtp(phone) { return cy.task('expireOtp', phone) }
function clearRateLimits() { return cy.task('clearRateLimits') }

// authenticate(): request OTP for a unique phone, read the code from Redis, verify.
function authenticate(world, phone) {
  const p = phone || uniquePhone()
  return requestOtp(p)
    .then(([resp, code]) => {
      if (!code) throw new Error(`OTP not found in Redis for ${p}`)
      return post('/api/identity/otp/verify', { phone: p, otp: code })
    })
    .then((verify) => {
      if (verify.status === 200) {
        world.authToken = body(verify).access_token
        return currentUserId(world).then((uid) => {
          world.userId = uid
          return verify
        })
      }
      return verify
    })
}

function currentUserId(world) {
  return get('/shared/users/me', world.authToken).then((resp) => {
    const b = body(resp)
    return b.user_id || (b.user && b.user.id) || 1
  })
}

function ensureEvent(world, name = 'Tech Conference 2024') {
  return get('/registration/events', world.authToken, { name }).then((resp) => {
    const events = body(resp).events || []
    for (const e of events) if (e.name === name) { world.eventId = e.id; world.eventName = name; return e }
    return post('/registration/events', { name, starts_at: '2024-06-01T09:00:00Z', ends_at: '2024-06-02T18:00:00Z', description: `${name} event`, status: 'ACTIVE', is_registration_open: true }, world.authToken).then((created) => {
      const e = body(created).event || body(created)
      world.eventId = e.id; world.eventName = name; return e
    })
  })
}

function ensureEventOrganizer(world) {
  return ensureRole(world, 'event_organizer').then(() =>
    currentUserId(world).then((uid) => {
      world.userId = uid
      return post('/shared/users/' + world.userId + '/roles', { role: 'event_organizer' }, world.authToken)
    })
  )
}

function ensureTurnstile(world, name = 'Main Gate') {
  return get('/access/turnstiles', world.authToken, { name }).then((resp) => {
    const turnstiles = body(resp).turnstiles || []
    for (const t of turnstiles) if (t.name === name) { world.turnstileId = t.id; world.turnstileName = name; return t }
    return post('/access/turnstiles', { event_id: world.eventId, name, type: 'gate', is_active: true }, world.authToken).then((created) => {
      const t = body(created).turnstile || body(created)
      world.turnstileId = t.id; world.turnstileName = name; return t
    })
  })
}

function ensureExhibitor(world, name = 'Acme Corp') {
  return get('/exhibition/exhibitors', world.authToken, { name }).then((resp) => {
    const exhibitors = body(resp).exhibitors || []
    for (const x of exhibitors) if (x.company_name === name) { world.exhibitorId = x.id; world.exhibitorName = name; return x }
    return post('/exhibition/exhibitors', { event_id: world.eventId, company_name: name, status: 'CONFIRMED' }, world.authToken).then((created) => {
      const x = body(created).exhibitor || body(created)
      world.exhibitorId = x.id; world.exhibitorName = name; return x
    })
  })
}

function ensureBooth(world, name = 'Acme Corp Booth', exhibitorId, eventId) {
  const sid = exhibitorId || world.exhibitorId
  const eid = eventId || world.eventId
  return get('/exhibition/booths', world.authToken, { name }).then((resp) => {
    const booths = body(resp).booths || []
    for (const b of booths) if (b.name === name) { world.boothId = b.id; world.boothName = name; return b }
    return post('/exhibition/booths', { exhibitor_id: sid, event_id: eid, number: 'B' + Math.abs(hashStr(name)) % 900 + 100, name, location: name }, world.authToken).then((created) => {
      const b = body(created).booth || body(created)
      world.boothId = b.id; world.boothName = name; return b
    })
  })
}

function hashStr(s) { let h = 0; for (let i = 0; i < s.length; i++) h = ((h << 5) - h) + s.charCodeAt(i); return h }

function ensureRole(world, name, description = '') {
  return get('/shared/roles', world.authToken).then((resp) => {
    const roles = body(resp).roles || []
    for (const r of roles) if (r.name === name) { world.roleNames = Array.from(new Set([...(world.roleNames || []), name])); return r }
    return post('/shared/roles', { name, description }, world.authToken).then((created) => {
      const r = body(created).role || body(created)
      world.roleNames = Array.from(new Set([...(world.roleNames || []), name]))
      return r
    })
  })
}

function ensurePermission(world, name, description = '') {
  return get('/shared/permissions', world.authToken).then((resp) => {
    const perms = body(resp).permissions || []
    return perms.find((p) => p.name === name) || post('/shared/permissions', { name, description }, world.authToken).then((created) => body(created).permission || body(created))
  })
}

function assignPermissionToRole(world, roleName, permissionName) {
  return ensureRole(world, roleName).then((role) =>
    ensurePermission(world, permissionName).then(() =>
      post(`/shared/roles/${role.id}/permissions`, { permission: permissionName }, world.authToken).then((resp) => {
        const r = body(resp).role || {}
        return { ...role, permissions: r.permissions || role.permissions, permission_names: r.permission_names || role.permission_names }
      })
    )
  )
}

function assignRoleToUser(world, userId, roleName) {
  return ensureRole(world, roleName).then(() => post(`/shared/users/${userId}/roles`, { role: roleName }, world.authToken))
}

function registerAttendee(world, opts = {}) {
  const firstName = opts.firstName || 'John'
  const lastName = opts.lastName || 'Doe'
  const unique = uniqueSuffix()
  const payload = {
    first_name: firstName, last_name: lastName,
    name: `${firstName} ${lastName}`.trim(),
    email: opts.email || `${firstName.toLowerCase()}.${lastName.toLowerCase()}${unique}@example.com`,
    phone: opts.phone || `+9899${uniqueDigits(9)}`,
    ticket_type: opts.ticketType || 'GENERAL',
  }
  // The app rejects a registration without an event, so default to the
  // World's event the way bdd_utils.register_attendee defaults to the
  // client's event.
  const eventId = opts.eventId !== undefined ? opts.eventId : world.eventId
  const eventName = opts.eventName !== undefined ? opts.eventName : world.eventName
  if (eventId !== undefined && eventId !== null) payload.event_id = eventId
  if (eventName !== undefined && eventName !== null) payload.event_name = eventName
  return post('/registration/attendees', payload, world.authToken).then((resp) => {
    const b = body(resp)
    // bdd_utils.register_attendee raises on a non-2xx so the failing status
    // and body are visible instead of a downstream "undefined" assertion.
    if (resp.status !== 200 && resp.status !== 201) {
      throw new Error(`Could not register attendee: ${resp.status} - ${JSON.stringify(b)}`)
    }
    world.attendeeId = b.attendee ? b.attendee.id : (b.data && b.data.id)
    world.qrToken = b.qr_token
    return b
  })
}

function fetchQrToken(world, attendeeId) {
  const id = attendeeId || world.attendeeId
  return get(`/registration/attendees/${id}/qr`, world.authToken).then((resp) => {
    const token = body(resp).qr_token
    if (!token) throw new Error(`No qr_token in response: ${resp.body}`)
    world.qrToken = token
    return token
  })
}

function tableToDict(table) {
  if (!table) return {}
  const raw = table.raw ? table.raw() : (Array.isArray(table) ? table : [table])
  if (!raw || !raw.length) return {}
  const headers = [...raw[0]].map((h) => String(h).trim().toLowerCase())
  const out = {}
  for (const row of raw.slice(1)) {
    headers.forEach((h, i) => { const v = row[i]; if (v !== undefined && v !== null) out[h] = v })
  }
  return out
}

// Run `thunk` for every item in order, awaiting each Cypress chain before
// starting the next one. A `Promise.resolve()` seed makes the step handler
// return a NATIVE Promise, which the Cucumber preprocessor rejects ("you
// returned a native promise from a function handler"). Starting with a
// `cy.wrap(null)` seed keeps the whole thing a Cypress command chain, so the
// final value is a chainable rather than a native Promise.
function chainAll(thunks) {
  return thunks.reduce((acc, thunk) => acc.then(thunk), cy.wrap(null))
}

module.exports = {
  TEST_PHONE, uniquePhone, uniqueDigits, uniqueSuffix, body,
  post, get, put, del, requestOtp, fetchOtp, expireOtp, clearRateLimits,
  postParallel, absoluteUrl, chainAll,
  authenticate, currentUserId, ensureEvent, ensureEventOrganizer,
  ensureTurnstile, ensureExhibitor, ensureBooth, ensureRole, ensurePermission,
  assignPermissionToRole, assignRoleToUser, registerAttendee, fetchQrToken, tableToDict,
}
