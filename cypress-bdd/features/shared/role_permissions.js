// cypress-bdd/features/shared/role_permissions.js
//
// Step definitions for the Shared module feature (role & permission system),
// mirroring `/home/hamid/bdd-test/step_definitions/shared_role_permission_steps.py` exactly.
//
// v28 uses Cucumber Expressions natively. Free‑text steps use RegularExpression.
import { Given, When, Then } from '@badeball/cypress-cucumber-preprocessor'
import world from '../../support/world'
import api from '../../support/api'
import { registerBeforeHooks } from '../../support/setup'

registerBeforeHooks()

const SPEC_ROLES = [
  ['super_admin', 'Full system access'],
  ['event_organizer', 'Manage events and attendees'],
  ['exhibitor', 'Manage exhibitor leads'],
  ['staff', 'Event operations staff'],
]

const SPEC_PERMISSIONS = [
  ['attendee.create', 'Create new attendees'],
  ['attendee.view', 'View attendee information'],
  ['exhibitor.lead.create', 'Create leads for exhibitors'],
  ['exhibitor.lead.view', 'View exhibitor leads'],
  ['turnstile.operate', 'Operate turnstile check-in/out'],
  ['user.manage', 'Manage system users'],
]

Given('the system is running', () => {
  return api.ensureEvent(world, 'Tech Conference 2024')
})

Given('I have access to the Shared module', () => {})

Given('the following roles exist:', (datatable) => {
  const rows = datatable ? datatable.rawTable : null
  // Chain the calls: a bare loop would enqueue the requests but return before
  // they ran, so the scenario would assert on stale state.
  return api.chainAll(SPEC_ROLES.map(([name, defaultDescription]) => () => {
    const row = rows ? rows.find((r) => String(r[0]).trim() === name) : null
    return api.ensureRole(world, name, row ? row[1] : defaultDescription)
  }))
})

Given('the following permissions exist:', (datatable) => {
  const rows = datatable ? datatable.rawTable : null
  return api.chainAll(SPEC_PERMISSIONS.map(([name, defaultDescription]) => () => {
    const row = rows ? rows.find((r) => String(r[0]).trim() === name) : null
    return api.ensurePermission(world, name, row ? row[1] : defaultDescription)
  }))
})

Given('the {string} role exists', (role = 'event_organizer') => {
  return api.ensureRole(world, role)
})

Given('the {string} permission exists', (permission = 'attendee.create') => {
  return api.ensurePermission(world, permission)
})

Given('the {string} role has the {string} permission', (role, permission) => {
  return api.assignPermissionToRole(world, role, permission)
})

Given('the {string} role does not have the {string} permission', (role = 'staff', permission = 'exhibitor.lead.create') => {
  return api.ensureRole(world, role).then((rolePayload) => {
    return api.post(`/shared/roles/${rolePayload.id}/permissions`, { permission }, world.authToken).then((resp) => {
      if (resp.status === 200 || resp.status === 201) {
        return api.del(`/shared/roles/${rolePayload.id}/permissions/${permission}`, world.authToken)
      }
    })
  })
})

Given('a user has the {string} role', (role = 'event_organizer') => {
  return currentUserId(world).then((userId) => {
    world.userId = userId
    return api.assignRoleToUser(world, userId, role)
  })
})

Given('the {string} role inherits from all other roles', (role = 'super_admin') => {
  return api.ensureRole(world, role).then(() => {
    return api.post(`/shared/roles/${role}/inherit-all`, {}, world.authToken).then((resp) => {
      expect(resp.status, `Could not set the inheritance: ${resp.body}`).to.be.oneOf([200, 201])
    })
  })
})

function currentUserId(w) {
  return api.get('/shared/users/me', w.authToken).then((resp) => {
    const b = api.body(resp)
    return b.user_id || (b.user && b.user.id) || 1
  })
}

When('I assign the {string} permission to the {string} role', (permission, role) => {
  return api.ensureRole(world, role).then((rolePayload) => {
    return api.ensurePermission(world, permission).then(() => {
      return api.post(`/shared/roles/${rolePayload.id}/permissions`, { permission }, world.authToken)
    })
  }).then((resp) => {
    world.lastResponse = resp
  })
})

Then('the role should have the permission', () => {
  expect(world.lastResponse.status, `Assigning the permission failed: ${world.lastResponse.body}`).to.be.oneOf([200, 201])
  const role = api.body(world.lastResponse).role || {}
  const names = (role.permissions || []).map((permission) => permission.name)
  expect(names, `The role does not have the permission: ${JSON.stringify(names)}`).to.include('attendee.create')
})

Then('users with the {string} role should be able to create attendees', (role = 'event_organizer') => {
  return api.get(`/shared/roles/${role}`, world.authToken).then((resp) => {
    const payload = api.body(resp)
    const rolePayload = payload.role || payload
    const names = (rolePayload.permissions || []).map((permission) => permission.name)
    expect(names, `The role does not have attendee.create: ${JSON.stringify(names)}`).to.include('attendee.create')
  })
})

When('I check if the user can create attendees', () => {
  return api.get(`/shared/users/${world.userId}/permissions/attendee.create`, world.authToken).then((resp) => {
    world.lastResponse = resp
  })
})

When('I check if the user can create exhibitor leads', () => {
  return api.get(`/shared/users/${world.userId}/permissions/exhibitor.lead.create`, world.authToken).then((resp) => {
    world.lastResponse = resp
  })
})

// Free text: "I check if a \"super_admin\" user can view attendees"
When(/^I check if a "([^"]*)" user can (.+)$/, (role, action) => {
  return api.ensureRole(world, role).then(() => api.assignRoleToUser(world, world.userId, role)).then(() => {
    const permission = {
      'view attendees': 'attendee.view',
      'create attendees': 'attendee.create',
      'create exhibitor leads': 'exhibitor.lead.create',
    }[action] || action
    return api.get(`/shared/users/${world.userId}/permissions/${permission}`, world.authToken)
  }).then((resp) => {
    world.lastResponse = resp
  })
})

// Free text: "the super_admin inherits permissions from event_organizer"
Then(/^the (\S+) inherits permissions from (\S+)$/, (role, ancestor) => {
  const body = api.body(world.lastResponse)
  expect(body.allowed, `The check did not return true: ${JSON.stringify(body)}`).to.eq(true)
  return api.get(`/shared/roles/${ancestor}`, world.authToken).then((ancestorResp) => {
    const ancestorPermissions = (api.body(ancestorResp).role || {}).permission_names || []
    return api.get(`/shared/roles/${role}`, world.authToken).then((roleResp) => {
      const inherited = (api.body(roleResp).role || {}).permission_names || []
      const missing = ancestorPermissions.filter((name) => !inherited.includes(name))
      expect(missing.length, `The role ${JSON.stringify(role)} does not inherit ${JSON.stringify(missing)} from ${JSON.stringify(ancestor)}: ${JSON.stringify(inherited)}`).to.eq(0)
    })
  })
})

Then('the check should return true', () => {
  expect(world.lastResponse.status, `The permission check failed: ${world.lastResponse.body}`).to.eq(200)
  const body = api.body(world.lastResponse)
  expect(body.allowed, `The check did not return true: ${JSON.stringify(body)}`).to.eq(true)
})

Then('the check should return false', () => {
  expect(world.lastResponse.status, `The permission check failed: ${world.lastResponse.body}`).to.eq(200)
  const body = api.body(world.lastResponse)
  expect(body.allowed, `The check did not return false: ${JSON.stringify(body)}`).to.eq(false)
})

Then('the user should be allowed to create attendees', () => {
  const body = api.body(world.lastResponse)
  expect(body.allowed, `The user was not allowed: ${JSON.stringify(body)}`).to.eq(true)
})

Then('the user should be denied from creating exhibitor leads', () => {
  const body = api.body(world.lastResponse)
  expect(body.allowed, `The user was not denied: ${JSON.stringify(body)}`).to.eq(false)
})

// --- API access validation -------------------------------------------------

Given('I am an unauthenticated user', () => {
  world.authToken = null
})

Given('I am an authenticated user with {string} role', (role = 'staff') => {
  return api.ensureRole(world, role).then(() => currentUserId(world)).then((userId) => {
    world.userId = userId
    return api.assignRoleToUser(world, userId, role)
  })
})

When('I attempt to access the attendee creation API endpoint', () => {
  const payload = {
    first_name: 'John',
    last_name: 'Doe',
    name: 'John Doe',
    email: `permission.check.${api.uniqueSuffix().slice(0, 8)}@example.com`,
    phone: `+9895${api.uniqueDigits(9)}`,
    ticket_type: 'GENERAL',
    event_id: world.eventId,
    event_name: world.eventName,
  }
  return api.post('/registration/attendees', payload, world.authToken).then((resp) => {
    world.lastResponse = resp
  })
})

Then('I should receive an authentication error', () => {
  expect(world.lastResponse.status, `Expected an authentication error, got ${world.lastResponse.status} - ${world.lastResponse.body}`).to.be.oneOf([401, 403])
  expect(world.lastResponse.status).to.eq(401)
})

Then('I should receive a permission denied error', () => {
  expect(world.lastResponse.status, `Expected a 403 permission denied, got ${world.lastResponse.status} - ${world.lastResponse.body}`).to.eq(403)
})

Then('I should be allowed to access the endpoint', () => {
  expect(world.lastResponse.status, `Expected the endpoint to accept the request, got ${world.lastResponse.status}`).to.be.oneOf([200, 201])
})

Then('I should be able to create attendees', () => {
  expect(world.lastResponse.status, `Expected the attendee to be created, got ${world.lastResponse.status}`).to.be.oneOf([200, 201])
})

Then('I should not be able to create attendees', () => {
  expect(world.lastResponse.status, `Expected the request to be rejected, got ${world.lastResponse.status}`).to.be.gte(400)
  expect('attendee' in api.body(world.lastResponse), `An attendee was created despite the rejection: ${world.lastResponse.body}`).to.eq(false)
})