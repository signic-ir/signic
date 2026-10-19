<template>
  <div class="space-y-6">
    <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
      <h2 class="text-xl font-bold text-slate-900">My QR Badge</h2>
      <p class="text-slate-600 mt-1">Scan this QR code for check-in/check-out</p>
    </div>

    <div v-if="error" class="rounded-md bg-red-50 p-4 text-sm text-red-800" role="alert">
      {{ error }}
    </div>

    <div v-if="loading" class="text-center py-8">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-t-2 border-s-2 border-indigo-500"></div>
      <p class="mt-2 text-slate-500">Loading QR badge...</p>
    </div>

    <div v-else-if="attendee" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
        <div class="text-center">
          <div v-if="qrCodeUrl" class="inline-block">
            <img :src="qrCodeUrl" alt="QR Code" class="w-64 h-64 object-contain" />
          </div>
          <div v-else class="w-64 h-64 bg-slate-100 rounded-lg flex items-center justify-center">
            <span class="text-slate-400">No QR Code Available</span>
          </div>
        </div>
      </div>

      <div class="space-y-6">
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
          <h3 class="text-lg font-semibold text-slate-900 mb-4">Attendee Details</h3>
          <div class="space-y-3">
            <div>
              <p class="text-sm text-slate-500">Name</p>
              <p class="text-lg font-medium text-slate-900">{{ attendee.name }}</p>
            </div>
            <div>
              <p class="text-sm text-slate-500">Phone</p>
              <p class="text-slate-900">{{ attendee.phone }}</p>
            </div>
            <div v-if="attendee.email">
              <p class="text-sm text-slate-500">Email</p>
              <p class="text-slate-900">{{ attendee.email }}</p>
            </div>
            <div v-if="attendee.company">
              <p class="text-sm text-slate-500">Company</p>
              <p class="text-slate-900">{{ attendee.company }}</p>
            </div>
            <div v-if="attendee.job_title">
              <p class="text-sm text-slate-500">Job Title</p>
              <p class="text-slate-900">{{ attendee.job_title }}</p>
            </div>
            <div>
              <p class="text-sm text-slate-500">Ticket Type</p>
              <span :class="ticketTypeBadgeClass(attendee.ticket_type)" class="badge badge-primary">
                {{ attendee.ticket_type }}
              </span>
            </div>
            <div>
              <p class="text-sm text-slate-500">Status</p>
              <span :class="statusBadgeClass(attendee.status)" class="badge">
                {{ attendee.status }}
              </span>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
          <h3 class="text-lg font-semibold text-slate-900 mb-4">QR Token</h3>
          <div class="bg-slate-50 p-4 rounded-lg font-mono text-sm break-all">
            {{ qrToken }}
          </div>
          <p class="text-xs text-slate-500 mt-2">Keep this token secure. It can be scanned at check-in.</p>
        </div>

        <div class="flex gap-3">
          <button @click="downloadQR" class="btn btn-secondary">
            Download QR
          </button>
          <router-link to="/" class="btn btn-primary">
            Back to Dashboard
          </router-link>
        </div>
      </div>
    </div>

    <div v-else class="text-center py-12 bg-white rounded-lg shadow-sm border border-slate-200">
      <span class="text-4xl mb-4 block">🎫</span>
      <h3 class="text-lg font-semibold text-slate-900 mb-2">No Attendee Found</h3>
      <p class="text-slate-500 mb-4">You haven't registered any attendees yet.</p>
      <router-link to="/register" class="btn btn-primary inline-block">
        Register First Attendee
      </router-link>
    </div>
  </div>
</template>

<script>
import { ref, computed } from 'vue';
import axios from 'axios';

export default {
  name: 'AttendeeQRPage',
  setup() {
    const attendee = ref(null);
    const qrCodeUrl = ref('');
    const qrToken = ref('');
    const loading = ref(true);
    const error = ref('');

    async function fetchQR() {
      loading.value = true;
      error.value = '';

      try {
        // For now, we'll use a placeholder attendee
        // In a real app, this would come from the URL param or user's attendees
        const response = await axios.get('/api/registration/attendees/1/qr');
        attendee.value = response.data.attendee;
        qrCodeUrl.value = response.data.qr_code_url;
        qrToken.value = response.data.qr_token || 'token-will-be-provided';
      } catch (err) {
        console.error('Error fetching QR:', err);
        error.value = err.response?.data?.message || 'Failed to load QR badge';
      } finally {
        loading.value = false;
      }
    }

    const ticketTypeBadgeClass = (type) => {
      const classes = {
        general: 'badge-info',
        vip: 'badge-success',
        speaker: 'badge-warning',
        press: 'badge-info',
        organizer: 'badge-primary',
        volunteer: 'badge-success',
      };
      return classes[type] || 'badge';
    };

    const statusBadgeClass = (status) => {
      const classes = {
        registered: 'badge-info',
        checked_in: 'badge-success',
        checked_out: 'badge-warning',
        cancelled: 'badge-danger',
      };
      return classes[status] || 'badge';
    };

    async function downloadQR() {
      if (qrCodeUrl.value) {
        const link = document.createElement('a');
        link.href = qrCodeUrl.value;
        link.download = `qr-badge-${attendee.value?.name || 'attendee'}.png`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
      }
    }

    fetchQR();

    return {
      attendee,
      qrCodeUrl,
      qrToken,
      loading,
      error,
      ticketTypeBadgeClass,
      statusBadgeClass,
      downloadQR,
    };
  },
};
</script>