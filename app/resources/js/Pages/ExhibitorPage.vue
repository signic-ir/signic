<template>
  <div class="space-y-6">
    <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
      <h2 class="text-xl font-bold text-slate-900 mb-4">Exhibitors</h2>
      <p class="text-slate-600">Manage exhibitor information for the event</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <div v-for="exhibitor in exhibitors" :key="exhibitor.id" class="card p-6">
        <div class="flex items-center">
          <div class="p-3 bg-indigo-100 rounded-lg">
            <span class="text-xl">🏢</span>
          </div>
          <div class="ml-4">
            <h3 class="text-lg font-semibold text-slate-900">{{ exhibitor.name }}</h3>
            <p class="text-sm text-slate-500">{{ exhibitor.company }}</p>
          </div>
        </div>

        <div class="mt-4 pt-4 border-t border-slate-200">
          <p class="text-sm text-slate-500">Contact</p>
          <p class="text-slate-900">{{ exhibitor.contact_email }}</p>
          <p v-if="exhibitor.contact_phone" class="text-sm text-slate-500">{{ exhibitor.contact_phone }}</p>
        </div>

        <div class="mt-4 text-center">
          <router-link :to="`/exhibitor/${exhibitor.id}`" class="text-indigo-600 hover:text-indigo-500 text-sm">
            View Details
          </router-link>
        </div>
      </div>

      <div v-if="!exhibitors.length" class="col-span-3 text-center py-12">
        <span class="text-4xl mb-4 block">🏢</span>
        <h3 class="text-lg font-semibold text-slate-900 mb-2">No Exhibitors Found</h3>
        <p class="text-slate-500">Start by adding exhibitor information</p>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed } from 'vue';
import axios from 'axios';

export default {
  name: 'ExhibitorPage',
  setup() {
    const exhibitors = ref([]);

    async function fetchExhibitors() {
      try {
        const response = await axios.get('/api/exhibition/exhibitors');
        exhibitors.value = response.data.exhibitors || [];
      } catch (err) {
        console.error('Error fetching exhibitors:', err);
      }
    }

    fetchExhibitors();

    return {
      exhibitors,
    };
  },
};
</script>