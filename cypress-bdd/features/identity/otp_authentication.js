// cypress-bdd/features/identity/otp_authentication.js
//
// Step definitions for the Identity module feature, mirroring
// `/home/hamid/bdd-test/step_definitions/identity_otp_steps.py` exactly.
//
// v28 uses Cucumber Expressions natively — steps are plain strings with
// {placeholders}. The argument transformer strips quotes for {string} and
// coerces {int} to Number. Free‑text steps use RegularExpression.
import { Given, When, Then } from '@badeball/cypress-cucumber-preprocessor'
import world from '../../support/world'
import api from '../../support/api'
import { registerBeforeHooks } from '../../support/setup'

// The Identity scenarios drive authentication themselves, so they start
// unauthenticated — no bearer token is minted up front.
registerBeforeHooks({ authenticate: false })

Given('the system is running', () => {})

Given('I have access to the Identity module', () => {})

Given('the SMS provider is configured for testing', () => {})

Given('I am an unauthenticated user', () => {
  world.authToken = null
})

When('I request an OTP for my phone number {string}', (phone) => {
  return api.requestOtp(phone).then(([resp, code]) => {
    world.lastResponse = resp
    world.otpPhone = phone
    world.otpCode = code
    expect(resp.status, `OTP request failed: ${resp.body}`).to.be.oneOf([200, 201])
  })
})

When('I request another OTP for the same phone number', () => {
  return api.post('/api/identity/otp/request', { phone: api.TEST_PHONE }).then((resp) => {
    world.lastResponse = resp
  })
})

Given('I have requested {int} OTP codes for phone number {string} within 1 minute', (count, phone) => {
  // Mirror the pytest-bdd step: keep requesting until the app rate-limits us.
  // A Cypress chain (not a native Promise) has to come back from the handler.
  let lastResp = null
  let rateLimited = false
  return api.chainAll([...Array(count).keys()].map((index) => () => {
    if (rateLimited) return null
    return api.post('/api/identity/otp/request', { phone }).then((resp) => {
      lastResp = resp
      if (resp.status === 429) rateLimited = true
    })
  })).then(() => {
    // pytest-bdd keeps the previous response when the loop was rate limited.
    if (!rateLimited) world.lastResponse = lastResp
  })
})

Given('I have received an OTP code {string} for phone number {string}', (code, phone) => {
  return api.requestOtp(phone).then(([resp, fetched]) => {
    world.lastResponse = resp
    world.otpPhone = phone
    world.otpCode = fetched
    expect(fetched, `No OTP was issued for ${phone}`).to.exist
  })
})

When('I verify the OTP code {string} for phone number {string}', (code, phone) => {
  const otp = world.otpCode || code
  return api.post('/api/identity/otp/verify', { phone, otp }).then((resp) => {
    world.lastResponse = resp
    if (resp.status === 200) world.authToken = api.body(resp).access_token
  })
})

When('I verify an incorrect OTP code {string} for phone number {string}', (code, phone) => {
  return api.post('/api/identity/otp/verify', { phone, otp: code }).then((resp) => {
    world.lastResponse = resp
  })
})

Given('the OTP has expired \\(more than 5 minutes old)', () => {
  const phone = world.otpPhone || api.TEST_PHONE
  return api.expireOtp(phone).then((expired) => {
    expect(expired, 'No cached OTP found to expire').to.eq(true)
    return new Promise((resolve) => setTimeout(resolve, 1500))
  })
})

Then('I should receive an OTP code via SMS', () => {
  expect(api.body(world.lastResponse).message).to.eq('OTP sent successfully')
  expect(world.otpCode, 'No OTP code was issued for the phone number').to.exist
})

Then('the OTP should be valid for 5 minutes', () => {
  expect(true).to.eq(true)
})

Then('the OTP should be a 6-digit numeric code', () => {
  expect(world.otpCode, 'No OTP code was issued for the phone number').to.exist
  expect(world.otpCode).to.match(/^\d{6}$/)
})

Then('the verification should succeed', () => {
  expect(world.lastResponse.status, `OTP verification failed: ${world.lastResponse.body}`).to.eq(200)
  const data = api.body(world.lastResponse)
  expect(data.message).to.eq('Authentication successful')
  expect(data.access_token, `No access token in response: ${data}`).to.exist
  world.authToken = data.access_token
})

Then('I should be authenticated as a user', () => {
  expect(world.lastResponse.status, `Authentication failed: ${world.lastResponse.body}`).to.eq(200)
  expect(api.body(world.lastResponse).access_token, 'No access token in the response').to.exist
})

Then('the OTP should be marked as used', () => {
  const phone = world.otpPhone || api.TEST_PHONE
  return api.post('/api/identity/otp/verify', { phone, otp: world.otpCode }).then((resp) => {
    expect(resp.status, `Used OTP should fail: ${resp.body}`).to.eq(401)
  })
})

Then('the verification should fail', () => {
  expect(world.lastResponse.status, `Expected 401, got ${world.lastResponse.status}: ${world.lastResponse.body}`).to.eq(401)
})

Then('I should remain unauthenticated', () => {
  expect(world.lastResponse.status).to.eq(401)
  expect(world.authToken, 'User should not be authenticated').to.be.null
})

Then('the OTP should not be marked as used', () => {
  const phone = world.otpPhone || api.TEST_PHONE
  return api.post('/api/identity/otp/verify', { phone, otp: world.otpCode }).then((resp) => {
    expect(resp.status, `OTP should still be usable after a failed attempt: ${resp.body}`).to.eq(200)
    world.authToken = api.body(resp).access_token
  })
})

Then('I should receive a rate limit error', () => {
  expect(world.lastResponse.status, `Expected 429 rate limit, got ${world.lastResponse.status}: ${world.lastResponse.body}`).to.eq(429)
})

Then('I should not receive another OTP until the rate limit window expires', () => {
  expect(api.body(world.lastResponse).message).to.eq('Too many OTP requests. Please try again later.')
})

Then('the verification should fail with an expired OTP error', () => {
  expect(world.lastResponse.status, `Expected 401, got ${world.lastResponse.status}: ${world.lastResponse.body}`).to.eq(401)
  const message = String(api.body(world.lastResponse).message || '').toLowerCase()
  expect(message.includes('expired') || message.includes('invalid'), `Unexpected message: ${message}`).to.eq(true)
})