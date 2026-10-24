import { useAuth } from './useAuth.js';

/**
 * Client-side auth guard. Every protected Inertia page calls this in its
 * `setup()`; when no `signic_token` is present it forces a full redirect to
 * `/login`. The spec (§3.4) requires per-page guards rather than a router
 * middleware because there is no client router.
 */
export function requireAuth() {
  const { isAuthenticated } = useAuth();

  if (!isAuthenticated.value) {
    // Full page redirect: there is no Inertia router in the bundled version.
    window.location.href = '/login';
  }
}
