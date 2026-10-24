<template>
  <div class="space-y-6">
    <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
      <h2 class="text-xl font-bold text-slate-900 mb-4">Welcome {{ user?.name || 'User' }}</h2>
      <p class="text-slate-600">Use the navigation menu to access different features of Signic platform.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      <div class="card">
        <div class="flex items-center">
          <div class="p-3 bg-indigo-100 rounded-lg">
            <span class="text-2xl">📱</span>
          </div>
          <div class="ml-4">
            <h3 class="text-lg font-semibold text-slate-900">Quick Access</h3>
            <p class="text-sm text-slate-500">Register attendees and manage access</p>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="flex items-center">
          <div class="p-3 bg-green-100 rounded-lg">
            <span class="text-2xl">✓</span>
          </div>
          <div class="ml-4">
            <h3 class="text-lg font-semibold text-slate-900">Check Status</h3>
            <p class="text-sm text-slate-500">Track check-in/check-out status</p>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="flex items-center">
          <div class="p-3 bg-blue-100 rounded-lg">
            <span class="text-2xl">👁️</span>
          </div>
          <div class="ml-4">
            <h3 class="text-lg font-semibold text-slate-900">QR Badges</h3>
            <p class="text-sm text-slate-500">View and download QR badges</p>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="flex items-center">
          <div class="p-3 bg-purple-100 rounded-lg">
            <span class="text-2xl">🏢</span>
          </div>
          <div class="ml-4">
            <h3 class="text-lg font-semibold text-slate-900">Exhibitors</h3>
            <p class="text-sm text-slate-500">Manage exhibitor information</p>
          </div>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <div class="card">
        <h3 class="text-lg font-semibold text-slate-900 mb-4">Quick Actions</h3>
<div class="space-y-3">
          <Link
            href="/qr-guide"
            class="block w-full btn btn-secondary text-center"
          >
            QR Code Guide
          </Link>
          <a
            @click="goBack"
            class="block w-full btn btn-secondary text-center"
          >
            Cancel
          </a>
          <button
            type="submit"
            class="btn btn-primary inline-block text-center"
          >
            Register Attendee
          </button>
        </div>
      </div>

      <div class="card">
        <h3 class="text-lg font-semibold text-slate-900 mb-4">Recent Activity</h3>
        <div class="text-center py-8">
          <span class="text-4xl">📊</span>
          <p class="text-slate-500 mt-2">No recent activity to display</p>
          <p class="text-sm text-slate-400 mt-1">Start by registering an attendee</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { useAuth } from '../composables/useAuth.js';
import { requireAuth } from '../composables/useGuard.js';
import { Link } from '@inertiajs/vue3';

export default {
  name: 'DashboardPage',
  setup() {
    requireAuth();
    const { user } = useAuth();

    return { user };
  },
};
</script>