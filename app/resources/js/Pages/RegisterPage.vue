<template>
  <div class="min-h-screen bg-slate-50 py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
        <div class="mb-6">
          <h1 class="text-2xl font-bold text-slate-900">Register New Attendee</h1>
          <p class="text-slate-600 mt-1">Fill out the form below to register as an attendee</p>
        </div>

        <form @submit.prevent="handleSubmit" class="space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-2">
              <label for="name" class="block text-sm font-medium text-slate-700">Full Name</label>
              <input
                id="name"
                v-model="form.name"
                type="text"
                class="input"
                placeholder="John Doe"
                required
              />
              <p v-if="errors.name" class="input-error">{{ errors.name }}</p>
            </div>

            <div class="space-y-2">
              <label for="phone" class="block text-sm font-medium text-slate-700">Phone Number</label>
              <input
                id="phone"
                v-model="form.phone"
                type="tel"
                class="input"
                placeholder="+1 (555) 123-4567"
                required
              />
              <p v-if="errors.phone" class="input-error">{{ errors.phone }}</p>
            </div>

            <div class="space-y-2">
              <label for="email" class="block text-sm font-medium text-slate-700">Email (Optional)</label>
              <input
                id="email"
                v-model="form.email"
                type="email"
                class="input"
                placeholder="john@example.com"
              />
              <p v-if="errors.email" class="input-error">{{ errors.email }}</p>
            </div>

            <div class="space-y-2">
              <label for="company" class="block text-sm font-medium text-slate-700">Company</label>
              <input
                id="company"
                v-model="form.company"
                type="text"
                class="input"
                placeholder="Acme Corp"
              />
              <p v-if="errors.company" class="input-error">{{ errors.company }}</p>
            </div>

            <div class="space-y-2">
              <label for="job_title" class="block text-sm font-medium text-slate-700">Job Title</label>
              <input
                id="job_title"
                v-model="form.job_title"
                type="text"
                class="input"
                placeholder="Software Engineer"
              />
              <p v-if="errors.job_title" class="input-error">{{ errors.job_title }}</p>
            </div>

            <div class="space-y-2">
              <label for="event_id" class="block text-sm font-medium text-slate-700">Event</label>
              <select
                id="event_id"
                v-model="form.event_id"
                class="input"
                :disabled="events.length === 1"
              >
                <option value="" disabled>Select an event</option>
                <option v-for="event in events" :key="event.id" :value="event.id">
                  {{ event.name }}
                </option>
              </select>
              <p v-if="errors.event_id" class="input-error">{{ errors.event_id }}</p>
            </div>

            <div class="space-y-2">
              <label for="ticket_type" class="block text-sm font-medium text-slate-700">Ticket Type</label>
              <select
                id="ticket_type"
                v-model="form.ticket_type"
                class="input"
                required
              >
                <option value="">Select a ticket type</option>
                <option value="GENERAL">General</option>
                <option value="VIP">VIP</option>
                <option value="SPEAKER">Speaker</option>
                <option value="PRESS">Press</option>
                <option value="ORGANIZER">Organizer</option>
                <option value="VOLUNTEER">Volunteer</option>
              </select>
              <p v-if="errors.ticket_type" class="input-error">{{ errors.ticket_type }}</p>
            </div>
          </div>

          <div>
            <label for="metadata" class="block text-sm font-medium text-slate-700">Additional Info (Optional)</label>
            <textarea
              id="metadata"
              v-model="form.metadata"
              class="input"
              rows="3"
              placeholder='{"company": "Acme", "department": "Engineering"}'
            ></textarea>
            <p v-if="errors.metadata" class="input-error">{{ errors.metadata }}</p>
          </div>

          <div class="flex justify-end space-x-4">
            <button
              type="button"
              @click="goBack"
              class="btn btn-secondary"
            >
              Cancel
            </button>
            <button
              type="submit"
              class="btn btn-primary"
              :disabled="submitting"
            >
              <template v-if="submitting">
                Creating...
              </template>
              <template v-else>
                Register Attendee
              </template>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed } from 'vue';
import { useAuth } from '../composables/useAuth.js';
import axios from 'axios';

export default {
  name: 'RegisterPage',
  setup() {
    const { user } = useAuth();
    const form = ref({
      event_id: '',
      name: '',
      phone: '',
      email: '',
      company: '',
      job_title: '',
      ticket_type: '',
      metadata: '',
    });
    const events = ref([]);
    const errors = ref({});
    const submitting = ref(false);

    // The event is not hardcoded: it is selected from the live event list.
    // When only one event exists it is picked automatically; otherwise the
    // user chooses one.
    async function loadEvents() {
      try {
        const response = await axios.get('/api/registration/events');
        events.value = response.data.events || response.data.data || [];

        if (events.value.length === 1) {
          form.value.event_id = events.value[0].id;
        } else if (events.value.length > 1) {
          form.value.event_id = '';
        }
      } catch (err) {
        console.error('Error fetching events:', err);
      }
    }

    loadEvents();

    const metadata = computed(() => {
      try {
        return form.value.metadata ? JSON.parse(form.value.metadata) : {};
      } catch (e) {
        return {};
      }
    });

    const phoneError = computed(() => {
      if (form.value.phone) {
        const phoneRegex = /^\+?([0-9]{10,15})$/;
        return phoneRegex.test(form.value.phone) ? '' : 'Phone number must contain 10-15 digits, optionally starting with +';
      }
      return '';
    });

    async function handleSubmit() {
      // Clear errors
      errors.value = {};

      // Validate form
      if (!form.value.name) {
        errors.value.name = 'Full name is required';
      }
      if (!form.value.phone) {
        errors.value.phone = 'Phone number is required';
      } else if (!/^\+?([0-9]{10,15})$/.test(form.value.phone)) {
        errors.value.phone = 'Phone number must contain 10-15 digits, optionally starting with +';
      }

      if (!form.value.ticket_type) {
        errors.value.ticket_type = 'Ticket type is required';
      }

      if (!form.value.event_id) {
        errors.value.event_id = 'Event is required';
      }

      if (Object.keys(errors.value).length > 0) {
        return;
      }

      submitting.value = true;

      try {
        const response = await axios.post('/api/registration/attendees', {
          ...form.value,
          metadata: metadata.value,
        });

        // Show success and redirect to QR page
        window.location.href = `/qr?attendee_id=${response.data.attendee.id}`;
      } catch (error) {
        console.error('Registration error:', error);
        if (error.response?.data?.errors) {
          errors.value = error.response.data.errors;
        } else {
          errors.value = {
            general: error.response?.data?.message || 'Failed to register. Please try again.',
          };
        }
      } finally {
        submitting.value = false;
      }
    }

    function goBack() {
      window.history.back();
    }

    return {
      user,
      form,
      events,
      errors,
      submitting,
      handleSubmit,
      goBack,
      phoneError,
    };
  },
};
</script>