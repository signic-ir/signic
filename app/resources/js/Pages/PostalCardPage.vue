<template>
  <div class="min-h-screen bg-slate-100" v-if="!attendeeId">
    <header class="bg-white shadow-sm border-b border-slate-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
          <div class="flex items-center">
            <Link href="/" class="flex items-center">
              <span class="text-2xl">🎫</span>
              <span class="ml-2 text-xl font-bold text-slate-900">ain1.ir</span>
            </Link>
          </div>
        </div>
      </div>
    </header>

    <main class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
      <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-8 text-center">
        <span class="text-5xl block mb-4">📭</span>
        <h1 class="text-2xl font-bold text-slate-900 mb-4">Print Your Postal Card</h1>
        <p class="text-slate-600 mb-6">
          Enter your attendee ID to generate a printable postal card with your QR code.
        </p>
        <div class="space-y-4">
          <input
            v-model="enteredId"
            type="number"
            placeholder="Attendee ID (e.g. 5)"
            class="input w-full text-center text-lg"
            min="1"
          />
          <button
            @click="submitId"
            :disabled="!enteredId"
            class="w-full btn btn-primary py-3 text-lg"
          >
            Generate Postal Card
          </button>
        </div>
      </div>
    </main>
  </div>

  <div v-else class="min-h-screen bg-slate-100 print:bg-white">
    <!-- On-screen controls (hidden when printing) -->
    <div class="print:hidden">
      <header class="bg-white shadow-sm border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div class="flex items-center justify-between h-16">
            <div class="flex items-center">
              <Link href="/" class="flex items-center">
                <span class="text-2xl">🎫</span>
                <span class="ml-2 text-xl font-bold text-slate-900">ain1.ir</span>
              </Link>
            </div>
            <div class="flex gap-3">
              <Link href="/qr-guide" class="btn btn-secondary">Back to Guide</Link>
              <button @click="downloadPDF" class="btn btn-primary" :disabled="!qrCodeUrl">
                Download PDF
              </button>
              <button @click="printCard" class="btn btn-primary" :disabled="!qrCodeUrl">
                Print Card
              </button>
            </div>
          </div>
        </div>
      </header>

      <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div v-if="loading" class="text-center py-12">
          <div class="inline-block animate-spin rounded-full h-10 w-10 border-t-2 border-s-2 border-indigo-500"></div>
          <p class="mt-4 text-slate-500">Generating your postal card...</p>
        </div>

        <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-xl p-6 text-center">
          <span class="text-red-800 font-semibold">{{ error }}</span>
        </div>

        <div v-else-if="qrCodeUrl" class="text-center">
          <h1 class="text-2xl font-bold text-slate-900 mb-2">Postal Card Preview</h1>
          <p class="text-slate-600 mb-6">
            Review your card below, then click "Print" or "Download PDF".
          </p>
        </div>
      </main>
    </div>

    <!-- Printable postal card -->
    <div v-if="qrCodeUrl" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="max-w-md mx-auto print:mx-0">
        <div class="postal-card print:break-inside-avoid">
          <div class="postal-card-header">
            <span class="text-2xl">🎫</span>
            <span class="text-2xl font-bold">ain1.ir</span>
          </div>

          <div class="postal-card-body">
            <h2 class="postal-card-title">Event Badge</h2>
            <p class="postal-card-subtitle">Tech Summit 2026</p>

            <div class="postal-card-qr">
              <img :src="qrCodeUrl" alt="QR Code" class="w-48 h-48 object-contain" />
            </div>

            <div class="postal-card-details">
              <div class="postal-card-field">
                <span class="postal-card-label">Name</span>
                <span class="postal-card-value">{{ attendee?.name || 'Attendee' }}</span>
              </div>
              <div class="postal-card-field">
                <span class="postal-card-label">Ticket</span>
                <span class="postal-card-value">
                  <span :class="ticketTypeBadgeClass(attendee?.ticket_type)">
                    {{ attendee?.ticket_type || 'GENERAL' }}
                  </span>
                </span>
              </div>
              <div v-if="attendee?.company" class="postal-card-field">
                <span class="postal-card-label">Company</span>
                <span class="postal-card-value">{{ attendee.company }}</span>
              </div>
              <div v-if="attendee?.email" class="postal-card-field">
                <span class="postal-card-label">Email</span>
                <span class="postal-card-value">{{ attendee.email }}</span>
              </div>
              <div class="postal-card-field">
                <span class="postal-card-label">ID</span>
                <span class="postal-card-value">#{{ attendee?.id || attendeeId }}</span>
              </div>
            </div>

            <div class="postal-card-footer">
              <p class="postal-card-scan-text">Scan at venue entrance</p>
              <p class="postal-card-date">Oct 24-26, 2026 • Tehran</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import axios from 'axios';

export default {
  name: 'PostalCardPage',
  props: {
    attendeeId: {
      type: [String, Number],
      default: null,
    },
  },
  setup(props) {
    const enteredId = ref(props.attendeeId || '');
    const attendee = ref(null);
    const qrCodeUrl = ref('');
    const loading = ref(false);
    const error = ref('');

    function ticketTypeBadgeClass(type) {
      const classes = {
        GENERAL: 'text-indigo-600 bg-indigo-50',
        VIP: 'text-yellow-600 bg-yellow-50',
        SPEAKER: 'text-purple-600 bg-purple-50',
        PRESS: 'text-blue-600 bg-blue-50',
        ORGANIZER: 'text-emerald-600 bg-emerald-50',
        VOLUNTEER: 'text-teal-600 bg-teal-50',
      };
      return classes[type] || 'text-slate-600 bg-slate-100';
    }

    function submitId() {
      if (enteredId.value) {
        window.location.href = `/postal-card/${enteredId.value}`;
      }
    }

    async function fetchQr() {
      const id = props.attendeeId;
      if (!id) return;

      loading.value = true;
      error.value = '';

      try {
        const response = await axios.get(`/api/registration/attendees/${id}/qr`);
        attendee.value = response.data.attendee;
        qrCodeUrl.value = response.data.qr_code_url;
      } catch (err) {
        error.value = err.response?.data?.message || 'Failed to generate QR code. Please verify your attendee ID.';
      } finally {
        loading.value = false;
      }
    }

    function printCard() {
      window.print();
    }

    function downloadPDF() {
      window.print();
    }

    onMounted(() => {
      fetchQr();
    });

    return {
      attendee,
      qrCodeUrl,
      loading,
      error,
      enteredId,
      submitId,
      printCard,
      downloadPDF,
      ticketTypeBadgeClass,
    };
  },
};
</script>

<style>
/* Postal card print styles — A6 format */
.postal-card {
  border: 2px solid #d1d5db;
  border-radius: 16px;
  padding: 24px;
  background: white;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  margin: 24px 0;
}

.postal-card-header {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 16px;
  padding-bottom: 12px;
  border-bottom: 1px dashed #d1d5db;
}

.postal-card-body {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.postal-card-title {
  font-size: 20px;
  font-weight: 700;
  color: #1e293b;
  margin-bottom: 4px;
}

.postal-card-subtitle {
  font-size: 14px;
  color: #64748b;
  margin-bottom: 20px;
}

.postal-card-qr {
  margin: 16px 0;
}

.postal-card-details {
  width: 100%;
  margin-top: 20px;
  space-y: 8px;
}

.postal-card-field {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 6px 0;
  border-bottom: 1px solid #f1f5f9;
}

.postal-card-label {
  font-size: 12px;
  color: #94a3b8;
  font-weight: 600;
}

.postal-card-value {
  font-size: 14px;
  color: #1e293b;
  font-weight: 500;
}

.postal-card-footer {
  margin-top: 20px;
  text-align: center;
  width: 100%;
}

.postal-card-scan-text {
  font-size: 12px;
  color: #64748b;
  font-style: italic;
}

.postal-card-date {
  font-size: 12px;
  color: #94a3b8;
  margin-top: 4px;
}

/* Print-specific styling */
@media print {
  body {
    background: white;
  }
  
  .print\\:hidden {
    display: none !important;
  }

  .postal-card {
    border: none;
    box-shadow: none;
    margin: 0;
  }
}
</style>