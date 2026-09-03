<script setup>
import AppLogo from '@/components/AppLogo.vue'
import { auth } from '@/auth/session'
import { useI18n } from 'vue-i18n'

const { t } = useI18n({ useScope: 'global' })
</script>

<template>
  <div v-if="!auth.state.initialized" class="app-boot" :aria-label="t('app.initializing')">
    <AppLogo />
    <span class="app-boot__line"></span>
  </div>
  <RouterView v-else />
</template>

<style scoped>
.app-boot { min-height: 100vh; display: grid; place-content: center; justify-items: center; gap: 1.25rem; background: var(--canvas); }
.app-boot__line { position: relative; overflow: hidden; width: 80px; height: 3px; border-radius: 999px; background: #dce5e1; }
.app-boot__line::after { content: ''; position: absolute; inset: 0; width: 45%; border-radius: inherit; background: var(--teal); animation: boot 1s ease-in-out infinite alternate; }
@keyframes boot { from { transform: translateX(-10%); } to { transform: translateX(145%); } }
</style>
