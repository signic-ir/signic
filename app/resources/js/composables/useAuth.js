import { ref, computed } from 'vue';
import axios from 'axios';

const API_BASE = import.meta.env.VITE_API_URL || '/';

export function useAuth() {
  const user = ref(JSON.parse(localStorage.getItem('signic_user') || 'null'));
  const token = ref(localStorage.getItem('signic_token') || null);

  const isAuthenticated = computed(() => !!token.value);

  function login(newToken, newUser) {
    token.value = newToken;
    user.value = newUser;
    localStorage.setItem('signic_token', newToken);
    localStorage.setItem('signic_user', JSON.stringify(newUser));
  }

  function logout() {
    token.value = null;
    user.value = null;
    localStorage.removeItem('signic_token');
    localStorage.removeItem('signic_user');
    if (window.location.pathname === '/login') return;
    window.location.href = '/login';
  }

  async function getCurrentUser() {
    try {
      const response = await axios.get(`${API_BASE}api/identity/me`, {
        headers: {
          Authorization: `Bearer ${token.value}`,
        },
      });
      return response.data.user;
    } catch (error) {
      console.error('Failed to fetch user:', error);
      return null;
    }
  }

  return {
    user,
    token,
    isAuthenticated,
    login,
    logout,
    getCurrentUser,
  };
}