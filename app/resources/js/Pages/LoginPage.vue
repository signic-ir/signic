<template>
  <div class="min-h-screen flex items-center justify-center bg-slate-50">
    <div class="w-full max-w-sm space-y-8">
      <div>
        <div class="flex items-center justify-center">
          <span class="text-4xl">🎫</span>
          <span class="ml-3 text-2xl font-bold text-slate-900">Signic</span>
        </div>
        <h2 class="mt-6 text-center text-2xl font-bold leading-9 tracking-tight text-slate-900">
          Login to your account
        </h2>
        <p class="mt-2 text-center text-sm text-slate-600">
          Use your phone number and OTP to sign in
        </p>
      </div>

      <form class="space-y-6" @submit.prevent="handleLogin">
        <div class="space-y-4">
          <div>
            <label for="phone" class="block text-sm font-medium text-slate-700">
              Phone Number
            </label>
            <div class="mt-1">
              <input
                id="phone"
                name="phone"
                type="tel"
                autocomplete="tel"
                class="block w-full rounded-md border-0 py-1.5 pl-3 pr-10 text-base ring-offset-2 ring-indigo-600 focus:ring-2 focus:ring-indigo-200 sm:text-sm sm:leading-6"
                placeholder="+1 (555) 123-4567"
                v-model="form.phone"
                required
              />
            </div>
          </div>

          <div>
            <label for="otp" class="block text-sm font-medium text-slate-700">
              OTP Code
            </label>
            <div class="mt-1">
              <input
                id="otp"
                name="otp"
                type="text"
                autocomplete="one-time-code"
                inputmode="numeric"
                class="block w-full rounded-md border-0 py-1.5 pl-3 pr-10 text-base ring-offset-2 ring-indigo-600 focus:ring-2 focus:ring-indigo-200 sm:text-sm sm:leading-6"
                placeholder="123456"
                v-model="form.otp"
                required
                maxlength="6"
              />
            </div>
          </div>
        </div>

        <div v-if="error" class="rounded-md bg-red-50 p-4 text-sm text-red-800" role="alert">
          {{ error }}
        </div>

        <div>
          <button
            type="submit"
            :disabled="submitting"
            class="flex w-full justify-center rounded-md bg-indigo-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-200 focus:ring-offset-2 disabled:opacity-50 transition"
          >
            <template v-if="submitting">
              Signing in...
            </template>
            <template v-else>
              Sign In
            </template>
          </button>
        </div>
      </form>

      <div class="text-center text-sm text-slate-500">
        Need an account? <Link href="/register" class="font-medium text-indigo-600 hover:text-indigo-500">Register</Link>
      </div>
    </div>
  </div>
</template>

<script>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useAuth } from '../composables/useAuth.js';
import axios from 'axios';

export default {
  name: 'LoginPage',
  setup() {
    const { login } = useAuth();
    const form = ref({
      phone: '',
      otp: '',
    });
    const submitting = ref(false);
    const error = ref('');

    async function handleLogin() {
      submitting.value = true;
      error.value = '';

      try {
        // First, request OTP (if not already sent)
        await axios.post('/api/identity/otp/request', {
          phone: form.value.phone,
        });

        // For demo purposes, we'll simulate verification
        // In a real app, you'd have a separate verification step
        // Or we'd combine send and verify in one flow

        // Actually, let's implement the full flow:
        // 1. Send OTP
        // 2. User enters OTP
        // 3. Verify OTP
        const verifyResponse = await axios.post('/api/identity/otp/verify', {
          phone: form.value.phone,
          otp: form.value.otp,
        });

        if (verifyResponse.data.access_token) {
          login(
            verifyResponse.data.access_token,
            verifyResponse.data.user
          );
        } else {
          throw new Error('Login failed');
        }
      } catch (err) {
        console.error('Login error:', err);
        error.value = err.response?.data?.message || 'Invalid phone number or OTP. Please try again.';
      } finally {
        submitting.value = false;
      }
    }

    return { form, submitting, error, handleLogin };
  },
};
</script>