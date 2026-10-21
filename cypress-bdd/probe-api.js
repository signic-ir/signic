// Probe the app API directly to see the real response shapes.
const BASE = 'http://nginx'

async function req(method, path, body, token) {
  const headers = { Accept: 'application/json' }
  if (body) headers['Content-Type'] = 'application/json'
  if (token) headers.Authorization = `Bearer ${token}`
  const res = await fetch(BASE + path, {
    method,
    headers,
    body: body ? JSON.stringify(body) : undefined,
  })
  const text = await res.text()
  let parsed
  try { parsed = JSON.parse(text) } catch (e) { parsed = text }
  return { status: res.status, body: parsed }
}

;(async () => {
  // 1. request OTP
  const phone = '+989500000' + String(Math.floor(Math.random() * 100000)).padStart(5, '0')
  let r = await req('POST', '/api/identity/otp/request', { phone })
  console.log('otp request:', r.status, JSON.stringify(r.body))

  // 2. read OTP from redis (no redis client here) -> use the log-free path:
  //    request the OTP again is blocked, so ask the app to verify with a code
  //    fetched via the suite's redis task is not available here.
  console.log('phone used:', phone)

  // 3. shape probes that do NOT need auth
  const event = await req('GET', '/registration/events')
  console.log('events:', event.status, JSON.stringify(event.body).slice(0, 300))

  const qr = await req('GET', '/registration/attendees/1/qr')
  console.log('qr without auth:', qr.status, JSON.stringify(qr.body).slice(0, 200))

  const validate = await req('POST', '/registration/attendees/validate', { qr_token: '1|abc123|invalid_signature' })
  console.log('validate invalid:', validate.status, JSON.stringify(validate.body).slice(0, 300))

  process.exit(0)
})()
