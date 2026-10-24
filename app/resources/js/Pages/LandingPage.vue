<template>
  <div class="min-h-screen bg-slate-50">
    <!-- Hero Section -->
    <section class="relative bg-gradient-to-br from-indigo-600 via-indigo-700 to-indigo-900 text-white overflow-hidden py-20 sm:py-32">
      <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
          <span class="text-5xl block mb-4">🎫</span>
          <h1 class="text-4xl sm:text-5xl md:text-6xl font-bold tracking-tight mb-6">
            Welcome to <span class="text-yellow-300">ain1.ir</span>
          </h1>
          <p class="text-xl sm:text-2xl text-indigo-100 mb-8 max-w-3xl mx-auto">
            Iran's premier event registration & check-in platform. 
            Seamless QR badges, instant check-in, and real-time analytics.
          </p>
          <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <Link 
              href="/register" 
              class="btn btn-yellow text-lg px-8 py-3"
            >
              Register for Event
            </Link>
            <Link 
              href="/qr-guide" 
              class="btn btn-white-outline text-lg px-8 py-3"
            >
              How to Scan QR Code
            </Link>
          </div>
        </div>

        <!-- Event Info Badge -->
        <div class="absolute bottom-0 left-1/2 -translate-x-1/2 translate-y-1/2 mt-12">
          <div class="bg-white/10 backdrop-blur-sm rounded-2xl px-8 py-4 border border-white/20">
            <div class="flex flex-wrap items-center justify-center gap-6 text-sm sm:text-base">
              <span class="flex items-center gap-2">
                <span class="text-xl">📅</span>
                <span>Oct 24-26, 2026</span>
              </span>
              <span class="flex items-center gap-2">
                <span class="text-xl">📍</span>
                <span>Tehran International Exhibition Center</span>
              </span>
              <span class="flex items-center gap-2">
                <span class="text-xl">🏷️</span>
                <span>Tech Summit 2026</span>
              </span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- How It Works -->
    <section class="py-20 bg-white">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
          <h2 class="text-3xl sm:text-4xl font-bold text-slate-900 mb-4">
            How It Works
          </h2>
          <p class="text-lg text-slate-600 max-w-2xl mx-auto">
            Three simple steps from registration to entry
          </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
          <!-- Step 1 -->
          <div class="text-center group">
            <div class="relative inline-block mb-6">
              <div class="w-20 h-20 mx-auto rounded-full bg-indigo-100 flex items-center justify-center group-hover:bg-indigo-500 group-hover:text-white transition-colors duration-300">
                <span class="text-3xl">1️⃣</span>
              </div>
              <div class="absolute -bottom-3 left-1/2 -translate-x-1/2 w-6 h-6 bg-indigo-600 rounded-full flex items-center justify-center text-white text-sm font-bold">
                1
              </div>
            </div>
            <h3 class="text-xl font-semibold text-slate-900 mb-2">Register</h3>
            <p class="text-slate-600">
              Fill out the registration form with your details. 
              Select your ticket type and event.
            </p>
          </div>

          <!-- Step 2 -->
          <div class="text-center group">
            <div class="relative inline-block mb-6">
              <div class="w-20 h-20 mx-auto rounded-full bg-indigo-100 flex items-center justify-center group-hover:bg-indigo-500 group-hover:text-white transition-colors duration-300">
                <span class="text-3xl">2️⃣</span>
              </div>
              <div class="absolute -bottom-3 left-1/2 -translate-x-1/2 w-6 h-6 bg-indigo-600 rounded-full flex items-center justify-center text-white text-sm font-bold">
                2
              </div>
            </div>
            <h3 class="text-xl font-semibold text-slate-900 mb-2">Get Your QR Badge</h3>
            <p class="text-slate-600">
              Receive a unique QR code badge instantly. 
              Save it to your phone or print it.
            </p>
          </div>

          <!-- Step 3 -->
          <div class="text-center group">
            <div class="relative inline-block mb-6">
              <div class="w-20 h-20 mx-auto rounded-full bg-indigo-100 flex items-center justify-center group-hover:bg-indigo-500 group-hover:text-white transition-colors duration-300">
                <span class="text-3xl">3️⃣</span>
              </div>
              <div class="absolute -bottom-3 left-1/2 -translate-x-1/2 w-6 h-6 bg-indigo-600 rounded-full flex items-center justify-center text-white text-sm font-bold">
                3
              </div>
            </div>
            <h3 class="text-xl font-semibold text-slate-900 mb-2">Scan & Enter</h3>
            <p class="text-slate-600">
              Present your QR code at the venue turnstile. 
              Instant check-in with real-time verification.
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- Features -->
    <section class="py-20 bg-slate-50">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
          <h2 class="text-3xl sm:text-4xl font-bold text-slate-900 mb-4">
            Why Choose ain1.ir?
          </h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
          <FeatureCard
            icon="⚡"
            title="Instant Check-in"
            description="Sub-second QR validation with Redis atomic locks. No queues, no delays."
          />
          <FeatureCard
            icon="🔒"
            title="Secure & Verified"
            description="Cryptographically signed QR tokens. Tamper-proof and time-limited."
          />
          <FeatureCard
            icon="📊"
            title="Real-time Analytics"
            description="Live attendance dashboards, exhibitor lead capture, and event insights."
          />
        </div>
      </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20 bg-indigo-600 text-white">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl sm:text-4xl font-bold mb-6">
          Ready to Attend?
        </h2>
        <p class="text-xl text-indigo-100 mb-8">
          Register now and get your digital badge instantly.
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
          <Link 
            href="/register" 
            class="btn btn-yellow text-lg px-8 py-3"
          >
            Register Now
          </Link>
          <Link 
            href="/qr-guide" 
            class="btn btn-white-outline text-lg px-8 py-3"
          >
            Learn More About QR
          </Link>
        </div>
      </div>
    </section>

    <!-- Footer -->
    <footer class="bg-slate-900 text-white py-12">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
          <div>
            <span class="text-2xl">🎫</span>
            <span class="ml-2 text-xl font-bold">Signic</span>
            <p class="mt-4 text-slate-400 text-sm">
              Event Registration & Lead Capture Platform
            </p>
          </div>
          <div>
            <h4 class="font-semibold mb-3">Quick Links</h4>
            <ul class="space-y-2 text-slate-400 text-sm">
              <li><Link href="/" class="hover:text-white">Home</Link></li>
              <li><Link href="/qr-guide" class="hover:text-white">QR Guide</Link></li>
              <li><Link href="/register" class="hover:text-white">Register</Link></li>
              <li><Link href="/login" class="hover:text-white">Login</Link></li>
            </ul>
          </div>
          <div>
            <h4 class="font-semibold mb-3">Support</h4>
            <ul class="space-y-2 text-slate-400 text-sm">
              <li>help@ain1.ir</li>
              <li>+98 21 1234 5678</li>
              <li>Tehran, Iran</li>
            </ul>
          </div>
          <div>
            <h4 class="font-semibold mb-3">Follow Us</h4>
            <div class="flex gap-4">
              <a href="#" class="text-slate-400 hover:text-white">Twitter</a>
              <a href="#" class="text-slate-400 hover:text-white">LinkedIn</a>
              <a href="#" class="text-slate-400 hover:text-white">Instagram</a>
            </div>
          </div>
        </div>
        <div class="border-t border-slate-800 mt-8 pt-8 text-center text-slate-400 text-sm">
          © 2026 Signic. All rights reserved.
        </div>
      </div>
    </footer>
  </div>
</template>

<script>
import { Link } from '@inertiajs/vue3';

export default {
  name: 'LandingPage',
  components: {
    FeatureCard: {
      props: ['icon', 'title', 'description'],
      template: `
        <div class="bg-white rounded-xl p-6 shadow-sm border border-slate-200 hover:shadow-md transition-shadow">
          <div class="text-4xl mb-4">{{ icon }}</div>
          <h4 class="text-lg font-semibold text-slate-900 mb-2">{{ title }}</h4>
          <p class="text-slate-600">{{ description }}</p>
        </div>
      `
    }
  },
  setup() {
    return { Link };
  },
};
</script>

<style scoped>
/* Custom button styles for landing page */
.btn-yellow {
  @apply inline-flex items-center justify-center px-6 py-3 border border-transparent 
         text-base font-semibold rounded-lg text-indigo-900 bg-yellow-300 
         hover:bg-yellow-400 focus:outline-none focus:ring-2 focus:ring-yellow-500 
         focus:ring-offset-2 transition-colors duration-200;
}

.btn-white-outline {
  @apply inline-flex items-center justify-center px-6 py-3 border-2 border-white 
         text-base font-semibold rounded-lg text-white bg-transparent 
         hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-white 
         focus:ring-offset-2 focus:ring-offset-indigo-600 transition-colors duration-200;
}
</style>