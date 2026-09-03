<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import AuthLayout from '@/components/AuthLayout.vue'
import { auth } from '@/auth/session'
import { getErrorMessage } from '@/api/client'
import { useI18n } from 'vue-i18n'

const router = useRouter()
const email = ref('')
const password = ref('')
const loading = ref(false)
const error = ref('')
const { t } = useI18n({ useScope: 'global' })

async function submit() {
  loading.value = true
  error.value = ''
  try {
    await auth.register(email.value, password.value)
    await router.push({ name: 'surveys' })
  } catch (requestError) {
    error.value = getErrorMessage(requestError)
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <AuthLayout>
    <span class="eyebrow">{{ t('auth.register.eyebrow') }}</span>
    <h1>{{ t('auth.register.title') }}</h1>
    <p class="auth-intro">{{ t('auth.register.intro') }}</p>
    <div v-if="error" class="alert" role="alert">{{ error }}</div>
    <form @submit.prevent="submit">
      <div class="field">
        <label for="register-email">{{ t('auth.register.email') }}</label>
        <input id="register-email" v-model.trim="email" class="input" type="email" autocomplete="email" :placeholder="t('auth.register.emailPlaceholder', { at: '@' })" required />
      </div>
      <div class="field">
        <label for="register-password">{{ t('auth.register.password') }}</label>
        <input id="register-password" v-model="password" class="input" type="password" autocomplete="new-password" minlength="8" :placeholder="t('auth.register.passwordPlaceholder')" required />
        <small class="field__hint">{{ t('auth.register.passwordHint') }}</small>
      </div>
      <button class="button button--dark button--wide auth-submit" :disabled="loading">
        <span v-if="loading" class="spinner"></span>
        {{ loading ? t('auth.register.submitting') : t('auth.register.submit') }}
      </button>
    </form>
    <p class="auth-switch">{{ t('auth.register.hasAccount') }} <RouterLink to="/login">{{ t('auth.register.login') }}</RouterLink></p>
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
