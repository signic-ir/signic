import { createRouter, createWebHistory } from 'vue-router';
import { useAuth } from '../composables/useAuth.js';

const routes = [
    {
        path: '/',
        name: 'dashboard',
        component: () => import('../Pages/DashboardPage.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/login',
        name: 'login',
        component: () => import('../Pages/LoginPage.vue'),
        meta: { guest: true },
    },
    {
        path: '/register',
        name: 'register',
        component: () => import('../Pages/RegisterPage.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/qr',
        name: 'qr',
        component: () => import('../Pages/AttendeeQRPage.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/checkin',
        name: 'checkin',
        component: () => import('../Pages/CheckInPage.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/checkout',
        name: 'checkout',
        component: () => import('../Pages/CheckOutPage.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/exhibitors',
        name: 'exhibitors',
        component: () => import('../Pages/ExhibitorPage.vue'),
        meta: { requiresAuth: true },
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach(async (to, from, next) => {
    const { user, isAuthenticated, getCurrentUser } = useAuth();

    // Handle guest-only routes
    if (to.meta.guest) {
        if (isAuthenticated) {
            next('/');
        } else {
            next();
        }
        return;
    }

    // Handle auth-required routes
    if (to.meta.requiresAuth) {
        if (!isAuthenticated) {
            // If we have a token but no user data, fetch it
            if (token.value) {
                await getCurrentUser();
            }
            next('/login');
        } else {
            // Fetch current user data
            if (!user.value) {
                await getCurrentUser();
            }
            next();
        }
        return;
    }

    next();
});

export { router };