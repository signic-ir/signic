<template>
  <div class="min-h-screen bg-slate-50 py-12">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="bg-white rounded-2xl shadow-lg border border-slate-200 p-8">
        <h1 class="text-3xl font-bold text-slate-900 mb-2">QR Code Guide</h1>
        <p class="text-slate-600 mb-8">
          Scan the QR code to check in and print your postal card.
        </p>

        <div class="prose prose-slate max-w-none">
          <h2 class="text-xl font-semibold text-slate-900">How to scan your QR code</h2>
          <ol class="space-y-4 mt-4 text-slate-700">
            <li class="flex items-start">
              <span class="flex items-center justify-center w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 font-bold mr-3 shrink-0">1</span>
              <span>
                <strong>Open your phone's camera app.</strong> Most modern smartphones can read QR codes directly through the camera — no extra app needed.
              </span>
            </li>
            <li class="flex items-start">
              <span class="flex items-center justify-center w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 font-bold mr-3 shrink-0">2</span>
              <span>
                <strong>Point your camera at the QR code.</strong> Hold your phone steady about 12 inches (30 cm) from the code. Your phone will show a notification — tap it to proceed.
              </span>
            </li>
            <li class="flex items-start">
              <span class="flex items-center justify-center w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 font-bold mr-3 shrink-0">3</span>
              <span>
                <strong>Your postal card will be generated.</strong> After scanning, you'll see your personalized postal card. Click "Print My Postal Card" to print a physical copy for your records.
              </span>
            </li>
          </ol>
        </div>

        <div class="mt-8 p-6 bg-indigo-50 rounded-lg text-center">
          <p class="text-sm font-medium text-indigo-900 mb-4">Sample QR Code</p>
          <div class="inline-block bg-white p-4 rounded-lg border border-slate-200">
            <div class="w-32 h-32 flex items-center justify-center bg-gray-100">
              <span class="text-2xl text-gray-400">QR Code</span>
            </div>
          </div>
          <p class="text-xs text-slate-500 mt-2">Your actual QR code will appear after registration.</p>
        </div>

        <div class="mt-8 flex flex-col sm:flex-row gap-4 justify-center">
          <Link
            href="/"
            class="btn btn-secondary"
          >
            Back to Landing
          </Link>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { Link } from '@inertiajs/vue3';

export default {
  name: 'QrGuidePage',
  setup() {
    return {};
  },
};
</script>
