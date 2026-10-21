// cypress-bdd/features/exhibition/lead_capture.js
//
// Step definitions for the Exhibition module feature, mirroring
// `/home/hamid/bdd-test/step_definitions/exhibition_lead_steps.py` exactly.
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

Given('the system is running', () => {})

Given('I have access to the Exhibition module', () => {})

Given('there is an active event {string}', (eventName = DEFAULT_EVENT) => {
  return api.ensureEvent(world, eventName)
})

Given('there is an exhibitor {string} with a booth at the event', (exhibitorName = DEFAULT_EXHIBITOR) => {
  return api.ensureEvent(world, DEFAULT_EVENT)
    .then(() => api.ensureEventOrganizer(world))
    .then(() => api.ensureExhibitor(world, exhibitorName))
    .then(() => api.ensureBooth(world, DEFAULT_BOOTH))
})

Given('there is an attendee {string} registered for the event', (attendeeName = 'John Doe') => {
  const [firstName, , lastName] = attendeeName.split(' ')
  const unique = api.uniqueSuffix().slice(0, 8)
  return api.registerAttendee(world, {
    first_name: firstName || 'John',
    last_name: lastName || 'Doe',
    email: `john.doe.${unique}@example.com`,
    phone: `+9895${api.uniqueDigits(9)}`,
    ticket_type: 'GENERAL',
  }).then((body) => {
    expect(body.attendee ? body.attendee.id : (body.data && body.data.id), 'Could not register the attendee').to.exist
    world.attendeeId = body.attendee ? body.attendee.id : (body.data && body.data.id)
  })
})

// The lead endpoints only accept a caller who is a member of the exhibitor,
// so every scenario that creates a lead has to join the exhibitor first —
// the exhibitor is reused across runs, so the join is repeated every time.
function becomeExhibitorRepresentative(exhibitorName = DEFAULT_EXHIBITOR) {
  return api.ensureEvent(world, DEFAULT_EVENT)
    .then(() => api.ensureEventOrganizer(world))
    .then(() => api.ensureExhibitor(world, exhibitorName))
    .then(() => api.ensureBooth(world, DEFAULT_BOOTH))
    .then(() => currentUserId(world).then((uid) => {
      world.userId = uid
      return api.post(`/exhibition/exhibitors/${world.exhibitorId}/members`, {
        user_id: uid,
        role: 'representative',
        is_active: true,
      }, world.authToken)
    }))
}

Given('I am an authenticated exhibitor representative for {string}', (exhibitorName = DEFAULT_EXHIBITOR) => {
  return becomeExhibitorRepresentative(exhibitorName).then((resp) => {
    expect(resp.status, `Could not join the exhibitor as a member: ${resp.body}`).to.be.oneOf([200, 201])
  })
})

Given('I am an authenticated exhibitor representative', () => {
  return becomeExhibitorRepresentative().then((resp) => {
    expect(resp.status, `Could not join the exhibitor as a member: ${resp.body}`).to.be.oneOf([200, 201])
  })
})

Given('I am an authenticated user but not associated with exhibitor {string}', (exhibitorName = DEFAULT_EXHIBITOR) => {
  return api.ensureEvent(world, DEFAULT_EVENT)
    .then(() => api.ensureEventOrganizer(world))
    .then(() => api.ensureExhibitor(world, exhibitorName))
    .then(() => {
      // Authenticate as a different user so the caller is NOT a member of the exhibitor.
      const phone = '+989300000042'
      return api.requestOtp(phone).then(([resp, code]) => {
        expect(resp.status, `Could not request an OTP: ${resp.body}`).to.be.oneOf([200, 201])
        return api.post('/api/identity/otp/verify', { phone, otp: code }).then((verifyResp) => {
          expect(verifyResp.status, `Could not authenticate as the unrelated user: ${verifyResp.body}`).to.eq(200)
          world.outsiderToken = api.body(verifyResp).access_token
        })
      })
    })
})

When('I capture a lead for attendee {string} with the following details:', (attendeeName, datatable) => {
  const details = api.tableToDict(datatable)
  const payload = {
    attendee_name: attendeeName,
    attendee_id: world.attendeeId,
    exhibitor_id: world.exhibitorId,
    exhibitor_name: world.exhibitorName,
    interest_level: details.interest_level,
    notes: details.notes,
  }
  return api.post('/exhibition/leads', payload, world.authToken).then((resp) => {
    world.lastResponse = resp
    if (resp.status === 200 || resp.status === 201) {
      const lead = api.body(resp).lead || {}
      world.leadId = lead.id
      world.lead = lead
    }
  })
})

When('I capture a lead for attendee {string} with:', (attendeeName, datatable) => {
  const details = api.tableToDict(datatable)
  const payload = {
    attendee_id: world.attendeeId,
    exhibitor_id: world.exhibitorId,
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
    if (resp.status === 200 || resp.status === 201) {
      world.lead = api.body(resp).lead || world.lead
    }
  })
})

Then('the lead should be created successfully', () => {
  const lead = world.lead
  expect(lead, 'No lead was created').to.exist
  expect(lead.id, `No lead id in the response: ${JSON.stringify(lead)}`).to.exist
  world.leadId = lead.id
})

Then('the lead should be associated with the correct attendee', () => {
  const lead = world.lead
  const attendee = lead.attendee || {}
  expect(attendee.id, `Expected attendee ${world.attendeeId}, got ${attendee.id}`).to.eq(world.attendeeId)
})

Then('the lead should have the correct interest level', () => {
  expect(world.lead.interest_level, `Expected interest level 'HIGH', got ${world.lead.interest_level}`).to.eq('HIGH')
})

Then('the lead should have the correct notes', () => {
  expect(world.lead.notes, 'The lead has no notes').to.exist
  expect(world.lead.notes, `Unexpected notes: ${world.lead.notes}`).to.include('enterprise license')
})

Then('the lead status should be {string}', (status) => {
  expect(world.lead.status, `Expected lead status ${JSON.stringify(status)}, got ${world.lead.status}`).to.eq(status)
})

// --- Update status -----------------------------------------------------------

Given('a lead exists for attendee {string} from exhibitor {string}', (attendeeName = 'John Doe', exhibitorName = DEFAULT_EXHIBITOR) => {
  return becomeExhibitorRepresentative(exhibitorName)
    .then(() => {
      if (!world.attendeeId) {
        return api.registerAttendee(world, {
          first_name: 'John',
          last_name: 'Doe',
          email: `john.doe.${api.uniqueSuffix().slice(0, 8)}@example.com`,
          phone: `+9895${api.uniqueDigits(9)}`,
          ticket_type: 'GENERAL',
          eventId: world.eventId,
        }).then((body) => {
          expect(body.attendee ? body.attendee.id : (body.data && body.data.id)).to.exist
          world.attendeeId = body.attendee ? body.attendee.id : (body.data && body.data.id)
        })
      }
    })
    .then(() => api.post('/exhibition/leads', {
      attendee_id: world.attendeeId,
      exhibitor_id: world.exhibitorId,
      interest_level: 'HIGH',
      notes: 'Interested in enterprise license',
    }, world.authToken))
    .then((resp) => {
      expect(resp.status, `Could not create the lead: ${resp.body}`).to.be.oneOf([200, 201])
      world.leadId = api.body(resp).lead.id
      world.lead = api.body(resp).lead
    })
})

Given('the lead status is {string}', (status = 'NEW') => {
  return api.get(`/exhibition/leads/${world.leadId}`, world.authToken).then((resp) => {
    const body = api.body(resp)
    const lead = body.lead || body
    expect(lead.status, `Unexpected lead status: ${JSON.stringify(body)}`).to.eq(status)
  })
})

When('I update the lead status to {string}', (status = 'QUALIFIED') => {
  return api.put(`/exhibition/leads/${world.leadId}`, { status }, world.authToken).then((resp) => {
    world.lastResponse = resp
  })
})

Then('the lead status should be updated successfully', () => {
  expect(world.lastResponse.status, `Lead update failed: ${world.lastResponse.body}`).to.eq(200)
  const lead = api.body(world.lastResponse).lead || {}
  expect(lead.status, `Unexpected lead status: ${JSON.stringify(lead)}`).to.eq('QUALIFIED')
})

Then('the update should be recorded in the lead\'s history', () => {
  return api.get(`/exhibition/leads/${world.leadId}/history`, world.authToken).then((resp) => {
    expect(resp.status, `Could not read the lead history: ${resp.body}`).to.eq(200)
    const history = api.body(resp).history || []
    expect(history.length, `The lead history is empty: ${resp.body}`).to.be.gt(0)
    const entries = history.filter((entry) => entry.field === 'status')
    expect(entries.length, `No status entry in the lead history: ${JSON.stringify(history)}`).to.be.gt(0)
    expect(entries[entries.length - 1].new_value, `Unexpected history entry: ${JSON.stringify(entries)}`).to.eq('QUALIFIED')
  })
})

// --- Tags -------------------------------------------------------------------

When('I add the tag {string} to the lead', (tag = 'enterprise') => {
  return api.post(`/exhibition/leads/${world.leadId}/tags`, { tag_name: tag }, world.authToken).then((resp) => {
    world.lastResponse = resp
  })
})

When('I remove the tag {string} from the lead', (tag = 'enterprise') => {
  return api.del(`/exhibition/leads/${world.leadId}/tags/${tag}`, world.authToken).then((resp) => {
    world.lastResponse = resp
  })
})

Given('the lead has the tag {string}', (tag = 'enterprise') => {
  return api.post(`/exhibition/leads/${world.leadId}/tags`, { tag_name: tag }, world.authToken)
})

Then('the tag should be associated with the lead', (tag = 'enterprise') => {
  expect(world.lastResponse.status, `Tagging failed: ${world.lastResponse.body}`).to.be.oneOf([200, 201])
  return api.get(`/exhibition/leads/${world.leadId}`, world.authToken).then((resp) => {
    const body = api.body(resp)
    const lead = body.lead || {}
    const tags = (lead.tags || []).map((t) => t.tag_name)
    expect(tags, `The tag ${JSON.stringify(tag)} is not associated with the lead: ${JSON.stringify(tags)}`).to.include(tag)
  })
})

Then('the tag should no longer be associated with the lead', (tag = 'enterprise') => {
  expect(world.lastResponse.status, `Tag removal failed: ${world.lastResponse.body}`).to.eq(200)
  return api.get(`/exhibition/leads/${world.leadId}`, world.authToken).then((resp) => {
    const body = api.body(resp)
    const lead = body.lead || {}
    const tags = (lead.tags || []).map((t) => t.tag_name)
    expect(tags.includes(tag), `The tag ${JSON.stringify(tag)} is still associated with the lead: ${JSON.stringify(tags)}`).to.eq(false)
  })
})

Then('the lead should be filterable by the {string} tag', (tag = 'enterprise') => {
  return api.get('/exhibition/leads', world.authToken, { tag }).then((resp) => {
    expect(resp.status, `Could not filter the leads: ${resp.body}`).to.eq(200)
    const leads = api.body(resp).leads || []
    const ids = leads.map((lead) => lead.id)
    expect(ids, `The lead is not in the filtered result: ${JSON.stringify(ids)}`).to.include(world.leadId)
  })
})

Then('the lead should not be filterable by the {string} tag', (tag = 'enterprise') => {
  return api.get('/exhibition/leads', world.authToken, { tag }).then((resp) => {
    expect(resp.status, `Could not filter the leads: ${resp.body}`).to.eq(200)
    const leads = api.body(resp).leads || []
    const ids = leads.map((lead) => lead.id)
    expect(ids.includes(world.leadId), `The lead is still in the filtered result: ${JSON.stringify(ids)}`).to.eq(false)
  })
})

// --- Permission -------------------------------------------------------------

When('I attempt to create a lead for exhibitor {string}', (exhibitorName = DEFAULT_EXHIBITOR) => {
  const payload = {
    attendee_id: world.attendeeId,
    exhibitor_id: world.exhibitorId,
    exhibitor_name: exhibitorName,
    interest_level: 'HIGH',
    notes: 'Interested in enterprise license',
  }
  const token = world.outsiderToken || world.authToken
  return api.post('/exhibition/leads', payload, token).then((resp) => {
    world.lastResponse = resp
  })
})

Then('I should receive a permission denied error', () => {
  expect(world.lastResponse.status, `Expected a 403 permission denied, got ${world.lastResponse.status} - ${world.lastResponse.body}`).to.eq(403)
  const message = String(api.body(world.lastResponse).message || '').toLowerCase()
  expect(message, `Unexpected message: ${message}`).to.satisfy((s) => s.includes('permission') || s.includes('denied') || s.includes('authorized'))
})

Then('no lead should be created', () => {
  expect('lead' in api.body(world.lastResponse), `A lead was created despite the error: ${world.lastResponse.body}`).to.eq(false)
})

// --- Validation -------------------------------------------------------------

When('I attempt to create a lead without specifying an attendee', () => {
  const payload = {
    exhibitor_id: world.exhibitorId,
    interest_level: 'HIGH',
    notes: 'Interested in enterprise license',
  }
  return api.post('/exhibition/leads', payload, world.authToken).then((resp) => {
    world.lastResponse = resp
  })
})

When('I attempt to create a lead without specifying notes', () => {
  const payload = {
    attendee_id: world.attendeeId,
    exhibitor_id: world.exhibitorId,
    interest_level: 'HIGH',
  }
  return api.post('/exhibition/leads', payload, world.authToken).then((resp) => {
    world.lastResponse = resp
  })
})

Then('I should receive a validation error about missing {word}', (field = 'attendee') => {
  expect(world.lastResponse.status, `Expected a 422 validation error, got ${world.lastResponse.status} - ${world.lastResponse.body}`).to.eq(422)
  const errors = api.body(world.lastResponse).errors || {}
  expect(errors[field], `No ${field} validation error`).to.exist
})

function currentUserId(w) {
  return api.get('/shared/users/me', w.authToken).then((resp) => {
    const b = api.body(resp)
    return b.user_id || (b.user && b.user.id) || 1
  })
}