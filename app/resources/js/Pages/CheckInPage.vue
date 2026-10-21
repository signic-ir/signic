<template>
  <div class="space-y-6">
    <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
      <h2 class="text-xl font-bold text-slate-900 mb-4">Check In / Out</h2>
      <p class="text-slate-600">Manage attendance status for your event</p>
    </div>

    <!-- Check In -->
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

    <!-- Check Out -->
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
import { ref, computed } from 'vue';
import axios from 'axios';

export default {
  name: 'CheckInPage',
  setup() {
    const attendee = ref(null);
    const qrCodeUrl = ref('');
    const qrToken = ref('');
    const loading = ref(true);
    const error = ref('');

    async function checkIn() {
      if (!attendee || !qrCodeUrl.value) {
        error.value = 'Please register an attendee first and get a QR code.';
        return;
      }

      loading.value = true;
      error.value = '';

      try {
        const response = await axios.post('/api/access/checkin', {
          token: qrToken.value,
          attendee_id: attendee.value.id,
        });

        if (response.data.success) {
          attendee.value = response.data.attendee;
          qrCodeUrl.value = response.data.qr_code_url;
          qrToken.value = response.data.qr_token;
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
      if (!attendee || !qrCodeUrl.value) {
        error.value = 'Please register an attendee first and get a QR code.';
        return;
      }

      loading.value = true;
      error.value = '';

      try {
        const response = await axios.post('/api/access/checkout', {
          token: qrToken.value,
          attendee_id: attendee.value.id,
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

    return {
      attendee,
      qrCodeUrl,
      qrToken,
      loading,
      error,
      checkIn,
      checkOut,
    };
  },
};
</script>