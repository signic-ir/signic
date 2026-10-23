<template>
  <div class="space-y-6">
    <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
      <h2 class="text-xl font-bold text-slate-900 mb-4">Check In / Out</h2>
      <p class="text-slate-600">Manage attendance status for your event</p>
    </div>

    <div class="bg-gradient-to-r from-indigo-50 to-purple-50 rounded-lg p-6">
      <h3 class="text-lg font-semibold text-indigo-900 mb-3">Check In</h3>
      <p class="text-sm text-slate-500">Scan the QR code or enter your attendee ID to check in</p>
      <div class="flex gap-2">
        <button
          @click="checkIn"
          class="btn btn-primary"
          :disabled="!attendee || !qrCodeUrl"
        >
          Check In
        </button>
        <button
          @click="checkOut"
          class="btn btn-secondary"
          :disabled="!attendee || !qrCodeUrl"
        >
          Check Out
        </button>
      </div>
    </div>

    <div class="bg-gradient-to-r from-indigo-50 to-purple-50 rounded-lg p-6">
      <h3 class="text-lg font-semibold text-indigo-900 mb-3">Check Out</h3>
      <p class="text-sm text-slate-500">Leave the venue and update your status</p>
      <div class="flex gap-2">
        <button
          @click="checkOut"
          class="btn btn-primary"
          :disabled="!attendee || !qrCodeUrl"
        >
          Check Out
        </button>
      </div>
    </div>

    <div class="text-center py-8">
      <p class="text-slate-500">
        Currently no attendees are checked in. Once you register attendees, you can check them in/out.
      </p>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import { requireAuth } from '../composables/useGuard.js';

export default {
  name: 'CheckInPage',
  setup() {
    requireAuth();
    const attendee = ref(null);
    const qrCodeUrl = ref('');
    const qrToken = ref('');
    const turnstileId = ref('');
    const turnstiles = ref([]);
    const loading = ref(true);
    const error = ref('');

    async function loadAttendees() {
      try {
        const response = await axios.get('/api/registration/attendees');
        const attendees = response.data.attendees || response.data.data || [];
        if (attendees.length > 0) {
          attendee.value = attendees[0];
          await loadQrCode(attendee.value.id);
        }
      } catch (err) {
        error.value = err.response?.data?.message || 'Failed to load attendees.';
      }
    }

    async function loadTurnstiles() {
      try {
        const response = await axios.get('/api/access/turnstiles');
        turnstiles.value = response.data.turnstiles || response.data.data || [];
        if (turnstiles.value.length > 0) {
          turnstileId.value = turnstiles.value[0].id;
        }
      } catch (err) {
        console.error('Failed to load turnstiles:', err);
      }
    }

    async function loadQrCode(attendeeId) {
      try {
        const response = await axios.get(`/api/registration/attendees/${attendeeId}/qr`);
        qrToken.value = response.data.qr_token || response.data.qr_code;
        qrCodeUrl.value = response.data.qr_code_url || '';
      } catch (err) {
        console.error('Failed to load QR code:', err);
      }
    }

    async function checkIn() {
      if (!attendee.value || !qrCodeUrl.value) {
        error.value = 'Please register an attendee first and get a QR code.';
        return;
      }

      loading.value = true;
      error.value = '';

      try {
        const response = await axios.post('/api/access/checkin', {
          qr_token: qrToken.value,
          turnstile_id: turnstileId.value,
        });

        if (response.data.success) {
          attendee.value = response.data.attendee || null;
          qrCodeUrl.value = response.data.qr_code_url || '';
          qrToken.value = response.data.qr_token || '';
        } else {
          throw new Error(response.data.message || 'Check-in failed');
        }
      } catch (err) {
        console.error('Check-in error:', err);
        error.value = err.response?.data?.message || 'Failed to check in. Please try again.';
      } finally {
        loading.value = false;
      }
    }

    async function checkOut() {
      if (!attendee.value || !qrCodeUrl.value) {
        error.value = 'Please register an attendee first and get a QR code.';
        return;
      }

      loading.value = true;
      error.value = '';

      try {
        const response = await axios.post('/api/access/checkout', {
          qr_token: qrToken.value,
          turnstile_id: turnstileId.value,
        });

        if (response.data.success) {
          attendee.value = null;
          qrCodeUrl.value = '';
          qrToken.value = '';
        } else {
          throw new Error(response.data.message || 'Check-out failed');
        }
      } catch (err) {
        console.error('Check-out error:', err);
        error.value = err.response?.data?.message || 'Failed to check out. Please try again.';
      } finally {
        loading.value = false;
      }
    }

    onMounted(() => {
      loadAttendees();
      loadTurnstiles();
    });

    return {
      attendee,
      qrCodeUrl,
      qrToken,
      turnstileId,
      turnstiles,
      loading,
      error,
      checkIn,
      checkOut,
    };
  },
};
</script>
