const { defineConfig } = require('cypress')

const BASE_URL = process.env.CYPRESS_BASE_URL || 'http://nginx'

// The app caches the OTP it issues in Redis, so the suite reads the code back
// from there. The redis client runs in Node, so these are `cy.task`s.
const REDIS_HOST = process.env.REDIS_HOST || 'redis'
const REDIS_PORT = parseInt(process.env.REDIS_PORT || '6379', 10)
const REDIS_DBS = (process.env.REDIS_DBS || '0,1,2').split(',').map(Number)
const OTP_CACHE_PREFIX = 'signic_otp_'

let redisClient = null
let redisConnectPromise = null

function getRedisClient() {
  if (!redisClient) {
    const { createClient } = require('redis')
    redisClient = createClient({
      socket: { host: REDIS_HOST, port: REDIS_PORT },
    })
    redisClient.on('error', () => {})
    redisConnectPromise = redisClient.connect()
  }
  return redisClient
}

// Yield [db, key] for every key matching `pattern` across the redis DBs
// the app may cache in. Mirrors bdd_utils.scan_keys.
//
// A `createClient()`-only client queues commands until it has actually
// connected; calling `.select()`/`.scan()` before the connect promise
// resolves hangs the whole cy.task until its 60 s timeout. Await the
// connect promise first so the first command runs against a live socket.
//
// The SCAN reply cursor is a NUMBER in node-redis 4.x, so it must be
// normalised to a string before comparing it with '0' — a strict
// `cursor !== '0'` never terminates the loop and the task times out.
async function scanKeys(pattern) {
  const client = getRedisClient()
  await redisConnectPromise
  const found = []
  for (const db of REDIS_DBS) {
    await client.select(db)
    let cursor = '0'
    do {
      const reply = await client.scan(cursor, { MATCH: pattern, COUNT: 100 })
      cursor = String(reply.cursor)
      for (const key of reply.keys) found.push([db, key])
    } while (cursor !== '0')
  }
  return found
}

module.exports = defineConfig({
  defaultCommandTimeout: 15000,
  responseTimeout: 20000,
  requestTimeout: 20000,
  viewportWidth: 1280,
  viewportHeight: 800,
  e2e: {
    baseUrl: BASE_URL,
    specPattern: 'features/**/*.feature',
    supportFile: 'support/e2e.js',
    screenshotsFolder: 'cypress/screenshots',
    videosFolder: 'cypress/videos',
        stepDefinitions: ['features/[filepath].js'],
    async setupNodeEvents(on, config) {
      const cucumber = require('@badeball/cypress-cucumber-preprocessor')
      const { createEsbuildPlugin } = require('@badeball/cypress-cucumber-preprocessor/esbuild')
      const createBundler = require('@bahmutov/cypress-esbuild-preprocessor')

      await cucumber.addCucumberPreprocessorPlugin(on, config)

      on(
        'file:preprocessor',
        createBundler({
          plugins: [createEsbuildPlugin(config)],
        }),
      )

      on('task', {
        // Read the OTP the app just cached for a phone number. The app caches it
        // immediately (NullSMSAdapter logs it), but poll Redis briefly to be safe
        // in case the cache was flushed between request and read.
        async fetchOtp(phone) {
          const client = getRedisClient()
          const deadline = Date.now() + 5000
          while (Date.now() < deadline) {
            for (const [db, key] of await scanKeys(`*${OTP_CACHE_PREFIX}${phone}`)) {
              await client.select(db)
              const value = await client.get(key)
              if (value) return value
            }
            await new Promise((resolve) => setTimeout(resolve, 100))
          }
          return null
        },

        // Expire the cached OTP without waiting for the real TTL.
        async expireOtp(phone) {
          const client = getRedisClient()
          let expired = false
          for (const [db, key] of await scanKeys(`*${OTP_CACHE_PREFIX}${phone}`)) {
            await client.select(db)
            await client.expire(key, 1)
            expired = true
          }
          return expired
        },

        // Clear every rate limiter key so no scenario is blocked by a 429.
        async clearRateLimits() {
          const client = getRedisClient()
          for (const pattern of ['*otp_request*', '*otp_verify*', '*scans_scan*']) {
            for (const [db, key] of await scanKeys(pattern)) {
              await client.select(db)
              await client.del(key)
            }
          }
          return true
        },
      })

      return Object.assign({}, config, { baseUrl: BASE_URL })
    },
  },
})
