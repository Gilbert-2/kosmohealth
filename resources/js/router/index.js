import Vue from 'vue';
import Router from 'vue-router';

Vue.use(Router);

export default new Router({
    mode: 'history',
    base: '/app',
    routes: [
        // Auth Routes
        {
            path: '/auth/login',
            name: 'login',
            component: () => import('../views/auth/login.vue'),
            meta: {
                auth: false,
                title: 'Login'
            }
        },
        {
            path: '/auth/login-glassmorphism',
            name: 'login-glassmorphism',
            component: () => import('../views/auth/login-glassmorphism.vue'),
            meta: {
                auth: false,
                title: 'Login'
            }
        },
        {
            path: '/auth/login-email-otp',
            name: 'login-email-otp',
            component: () => import('../views/auth/login-email-otp.vue'),
            meta: {
                auth: false,
                title: 'Login with Email OTP'
            }
        },
        {
            path: '/auth/login-sms-otp',
            name: 'login-sms-otp',
            component: () => import('../views/auth/login-sms-otp.vue'),
            meta: {
                auth: false,
                title: 'Login with SMS OTP'
            }
        },
        {
            path: '/auth/register',
            name: 'register',
            component: () => import('../views/auth/register.vue'),
            meta: {
                auth: false,
                title: 'Register'
            }
        },
        {
            path: '/auth/password',
            name: 'password',
            component: () => import('../views/auth/password.vue'),
            meta: {
                auth: false,
                title: 'Reset Password'
            }
        },
        {
            path: '/auth/security',
            name: 'authSecurity',
            component: () => import('../views/auth/security.vue'),
            meta: {
                auth: true,
                title: 'Two Factor Security'
            }
        },
        {
            path: '/auth/lock',
            name: 'lock',
            component: () => import('../views/auth/lock.vue'),
            meta: {
                auth: true,
                title: 'Lock Screen'
            }
        },

        // App Routes
        {
            path: '/dashboard',
            name: 'appDashboard',
            component: () => import('../views/app/dashboard/index.vue'),
            meta: {
                auth: true,
                title: 'Dashboard'
            }
        },
        // KYC route removed
        {
            path: '/period-tracker',
            name: 'period-tracker',
            component: () => import('../views/app/period-tracker/index.vue'),
            meta: {
                auth: true,
                title: 'Period Tracker'
            }
        },

        // Admin KYC Routes removed

        // Meeting Routes
        {
            path: '/live/meetings/:uuid',
            name: 'meetingLive',
            component: () => import('../views/app/meeting/live.vue'),
            meta: {
                auth: true,
                title: 'Live Meeting'
            }
        },
        {
            path: '/live/meetings-flo/:uuid',
            name: 'meetingLiveFloDesign',
            component: () => import('../views/app/meeting/live-with-flo-design.vue'),
            meta: {
                auth: true,
                title: 'Live Meeting (Flo Design)'
            }
        },
        {
            path: '/live/meetings-enhanced/:uuid',
            name: 'meetingLiveEnhanced',
            component: () => import('../views/app/meeting/enhanced-live.vue'),
            meta: {
                auth: true,
                title: 'Live Meeting (Enhanced Audio & Video)'
            }
        },
        {
            path: '/live/meetings-gesture/:uuid',
            name: 'meetingLiveGesture',
            component: () => import('../views/app/meeting/gesture-enabled-live.vue'),
            meta: {
                auth: true,
                title: 'Live Meeting (Gesture Recognition)'
            }
        },

        // Default route
        {
            path: '*',
            redirect: '/auth/login-glassmorphism'
        }
    ]
});