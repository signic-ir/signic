// cypress-bdd/features/accesscontrol/turnstile_checkin.js
//
// Step definitions for the AccessControl module feature, mirroring
// `/home/hamid/bdd-test/step_definitions/accesscontrol_turnstile_steps.py` exactly.
//
// v28 uses Cucumber Expressions natively. Free‑text steps use RegularExpression.
import { Given, When, Then } from '@badeball/cypress-cucumber-preprocessor'
import world from '../../support/world'
import api from '../../support/api'
import { registerBeforeHooks } from '../../support/setup'

registerBeforeHooks()

const DEFAULT_TURNSTILE = 'Main Gate'
const DEFAULT_EVENT = 'Tech Conference 2024'

Given('the system is running', () => {})

Given('I have access to the AccessControl module', () => {})

Given('there is an active turnstile at entrance {string}', (turnstile = DEFAULT_TURNSTILE) => {
  return api.ensureEvent(world, DEFAULT_EVENT).then(() => api.ensureTurnstile(world, turnstile))
})

Given('there is a confirmed attendee with a valid QR badge', () => {
  const unique = api.uniqueSuffix().slice(0, 8)
  return api.ensureEvent(world, DEFAULT_EVENT).then(() => api.ensureEventOrganizer(world)).then(() =>
    api.registerAttendee(world, {
      first_name: 'John',
      last_name: 'Doe',
      email: `john.doe.${unique}@example.com`,
      phone: `+9895${api.uniqueDigits(9)}`,
      ticket_type: 'GENERAL',
    }).then((body) => {
      expect(body.attendee ? body.attendee.id : (body.data && body.data.id), 'Could not register the attendee').to.exist
      world.attendeeId = body.attendee ? body.attendee.id : (body.data && body.data.id)
    }).then(() => api.fetchQrToken(world))
  ).then(() => {
    expect(world.qrToken, 'Could not obtain a QR token for the attendee').to.exist
  })
})

Given('I am at turnstile {string}', (turnstile = DEFAULT_TURNSTILE) => {
  if (!world.turnstileId) return api.ensureTurnstile(world, turnstile)
  world.turnstileName = turnstile
})

Given(/^I scan a valid QR badge for attendee (.+)$/, () => {
  if (!world.qrToken) return api.fetchQrToken(world, world.attendeeId)
})

Given(/^I scan the valid QR badge for attendee (.+)$/, () => {
  if (!world.qrToken) return api.fetchQrToken(world, world.attendeeId)
})

Given('I scan an invalid QR token', () => {
  world.qrToken = '1|abc123|invalid_signature'
})

When('I attempt to check-in the attendee', () => {
  return scanEventCount(world).then((count) => {
    world.scanEventCount = count
    return api.post('/access/checkin', {
      qr_token: world.qrToken,
      turnstile_id: world.turnstileId,
      turnstile: world.turnstileName,
    }, world.authToken)
  }).then((resp) => {
    world.lastResponse = resp
  })
})

When('I attempt to check-in the attendee again', () => {
  return scanEventCount(world).then((count) => {
    world.scanEventCount = count
    return api.post('/access/checkin', {
      qr_token: world.qrToken,
      turnstile_id: world.turnstileId,
      turnstile: world.turnstileName,
    }, world.authToken)
  }).then((resp) => {
    world.lastResponse = resp
  })
})

When('I attempt to check-out the attendee', () => {
  return scanEventCount(world).then((count) => {
    world.scanEventCount = count
    return api.post('/access/checkout', {
      qr_token: world.qrToken,
      turnstile_id: world.turnstileId,
      turnstile: world.turnstileName,
    }, world.authToken)
  }).then((resp) => {
    world.lastResponse = resp
  })
})

When('I attempt to process the scan', () => {
  return scanEventCount(world).then((count) => {
    world.scanEventCount = count
    return api.post('/access/checkin', {
      qr_token: world.qrToken,
      turnstile_id: world.turnstileId,
      turnstile: world.turnstileName,
    }, world.authToken)
  }).then((resp) => {
    world.lastResponse = resp
  })
})

Then('the check-in should succeed', () => {
  expect(world.lastResponse.status, `Check-in failed: ${world.lastResponse.body}`).to.eq(200)
  const body = api.body(world.lastResponse)
  expect(body.success, `Check-in did not report success: ${body}`).to.eq(true)
})

Then('the check-out should succeed', () => {
  expect(world.lastResponse.status, `Check-out failed: ${world.lastResponse.body}`).to.eq(200)
  const body = api.body(world.lastResponse)
  expect(body.success, `Check-out did not report success: ${body}`).to.eq(true)
})

Then('the check-in should fail', () => {
  expect(world.lastResponse.status, `Expected the check-in to fail, got ${world.lastResponse.status} - ${world.lastResponse.body}`).to.be.gte(400)
  const body = api.body(world.lastResponse)
  expect(body.success, `Check-in reported success: ${body}`).to.not.eq(true)
})

Then('the check-out should fail', () => {
  expect(world.lastResponse.status, `Expected the check-out to fail, got ${world.lastResponse.status} - ${world.lastResponse.body}`).to.be.gte(400)
  const body = api.body(world.lastResponse)
  expect(body.success, `Check-out reported success: ${body}`).to.not.eq(true)
})

Then('the operation should fail', () => {
  expect(world.lastResponse.status, `Expected the scan to fail, got ${world.lastResponse.status} - ${world.lastResponse.body}`).to.be.gte(400)
  const body = api.body(world.lastResponse)
  expect(body.success, `Scan reported success: ${body}`).to.not.eq(true)
})

// Free text: "I should receive an error about invalid check-out attempt"
Then('the attendee status should be updated to {string}', (status) => {
  return api.get(`/access/attendees/${world.attendeeId}/status`, world.authToken).then((resp) => {
    expect(resp.status, `Could not read the attendee status: ${resp.body}`).to.eq(200)
    const body = api.body(resp)
    expect(body.status, `Expected attendee status ${JSON.stringify(status)}, got ${body.status}`).to.eq(status)
  })
})

Then('a scan event should be recorded with type {string}', (eventType = 'CHECK_IN') => {
  return api.get('/access/scan-events', world.authToken, { attendee_id: world.attendeeId, event_type: eventType }).then((resp) => {
    expect(resp.status, `Could not read the scan events: ${resp.body}`).to.eq(200)
    const body = api.body(resp)
    const events = body.scan_events || []
    expect(events, `No scan event of type ${eventType} was recorded: ${body}`).to.be.an('array')
    expect(events.length, `Expected at least one scan event of type ${eventType}: ${events.length}`).to.be.gte(1)
  })
})

Then('the operation should be idempotent \\(scanning again should not create duplicate events\\)', () => {
  return api.get('/access/scan-events', world.authToken, { attendee_id: world.attendeeId, event_type: 'CHECK_IN' }).then((resp) => {
    const before = api.body(resp).scan_events.length
    return api.post('/access/checkin', {
      qr_token: world.qrToken,
      turnstile_id: world.turnstileId,
      turnstile: world.turnstileName,
    }, world.authToken).then(() => {
      return api.get('/access/scan-events', world.authToken, { attendee_id: world.attendeeId, event_type: 'CHECK_IN' }).then((resp) => {
        const after = api.body(resp).scan_events.length
        expect(after, `A repeated scan created a duplicate event: ${before} -> ${after}`).to.eq(before)
      })
    })
  })
})

Then('no scan event should be recorded', () => {
  return api.get('/access/scan-events', world.authToken, { attendee_id: world.attendeeId }).then((resp) => {
    const body = api.body(resp)
    const events = body.scan_events || []
    expect(events.length, `A scan event was recorded: ${JSON.stringify(events)}`).to.eq(0)
  })
})

Then('no new scan event should be recorded', () => {
  return api.get('/access/scan-events', world.authToken, { attendee_id: world.attendeeId }).then((resp) => {
    const events = api.body(resp).scan_events || []
    expect(events.length, `A new scan event was recorded: ${world.scanEventCount} -> ${events.length}`).to.eq(world.scanEventCount)
  })
})

Then('exactly one scan event should be recorded', () => {
  return api.get('/access/scan-events', world.authToken, { attendee_id: world.attendeeId }).then((resp) => {
    const events = api.body(resp).scan_events || []
    expect(events.length, `Expected exactly one scan event, got ${events.length}: ${JSON.stringify(events)}`).to.eq(1)
  })
})

Then(/^I should receive an error about (.+)$/, (errorAbout) => {
  const body = api.body(world.lastResponse)
  expect(world.lastResponse.status, `Expected an error status, got ${world.lastResponse.status} - ${world.lastResponse.body}`).to.be.gte(400)
  const message = String(body.message || '').toLowerCase()
  const expected = errorAbout.toLowerCase().replace(/"/g, '').trim()
  expect(message, `Expected a message about ${JSON.stringify(expected)}, got: ${message}`).to.include(expected)
})

// --- Check-out scenarios ----------------------------------------------------

Given(/^an attendee (.+) is currently checked-in$/, () => {
  return _checkIn(world)
})

Given(/^an attendee (.+) has never been checked-in$/, () => {
  if (!world.qrToken) return api.fetchQrToken(world, world.attendeeId)
  return api.get(`/access/attendees/${world.attendeeId}/status`, world.authToken).then((resp) => {
    const body = api.body(resp)
    expect(body.checkin_at, `The attendee was already checked in: ${body}`).to.be.null
  })
})

Given(/^an attendee (.+) is already checked-in$/, () => {
  return _checkIn(world)
})

function _checkIn(w) {
  if (!w.qrToken) return api.fetchQrToken(w, w.attendeeId).then(() => _doCheckIn(w))
  return _doCheckIn(w)
}

function _doCheckIn(w) {
  return api.post('/access/checkin', {
    qr_token: w.qrToken,
    turnstile_id: w.turnstileId,
    turnstile: w.turnstileName,
  }, w.authToken).then((resp) => {
    expect(resp.status, `Could not check the attendee in: ${resp.body}`).to.eq(200)
  })
}

// How many scan events the attendee has so far. The count is resolved inside
// the chain — assigning the chain itself to `world.scanEventCount` would
// leave a Cypress chainable in the world instead of a number.
function scanEventCount(w) {
  return api.get('/access/scan-events', w.authToken, { attendee_id: w.attendeeId }).then((resp) => {
    if (resp.status !== 200) return 0
    return api.body(resp).scan_events.length
  })
}

// --- Race condition ---------------------------------------------------------

Given('multiple staff members attempt to check-in the same attendee simultaneously', () => {
  expect(world.qrToken, 'No QR token available for the race scenario').to.exist
})

When('they scan the QR badge at the same time', () => {
  const payload = {
    qr_token: world.qrToken,
    turnstile_id: world.turnstileId,
    turnstile: world.turnstileName,
  }
  const promises = []
  for (let i = 0; i < 5; i++) {
    // postParallel returns a real promise, so these five scans are genuinely
    // concurrent — that is the whole point of this scenario.
    promises.push(api.postParallel('/access/checkin', payload, world.authToken))
  }
  // The handler has to hand back a Cypress chain, not the native promise that
  // Promise.all returns, so the wait happens inside a cy.wrap() seed.
  return cy.wrap(null).then(() => Promise.all(promises)).then((responses) => {
    world.raceResponses = responses
    world.lastResponse = responses[0]
  })
})

Then('only one check-in should succeed', () => {
  const successes = world.raceResponses.filter((r) => r.status === 200 && api.body(r).success === true)
  expect(successes.length, `Expected exactly one successful check-in, got ${successes.length}`).to.eq(1)
})

Then('the others should receive appropriate errors', () => {
  const failures = world.raceResponses.filter((r) => r.status >= 400)
  expect(failures.length, `Expected every other scan to fail, got ${JSON.stringify(world.raceResponses.map((r) => r.status))}`).to.eq(world.raceResponses.length - 1)
})