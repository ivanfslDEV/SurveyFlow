import { createRouter, createWebHistory } from 'vue-router'
import { auth } from '@/auth/session'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  scrollBehavior: () => ({ top: 0 }),
  routes: [
    {
      path: '/login',
      name: 'login',
      component: () => import('@/views/LoginView.vue'),
      meta: { guestOnly: true },
    },
    {
      path: '/register',
      name: 'register',
      component: () => import('@/views/RegisterView.vue'),
      meta: { guestOnly: true },
    },
    {
      path: '/s/:id',
      name: 'public-survey',
      component: () => import('@/views/PublicSurveyView.vue'),
    },
    {
      path: '/',
      component: () => import('@/layouts/AppShell.vue'),
      meta: { requiresAuth: true },
      children: [
        { path: '', redirect: { name: 'surveys' } },
        { path: 'surveys', name: 'surveys', component: () => import('@/views/SurveysView.vue') },
        { path: 'surveys/new', name: 'survey-create', component: () => import('@/views/CreateSurveyView.vue') },
        { path: 'surveys/:id', name: 'survey-detail', component: () => import('@/views/SurveyDetailView.vue') },
        { path: 'surveys/:id/submissions', name: 'submissions', component: () => import('@/views/SubmissionsView.vue') },
        { path: 'submissions/:id', name: 'submission-detail', component: () => import('@/views/SubmissionDetailView.vue') },
      ],
    },
    { path: '/:pathMatch(.*)*', name: 'not-found', component: () => import('@/views/NotFoundView.vue') },
  ],
})

router.beforeEach(async (to) => {
  await auth.initialize()

  if (to.meta.requiresAuth && !auth.isAuthenticated.value) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }

  if (to.meta.guestOnly && auth.isAuthenticated.value) {
    return { name: 'surveys' }
  }
})

window.addEventListener('surveyflow:session-expired', () => {
  if (router.currentRoute.value.meta.requiresAuth) {
    router.push({
      name: 'login',
      query: { redirect: router.currentRoute.value.fullPath },
    })
  }
})

export default router
