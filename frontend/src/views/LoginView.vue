<script setup>
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AuthLayout from '@/components/AuthLayout.vue'
import { auth } from '@/auth/session'
import { getErrorMessage } from '@/api/client'
import { useI18n } from 'vue-i18n'

const router = useRouter()
const route = useRoute()
const email = ref('')
const password = ref('')
const loading = ref(false)
const error = ref('')
const { t } = useI18n({ useScope: 'global' })

async function submit() {
  loading.value = true
  error.value = ''
  try {
    await auth.login(email.value, password.value)
    await router.push(typeof route.query.redirect === 'string' ? route.query.redirect : { name: 'surveys' })
  } catch (requestError) {
    error.value = requestError.status === 401 ? t('errors.credentials') : getErrorMessage(requestError)
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <AuthLayout>
    <span class="eyebrow">{{ t('auth.login.eyebrow') }}</span>
    <h1>{{ t('auth.login.title') }}</h1>
    <p class="auth-intro">{{ t('auth.login.intro') }}</p>
    <div v-if="error" class="alert" role="alert">{{ error }}</div>
    <form @submit.prevent="submit">
      <div class="field">
        <label for="login-email">{{ t('auth.login.email') }}</label>
        <input id="login-email" v-model.trim="email" class="input" type="email" autocomplete="email" :placeholder="t('auth.login.emailPlaceholder', { at: '@' })" required />
      </div>
      <div class="field">
        <label for="login-password">{{ t('auth.login.password') }}</label>
        <input id="login-password" v-model="password" class="input" type="password" autocomplete="current-password" :placeholder="t('auth.login.passwordPlaceholder')" required />
      </div>
      <button class="button button--dark button--wide auth-submit" :disabled="loading">
        <span v-if="loading" class="spinner"></span>
        {{ loading ? t('auth.login.submitting') : t('auth.login.submit') }}
      </button>
    </form>
    <p class="auth-switch">{{ t('auth.login.noAccount') }} <RouterLink to="/register">{{ t('auth.login.createAccount') }}</RouterLink></p>
  </AuthLayout>
</template>

<style scoped>
h1 { margin-bottom: .6rem; font-size: 2rem; }
.auth-intro { margin-bottom: 1.8rem; color: var(--ink-soft); line-height: 1.55; }
.auth-submit { margin-top: 1.4rem; }
.auth-submit .spinner { border-color: rgba(255,255,255,.25); border-top-color: white; }
.auth-switch { margin: 1.4rem 0 0; color: var(--ink-soft); text-align: center; font-size: .84rem; }
.auth-switch a { font-weight: 750; }
</style>
