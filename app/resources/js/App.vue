<template>
  <div id="app">
    <nav v-if="user" class="bg-white shadow-sm border-b border-slate-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex">
            <div class="flex-shrink-0 flex items-center">
              <span class="text-2xl">🎫</span>
              <span class="ml-2 text-xl font-bold text-slate-900">Signic</span>
            </div>
            <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
              <router-link
                v-for="item in navItems"
                :key="item.name"
                :to="item.to"
                :class="[
                  'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium',
                  isActive(item.to)
                    ? 'border-indigo-500 text-slate-900'
                    : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'
                ]"
              >
                {{ item.label }}
              </router-link>
            </div>
          </div>
          <div class="flex items-center">
            <span class="text-sm text-slate-700 mr-4">{{ user.name }}</span>
            <button
              @click="logout"
              class="text-sm text-slate-500 hover:text-red-600"
            >
              Logout
            </button>
          </div>
        </div>
      </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <router-view></router-view>
    </main>

    <footer v-if="user" class="bg-white border-t border-slate-200 mt-12">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <p class="text-center text-sm text-slate-500">
          © 2024 Signic. Event Registration & Lead Capture Platform.
        </p>
      </div>
    </footer>
  </div>
</template>

<script>
import { computed } from 'vue';
import { useAuth } from './composables/useAuth.js';

export default {
  name: 'App',
  setup() {
    const { user, logout } = useAuth();

    const navItems = [
      { name: 'dashboard', label: 'Dashboard', to: '/' },
      { name: 'register', label: 'Register', to: '/register' },
      { name: 'qr', label: 'My QR', to: '/qr' },
      { name: 'checkin', label: 'Check In/Out', to: '/checkin' },
      { name: 'exhibitor', label: 'Exhibitors', to: '/exhibitors' },
    ];

    const isActive = (to) => {
      return window.location.pathname === to;
    };

    return { user, logout, navItems, isActive };
  },
};
</script>