<template>
  <div class="min-h-screen bg-slate-50">
    <nav v-if="user" class="bg-white shadow-sm border-b border-slate-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex">
            <div class="flex-shrink-0 flex items-center">
              <span class="text-2xl">🎫</span>
              <span class="ml-2 text-xl font-bold text-slate-900">Signic</span>
            </div>
            <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
              <Link
                v-for="item in navItems"
                :key="item.name"
                :href="item.href"
                :class="[
                  'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium',
                  isActive(item.href)
                    ? 'border-indigo-500 text-slate-900'
                    : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'
                ]"
              >
                {{ item.label }}
              </Link>
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

    <nav v-else class="bg-white shadow-sm border-b border-slate-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex">
            <div class="flex-shrink-0 flex items-center">
              <span class="text-2xl">🎫</span>
              <span class="ml-2 text-xl font-bold text-slate-900">Signic</span>
            </div>
            <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
              <Link
                v-for="item in publicNavItems"
                :key="item.name"
                :href="item.href"
                :class="[
                  'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium',
                  isActive(item.href)
                    ? 'border-indigo-500 text-slate-900'
                    : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'
                ]"
              >
                {{ item.label }}
              </Link>
            </div>
          </div>
          <div class="flex items-center">
            <Link
              href="/login"
              class="text-sm font-medium text-slate-700 hover:text-indigo-600"
              >Login</Link
            >
          </div>
        </div>
      </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <slot />
    </main>

    <footer>
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <p class="text-center text-sm text-slate-500">
          © 2024 Signic. Event Registration &amp; Check-in Platform.
        </p>
      </div>
    </footer>
  </div>
  </template>

  import { Link } from '@inertiajs/vue3';
  import { useAuth } from './composables/useAuth.js';

  export default {
    name: 'App',
    setup() {
      const { user, logout } = useAuth();

      const navItems = [
        { name: 'dashboard', label: 'Dashboard', href: '/dashboard' },
        { name: 'register', label: 'Register', href: '/register' },
        { name: 'qr', label: 'My QR', href: '/qr' },
        { name: 'checkin', label: 'Check In/Out', href: '/checkin' },
        { name: 'exhibitor', label: 'Exhibitors', href: '/exhibitors' },
      ];

      const publicNavItems = [
        { name: 'landing', label: 'Home', href: '/' },
        { name: 'qr-guide', label: 'QR Guide', href: '/qr-guide' },
      ];

      const isActive = (href) => {
        return window.location.pathname === href;
      };

      return { user, logout, navItems, publicNavItems, isActive };
    },
  };
  </script>