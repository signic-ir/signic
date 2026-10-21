// cypress-bdd/features/registration/attendee_registration.js
//
// Step definitions for the Registration module feature, mirroring
// `/home/hamid/bdd-test/step_definitions/registration_attendee_steps.py` exactly.
//
// v28 uses Cucumber Expressions natively.
import { Given, When, Then } from '@badeball/cypress-cucumber-preprocessor'
import world from '../../support/world'
import api from '../../support/api'
import { registerBeforeHooks } from '../../support/setup'

registerBeforeHooks()

Given('the system is running', () => {})

Given('I have access to the Registration module', () => {})

Given('there is an active event {string}', (eventName = 'Tech Conference 2024') => {
  return api.ensureEvent(world, eventName)
})

Given('I am an authenticated event organizer', () => {
  expect(world.authToken, 'No authentication token available').to.exist
  return api.ensureEventOrganizer(world)
})

When('I register an attendee with the following details:', (datatable) => {
  const details = api.tableToDict(datatable)
  const payload = {
    first_name: details.first_name,
    last_name: details.last_name,
    name: `${details.first_name} ${details.last_name}`.trim(),
    email: details.email,
    phone: details.phone,
    ticket_type: details.ticket_type,
    event_id: world.eventId,
    event_name: world.eventName,
  }
  return api.post('/registration/attendees', payload, world.authToken).then((resp) => {
    world.lastResponse = resp
    if (resp.status === 200 || resp.status === 201) {
      const body = api.body(resp)
      world.attendeeId = body.attendee ? body.attendee.id : (body.data && body.data.id)
      world.qrToken = body.qr_token
    }
  })
})

When('I associate the attendee with event {string}', (eventName = 'Tech Conference 2024') => {
  expect(world.eventName).to.eq(eventName)
})

Then('the attendee should be created successfully', () => {
  expect(world.lastResponse.status, `Attendee registration failed: ${world.lastResponse.body}`).to.be.oneOf([200, 201])
  const body = api.body(world.lastResponse)
  const attendee = body.attendee
  expect(attendee, `No attendee in the response: ${body}`).to.exist
  expect(attendee.id, `No attendee id in the response: ${body}`).to.exist
  world.attendeeId = attendee.id
})

Then('the attendee status should be {string}', (status) => {
  const body = api.body(world.lastResponse)
  const attendee = body.attendee || {}
  expect(attendee.status).to.eq(status)
})

Then('a QR badge should be generated for the attendee', () => {
  const body = api.body(world.lastResponse)
  const token = body.qr_token
  expect(token, `No QR token in the response: ${body}`).to.exist
  expect(token).to.include('|')

  return api.get(`/registration/attendees/${world.attendeeId}/qr`, world.authToken).then((badge) => {
    expect(badge.status, `QR badge lookup failed: ${badge.body}`).to.eq(200)
    expect(api.body(badge).qr_token, `No QR token in badge response: ${badge.body}`).to.exist
  })
})

Then("a print job should be queued for the attendee's badge", () => {
  const attendee = api.body(world.lastResponse).attendee || {}
  expect(attendee.print_jobs, `No print jobs recorded for the attendee: ${JSON.stringify(attendee)}`).to.exist
})

// Duplicate-attendee setup — uses a fresh unique email so DB state doesn't leak between runs.
Given('an attendee with email {string} already exists for event {string}', (email, eventName = 'Tech Conference 2024') => {
  return api.ensureEvent(world, eventName).then(() => {
    const unique = api.uniqueSuffix().slice(0, 10)
    const scenarioEmail = `duplicate.${unique}@example.com`
    world.duplicateEmail = scenarioEmail
    return api.registerAttendee(world, {
      first_name: 'John',
      last_name: 'Doe',
      email: scenarioEmail,
      phone: `+9895${api.uniqueDigits(9)}`,
      ticket_type: 'GENERAL',
      eventId: world.eventId,
      eventName: world.eventName,
    }).then((body) => {
      expect(body.attendee ? body.attendee.id : (body.data && body.data.id), 'Could not create the pre-existing attendee').to.exist
    })
  })
})

When('I try to register another attendee with email {string} for the same event', (email) => {
  const useEmail = world.duplicateEmail || email
  const payload = {
    first_name: 'Jane',
    last_name: 'Roe',
    name: 'Jane Roe',
    email: useEmail,
    phone: `+9895${api.uniqueDigits(9)}`,
    ticket_type: 'GENERAL',
    event_id: world.eventId,
    event_name: world.eventName,
  }
  return api.post('/registration/attendees', payload, world.authToken).then((resp) => {
    world.lastResponse = resp
  })
})

When('I try to register an attendee with an invalid ticket type {string}', (ticketType) => {
  const payload = {
    first_name: 'Bad',
    last_name: 'Ticket',
    name: 'Bad Ticket',
    email: `bad.ticket.${api.uniqueSuffix().slice(0, 8)}@example.com`,
    phone: `+9895${api.uniqueDigits(9)}`,
    ticket_type: ticketType,
    event_id: world.eventId,
    event_name: world.eventName,
  }
  return api.post('/registration/attendees', payload, world.authToken).then((resp) => {
    world.lastResponse = resp
  })
})

Then('I should receive a validation error about duplicate email', () => {
  expect(world.lastResponse.status, `Expected a 422 validation error, got ${world.lastResponse.status} - ${world.lastResponse.body}`).to.eq(422)
  const body = api.body(world.lastResponse)
  const errors = body.errors || {}
  expect(errors.email, `No email validation error: ${body}`).to.exist
  const msg = Array.isArray(errors.email) ? errors.email.join(' ') : String(errors.email)
  expect(msg.toLowerCase()).to.satisfy((s) => s.includes('taken') || s.includes('duplicate'))
})

Then('no new attendee should be created', () => {
  expect(world.lastResponse.status).to.eq(422)
  expect('attendee' in api.body(world.lastResponse)).to.be.false
})

Then('I should receive a validation error about invalid ticket type', () => {
  expect(world.lastResponse.status, `Expected a 422 validation error, got ${world.lastResponse.status} - ${world.lastResponse.body}`).to.eq(422)
  const body = api.body(world.lastResponse)
  const errors = body.errors || {}
  expect(errors.ticket_type, `No ticket_type validation error: ${body}`).to.exist
})

Then('no attendee should be created', () => {
  expect(world.lastResponse.status).to.eq(422)
  expect('attendee' in api.body(world.lastResponse)).to.be.false
})

// --- QR token scenarios ----------------------------------------------------

Given('an attendee exists with ID {int}', (attendeeId = 1) => {
  return api.get(`/registration/attendees/${attendeeId}`, world.authToken).then((resp) => {
    if (resp.status === 200) {
      world.attendeeId = attendeeId
      return
    }
    return api.registerAttendee(world, {
      first_name: 'John',
      last_name: 'Doe',
      email: `attendee${attendeeId}.${api.uniqueSuffix().slice(0, 8)}@example.com`,
      phone: `+9895${api.uniqueDigits(9)}`,
      ticket_type: 'GENERAL',
      eventId: world.eventId,
      eventName: world.eventName,
    }).then((body) => {
      world.attendeeId = body.attendee ? body.attendee.id : (body.data && body.data.id)
      expect(world.attendeeId, 'Could not create the attendee the scenario needs').to.exist
    })
  })
})

When('I generate a QR token for the attendee', () => {
  return api.get(`/registration/attendees/${world.attendeeId}/qr`, world.authToken).then((resp) => {
    world.lastResponse = resp
    if (resp.status === 200) world.qrToken = api.body(resp).qr_token
  })
})

Then('I should receive a token in the format {string}', (fmt = 'attendee_id|random_string|signature') => {
  expect(world.lastResponse.status, `QR generation failed: ${world.lastResponse.body}`).to.eq(200)
  const token = api.body(world.lastResponse).qr_token
  expect(token, `No QR token in the response: ${world.lastResponse.body}`).to.exist
  const parts = token.split('|')
  expect(parts.length).to.eq(3)
  expect(parts[0]).to.eq(String(world.attendeeId))
  expect(parts[1]).to.have.length.greaterThan(0)
  expect(parts[2]).to.have.length.greaterThan(0)
})

Then('the token should be cryptographically secure', () => {
  const randomPart = world.qrToken.split('|')[1]
  expect(randomPart.length).to.be.gte(16)
  expect(new Set(randomPart.split('')).size).to.be.greaterThan(1)
})

Then('the token should be unique for this attendee', () => {
  return api.fetchQrToken(world, world.attendeeId).then((first) => {
    return api.fetchQrToken(world, world.attendeeId).then((second) => {
      expect(first).to.not.eq(second)
    })
  })
})

Given('a valid QR token {string} exists for attendee ID {int}', (token, attendeeId = 1) => {
  return api.get(`/registration/attendees/${attendeeId}/qr`, world.authToken).then((resp) => {
    expect(resp.status).to.eq(200)
    world.qrToken = api.body(resp).qr_token
    expect(world.qrToken, 'Could not obtain a valid QR token').to.exist
    world.attendeeId = attendeeId
  })
})

Given('I have an invalid QR token {string}', (token = '1|abc123|invalid_signature') => {
  world.invalidQrToken = token
  world.attendeeId = 1
})

When('I validate the QR token {string}', (token) => {
  return api.post('/registration/qr/validate', { token: world.qrToken }, world.authToken).then((resp) => {
    world.lastResponse = resp
  })
})

When('I validate the invalid QR token', () => {
  return api.post('/registration/qr/validate', { token: world.invalidQrToken }, world.authToken).then((resp) => {
    world.lastResponse = resp
  })
})

Then('I should get the attendee ID {int} as validation result', (attendeeId) => {
  expect(world.lastResponse.status, `Validation failed: ${world.lastResponse.body}`).to.eq(200)
  const body = api.body(world.lastResponse)
  expect(body.valid, `Token was not accepted as valid: ${body}`).to.eq(true)
  expect(body.attendee_id, `Expected attendee id ${attendeeId}, got ${body.attendee_id}`).to.eq(attendeeId)
})

Then('the token should be marked as validated', () => {
  const body = api.body(world.lastResponse)
  expect(body.valid, `Token was not accepted as valid: ${body}`).to.eq(true)
  expect(body.validated, `Token was not marked as validated: ${body}`).to.eq(true)
})

Then('I should get a validation error', () => {
  expect(world.lastResponse.status, `Expected an error status, got ${world.lastResponse.status} - ${world.lastResponse.body}`).to.be.gte(400)
  const body = api.body(world.lastResponse)
  expect(body.attendee_id ?? null, `An attendee id was returned for an invalid token: ${body}`).to.be.null
  expect(body.valid, `The token was reported as valid: ${body}`).to.not.eq(true)
})

Then('no attendee ID should be returned', () => {
  const body = api.body(world.lastResponse)
  expect(body.attendee_id ?? null, `An attendee id was returned: ${body}`).to.be.null
})

Given('a QR token was generated more than 24 hours ago', () => {
  // pytest-bdd's step creates attendee ID 1 first — the QR endpoints are
  // addressed by attendee id, so it has to exist.
  return api.get('/registration/attendees/1', world.authToken).then((resp) => {
    if (resp.status === 200) {
      world.attendeeId = 1
    } else {
      return api.registerAttendee(world, {
        first_name: 'John',
        last_name: 'Doe',
        email: `expired.qr.${api.uniqueSuffix().slice(0, 8)}@example.com`,
        phone: `+9895${api.uniqueDigits(9)}`,
        ticket_type: 'GENERAL',
        eventId: world.eventId,
        eventName: world.eventName,
      }).then((body) => {
        world.attendeeId = body.attendee ? body.attendee.id : (body.data && body.data.id)
        expect(world.attendeeId, 'Could not create the attendee the scenario needs').to.exist
      })
    }
  }).then(() => api.get(`/registration/attendees/${world.attendeeId}/qr`, world.authToken)).then((resp) => {
    expect(resp.status, `Could not get a QR token: ${resp.body}`).to.eq(200)
    world.qrToken = api.body(resp).qr_token
    return api.post(`/registration/attendees/${world.attendeeId}/qr/expire`, {}, world.authToken).then((expireResp) => {
      expect(expireResp.status, `Could not age the QR token: ${expireResp.body}`).to.be.oneOf([200, 201, 204])
    })
  })
})

When('I try to validate the expired QR token', () => {
  return api.post('/registration/qr/validate', { token: world.qrToken }, world.authToken).then((resp) => {
    world.lastResponse = resp
  })
})

Then('I should get an expiration error', () => {
  expect(world.lastResponse.status, `Expected an error status, got ${world.lastResponse.status} - ${world.lastResponse.body}`).to.be.gte(400)
  const body = api.body(world.lastResponse)
  const message = String(body.message || '').toLowerCase()
  expect(message, `Expected an expiration error, got: ${body}`).to.include('expire')
  expect(body.attendee_id ?? null, `An attendee id was returned for an expired token: ${body}`).to.be.null
})