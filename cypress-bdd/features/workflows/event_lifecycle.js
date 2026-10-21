// cypress-bdd/features/workflows/event_lifecycle.js
//
// Step definitions for the cross-module workflow feature, mirroring
// `/home/hamid/bdd-test/step_definitions/cross_module_steps.py` exactly.
//
// v28 uses Cucumber Expressions natively. Free‑text steps use RegularExpression.
import { Given, When, Then } from '@badeball/cypress-cucumber-preprocessor'
import world from '../../support/world'
import api from '../../support/api'
import { registerBeforeHooks } from '../../support/setup'

registerBeforeHooks()

const DEFAULT_EVENT = 'Tech Conference 2024'
const DEFAULT_EXHIBITOR = 'Acme Corp'
const DEFAULT_BOOTH = 'Acme Corp Booth'
const DEFAULT_TURNSTILE = 'Main Gate'

Given('the system is running', () => {})

Given('I have access to all modules', () => {})

Given('there is an active event {string}', (eventName = DEFAULT_EVENT) => {
  return api.ensureEvent(world, eventName).then(() => api.ensureEventOrganizer(world))
})

Given('there is an exhibitor {string} with a booth at the event', (exhibitorName = DEFAULT_EXHIBITOR) => {
  return api.ensureEvent(world, DEFAULT_EVENT)
    .then(() => api.ensureExhibitor(world, exhibitorName))
    .then(() => api.ensureBooth(world, DEFAULT_BOOTH))
})

Given('there are turnstiles configured at all entrances', () => {
  return api.ensureTurnstile(world, DEFAULT_TURNSTILE)
})

Given('I am an unauthenticated user', () => {
  world.authToken = null
})

When('I request an OTP for phone number {string}', (phone = '+989123456789') => {
  return api.post('/api/identity/otp/request', { phone }).then((resp) => {
    world.lastResponse = resp
    world.otpPhone = phone
  })
})

When('I receive OTP code {string}', (code = '123456') => {
  return api.fetchOtp(world.otpPhone).then((fetched) => {
    expect(fetched, `No OTP was issued for ${world.otpPhone}`).to.exist
    world.otpCode = fetched
  })
})

When('I verify the OTP code {string}', (code = '123456') => {
  return api.post('/api/identity/otp/verify', {
    phone: world.otpPhone,
    otp: world.otpCode || code,
  }).then((resp) => {
    world.lastResponse = resp
    if (resp.status === 200) {
      world.authToken = api.body(resp).access_token
      return api.ensureEventOrganizer(world)
    }
  })
})

Then('I should be authenticated as a user', () => {
  expect(world.lastResponse.status, `Authentication failed: ${world.lastResponse.body}`).to.eq(200)
  expect(api.body(world.lastResponse).access_token, 'No access token').to.exist
})

Given('I am now an authenticated event organizer', () => {
  expect(world.authToken, 'No authentication token available').to.exist
  return api.ensureEventOrganizer(world)
})

When('I register an attendee with:', (datatable) => {
  const details = api.tableToDict(datatable)
  // The database survives between runs, so the contact details are made
  // unique per run — otherwise the journey would resume with an attendee
  // that a previous run already checked in.
  const unique = api.uniqueSuffix().slice(0, 8)
  const payload = {
    first_name: details.first_name,
    last_name: details.last_name,
    name: `${details.first_name} ${details.last_name}`.trim(),
    email: `${details.first_name.toLowerCase()}.${unique}@example.com`,
    phone: `+9899${api.uniqueDigits(8)}`,
    ticket_type: details.ticket_type,
    event_id: world.eventId,
    event_name: world.eventName,
  }
  return api.post('/registration/attendees', payload, world.authToken).then((resp) => {
    world.lastResponse = resp
    if (resp.status === 200 || resp.status === 201) {
      const body = api.body(resp)
      world.attendeeId = body.attendee ? body.attendee.id : (body.data && body.data.id)
      world.attendeeName = payload.name
    }
  })
})

When('I associate the attendee with event {string}', (eventName = DEFAULT_EVENT) => {
  expect(world.eventName).to.eq(eventName)
})

Then('the attendee should be created successfully', () => {
  expect(world.lastResponse.status, `Attendee registration failed: ${world.lastResponse.body}`).to.be.oneOf([200, 201])
  const attendee = api.body(world.lastResponse).attendee || {}
  expect(attendee.id, `No attendee id in the response: ${world.lastResponse.body}`).to.exist
})

Then('a QR badge should be generated for the attendee', () => {
  const body = api.body(world.lastResponse)
  expect(body.qr_token, `No QR token in the response: ${world.lastResponse.body}`).to.exist
})

Then('a print job should be queued', () => {
  const attendee = api.body(world.lastResponse).attendee || {}
  expect(attendee.print_jobs, `No print jobs recorded for the attendee: ${JSON.stringify(attendee)}`).to.exist
})

// Free text: "the attendee Jane Smith has a valid QR badge"
Given(/^the attendee (.+) has a valid QR badge$/, (attendeeName = 'Jane Smith') => {
  world.attendeeName = attendeeName
  return api.fetchQrToken(world, world.attendeeId).then((token) => {
    expect(token, 'Could not obtain a QR token').to.exist
  })
})

When('I scan the QR badge at turnstile {string} for check-in', (turnstile = DEFAULT_TURNSTILE) => {
  return api.ensureTurnstile(world, turnstile).then(() => {
    return api.post('/access/checkin', {
      qr_token: world.qrToken,
      turnstile_id: world.turnstileId,
      turnstile: world.turnstileName,
    }, world.authToken).then((resp) => {
      world.lastResponse = resp
    })
  })
})

When('I scan the QR badge at turnstile {string} for check-out', (turnstile = DEFAULT_TURNSTILE) => {
  return api.ensureTurnstile(world, turnstile).then(() => {
    return api.post('/access/checkout', {
      qr_token: world.qrToken,
      turnstile_id: world.turnstileId,
      turnstile: world.turnstileName,
    }, world.authToken).then((resp) => {
      world.lastResponse = resp
    })
  })
})

Then('the check-in should succeed', () => {
  expect(world.lastResponse.status, `Check-in failed: ${world.lastResponse.body}`).to.eq(200)
  expect(api.body(world.lastResponse).success, `Check-in did not succeed: ${world.lastResponse.body}`).to.eq(true)
})

Then('the check-out should succeed', () => {
  expect(world.lastResponse.status, `Check-out failed: ${world.lastResponse.body}`).to.eq(200)
  expect(api.body(world.lastResponse).success, `Check-out did not succeed: ${world.lastResponse.body}`).to.eq(true)
})

Then('the attendee status should be {string}', (status) => {
  return api.get(`/access/attendees/${world.attendeeId}/status`, world.authToken).then((resp) => {
    expect(resp.status, `Could not read the attendee status: ${resp.body}`).to.eq(200)
    expect(api.body(resp).status, `Unexpected status: ${resp.body}`).to.eq(status)
  })
})

// Free text: "the attendee Jane Smith is checked-in"
Given(/^the attendee (.+) is checked-in$/, (attendeeName = 'Jane Smith') => {
  world.attendeeName = attendeeName
})

When('I visit exhibitor {string} booth', (exhibitorName = DEFAULT_EXHIBITOR) => {
  return api.ensureExhibitor(world, exhibitorName)
    .then(() => api.ensureBooth(world, DEFAULT_BOOTH))
    .then(() => {
      // The organiser stands at the exhibitor's booth, so the caller has to be a
      // representative of that exhibitor before a lead may be captured for it.
      // The exhibitor is reused across runs, so its members belong to whoever
      // joined it first — the join has to be repeated for the current caller.
      return currentUserId(world).then((uid) => {
        world.userId = uid
        return api.post(`/exhibition/exhibitors/${world.exhibitorId}/members`, {
          user_id: uid,
          role: 'representative',
          is_active: true,
        }, world.authToken)
      })
    }).then((members) => {
      expect(members.status, `Could not join the exhibitor ${JSON.stringify(exhibitorName)}: ${members.body}`).to.be.oneOf([200, 201])
    })
})

// Free text: "I capture a lead for attendee Jane Smith with:"
When(/^I capture a lead for attendee (.+) with:$/, (attendeeName, datatable) => {
  const details = api.tableToDict(datatable)
  const payload = {
    attendee_id: world.attendeeId,
    exhibitor_id: world.exhibitorId,
    booth_id: world.boothId,
    interest_level: details.interest_level,
    notes: details.notes,
  }
  return api.post('/exhibition/leads', payload, world.authToken).then((resp) => {
    world.lastResponse = resp
    if (resp.status === 200 || resp.status === 201) {
      world.leadId = api.body(resp).lead.id
      world.lead = api.body(resp).lead
    }
  })
})

When('I associate the lead with booth {string}', (boothName = DEFAULT_BOOTH) => {
  if (!world.boothId) return api.ensureBooth(world, boothName)
  return api.put(`/exhibition/leads/${world.leadId}`, { booth_id: world.boothId }, world.authToken).then((resp) => {
    world.lastResponse = resp
  })
})

Then('the lead should be created successfully', () => {
  expect(world.lead, 'No lead was created').to.exist
  expect(world.lead.id, `No lead id in the response: ${JSON.stringify(world.lead)}`).to.exist
})

Then('the lead should be associated with the correct attendee', () => {
  expect(world.lead.attendee_id, `Expected attendee ${world.attendeeId}, got ${world.lead.attendee_id}`).to.eq(world.attendeeId)
})

// --- Analytics -------------------------------------------------------------

Given('multiple attendees have registered for event {string}', (eventName = DEFAULT_EVENT) => {
  return api.ensureEvent(world, eventName).then(() => {
    // Chain the registrations: a bare loop would enqueue the requests but
    // return before they ran, so the scenario would assert on stale state.
    return api.chainAll([...Array(3).keys()].map((index) => () => {
      return api.registerAttendee(world, {
        first_name: `Guest${index}`,
        last_name: 'Visitor',
        email: `guest${index}.${api.uniqueSuffix().slice(0, 8)}@example.com`,
        phone: `+9895${api.uniqueDigits(9)}`,
        ticket_type: index ? 'VIP' : 'GENERAL',
      })
    }))
  })
})

Given('multiple attendees have checked-in and checked-out', () => {
  return api.ensureTurnstile(world, DEFAULT_TURNSTILE).then(() => {
    return api.get('/registration/attendees', world.authToken, { event_id: world.eventId }).then((resp) => {
      const attendees = api.body(resp).attendees || []
      const chain = api.chainAll(attendees.slice(0, 2).map((attendee) => () => {
        return api.fetchQrToken(world, attendee.id).then((token) => {
          return api.post('/access/checkin', {
            qr_token: token,
            turnstile_id: world.turnstileId,
          }, world.authToken).then(() => {
            return api.post('/access/checkout', {
              qr_token: token,
              turnstile_id: world.turnstileId,
            }, world.authToken)
          })
        })
      }))
      return chain
    })
  })
})

Given('multiple leads have been captured by exhibitors', () => {
  return api.ensureEvent(world, DEFAULT_EVENT)
    .then(() => api.ensureEventOrganizer(world))
    .then(() => api.ensureExhibitor(world, DEFAULT_EXHIBITOR))
    .then(() => api.ensureBooth(world, DEFAULT_BOOTH))
    .then(() => currentUserId(world).then((uid) => {
      world.userId = uid
      return api.post(`/exhibition/exhibitors/${world.exhibitorId}/members`, {
        user_id: uid,
        role: 'representative',
        is_active: true,
      }, world.authToken)
    })).then((members) => {
      expect(members.status, `Could not join the exhibitor: ${members.body}`).to.be.oneOf([200, 201])
      return api.get('/registration/attendees', world.authToken, { event_id: world.eventId })
    }).then((resp) => {
      const attendees = api.body(resp).attendees || []
      const chain = api.chainAll(attendees.slice(0, 2).map((attendee) => () => {
        return api.post('/exhibition/leads', {
          attendee_id: attendee.id,
          exhibitor_id: world.exhibitorId,
          booth_id: world.boothId,
          interest_level: 'HIGH',
          notes: 'Interested in the enterprise license',
        }, world.authToken).then((leadResp) => {
          expect(leadResp.status, `Could not capture the lead: ${leadResp.body}`).to.be.oneOf([200, 201])
        })
      }))
      return chain
    })
})

When('I generate an event report', () => {
  return api.get(`/registration/events/${world.eventId}/report`, world.authToken).then((resp) => {
    world.lastResponse = resp
  })
})

Then('I should see:', (datatable) => {
  const required = {
    'Total registered attendees': 'total_registered_attendees',
    'Total checked-in attendees': 'total_checked_in_attendees',
    'Total leads captured': 'total_leads_captured',
    'Conversion rate from registration to check-in': 'conversion_rate',
    'Leads per exhibitor breakdown': 'leads_per_exhibitor',
    'Popular ticket types': 'popular_ticket_types',
  }
  return api.get(`/registration/events/${world.eventId}/report`, world.authToken).then((resp) => {
    expect(resp.status, `Report failed: ${resp.status} - ${resp.body}`).to.eq(200)
    const body = api.body(resp)
    const report = body.report || body
    for (const label of Object.keys(required)) {
      const key = required[label]
      expect(report[key] !== undefined, `The report has no ${JSON.stringify(label)} (${key}): ${resp.body}`).to.eq(true)
    }
  })
})

// --- Cleanup ---------------------------------------------------------------

Given('event {string} has ended', (eventName = DEFAULT_EVENT) => {
  return api.ensureEvent(world, eventName).then(() => {
    // A historical attendee, so that "historical data should be preserved"
    // has something to assert about.
    return api.registerAttendee(world, {
      first_name: 'Historic',
      last_name: 'Visitor',
      email: `historic.${api.uniqueSuffix().slice(0, 8)}@example.com`,
      phone: `+9895${api.uniqueDigits(9)}`,
      ticket_type: 'GENERAL',
      eventId: world.eventId,
      eventName: world.eventName,
    })
  }).then((body) => {
    expect(body.attendee ? body.attendee.id : (body.data && body.data.id), 'Could not register the historical attendee').to.exist
    world.attendeeId = body.attendee ? body.attendee.id : (body.data && body.data.id)
  })
})

When('I run the expired event cleanup process', () => {
  return api.post('/registration/cleanup-expired-events', {}, world.authToken).then((resp) => {
    world.lastResponse = resp
  })
})

Then('events older than 30 days should be archived', () => {
  expect(world.lastResponse.status, `Cleanup failed: ${world.lastResponse.body}`).to.eq(200)
  expect('archived_events' in api.body(world.lastResponse), `No archived event count: ${world.lastResponse.body}`).to.eq(true)
})

Then('expired QR tokens should be invalidated', () => {
  expect('invalidated_qr_tokens' in api.body(world.lastResponse), `No invalidated QR token count: ${world.lastResponse.body}`).to.eq(true)
})

Then('temporary data should be cleaned up', () => {
  expect('cleaned_temporary_data' in api.body(world.lastResponse), `No cleanup count: ${world.lastResponse.body}`).to.eq(true)
})

Then('historical data should be preserved for reporting', () => {
  const body = api.body(world.lastResponse)
  expect('preserved_records' in body, `No preserved record counts: ${world.lastResponse.body}`).to.eq(true)
  return api.get(`/registration/attendees/${world.attendeeId}`, world.authToken).then((attendee) => {
    expect(attendee.status, `The attendee was deleted: ${attendee.body}`).to.eq(200)
  })
})

function currentUserId(w) {
  return api.get('/shared/users/me', w.authToken).then((resp) => {
    const b = api.body(resp)
    return b.user_id || (b.user && b.user.id) || 1
  })
}