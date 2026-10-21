const { createClient } = require('redis')
const host = process.env.REDIS_HOST || 'redis'
const port = parseInt(process.env.REDIS_PORT || '6379', 10)
const t0 = Date.now()
async function main() {
  console.log('env redis host/port', host, port)
  const client = createClient({ socket: { host, port, connectTimeout: 5000 } })
  client.on('error', (e) => console.error('CLIENT_ERROR', Date.now() - t0, e.message))
  let connectRes
  try {
    connectRes = await client.connect()
    console.log('CONNECT_OK', Date.now() - t0, 'res=', connectRes)
  } catch (e) {
    console.error('CONNECT_FAIL', Date.now() - t0, e.message)
    return
  }
  try {
    const sel = await client.select(0)
    console.log('SELECT0_OK', Date.now() - t0, JSON.stringify(sel))
    const scan = await client.scan('0', { MATCH: '*otp_request*', COUNT: 100 })
    console.log('SCAN_OK', Date.now() - t0, JSON.stringify(scan))
  } catch (e) {
    console.error('CMD_ERR', Date.now() - t0, e.message)
  } finally {
    await client.quit().catch(() => {})
    console.log('QUIT_DONE', Date.now() - t0)
  }
}
main().catch((e) => { console.error('MAIN_ERR', Date.now() - t0, e.message); process.exit(1) })
process.on('unhandledRejection', (e) => console.error('UNHANDLED', e))
