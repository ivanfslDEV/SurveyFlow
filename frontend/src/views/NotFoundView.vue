<script setup>
import { useI18n } from 'vue-i18n'
const { t } = useI18n({ useScope: 'global' })
</script>

<template>
  <main class="not-found">
    <span>404</span>
    <h1>{{ t('notFound.title') }}</h1>
    <p>{{ t('notFound.text') }}</p>
    <RouterLink class="button" to="/surveys">{{ t('notFound.action') }}</RouterLink>
  </main>
</template>

<style scoped>
.not-found { min-height: 100vh; display: grid; place-content: center; justify-items: center; padding: 2rem; background: var(--canvas); text-align: center; }
.not-found span { color: var(--coral); font-size: .8rem; font-weight: 900; letter-spacing: .15em; }
.not-found h1 { margin: .7rem 0; font-size: clamp(2rem, 5vw, 3.5rem); }
.not-found p { margin-bottom: 1.5rem; color: var(--ink-soft); }
</style>
