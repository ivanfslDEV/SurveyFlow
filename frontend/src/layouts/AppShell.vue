<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import AppLogo from '@/components/AppLogo.vue'
import LanguageSwitcher from '@/components/LanguageSwitcher.vue'
import { auth } from '@/auth/session'
import { useI18n } from 'vue-i18n'

const router = useRouter()
const menuOpen = ref(false)
const { t } = useI18n({ useScope: 'global' })

function logout() {
  auth.logout()
  router.push({ name: 'login' })
}
</script>

<template>
  <div class="app-shell">
    <aside class="sidebar" :class="{ 'sidebar--open': menuOpen }">
      <div class="sidebar__top">
        <AppLogo light />
        <div class="sidebar__top-actions">
          <LanguageSwitcher light />
          <button class="icon-button sidebar__close" :aria-label="t('shell.closeMenu')" @click="menuOpen = false">×</button>
        </div>
      </div>

      <nav class="sidebar__nav" :aria-label="t('shell.navigation')">
        <RouterLink to="/surveys" @click="menuOpen = false">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 4h12a2 2 0 0 1 2 2v14H4V6a2 2 0 0 1 2-2Zm2 5h8M8 13h8M8 17h5"/></svg>
          {{ t('shell.surveys') }}
        </RouterLink>
        <RouterLink to="/surveys/new" @click="menuOpen = false">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
          {{ t('shell.newSurvey') }}
        </RouterLink>
      </nav>

      <div class="sidebar__tip">
        <span>{{ t('shell.tip') }}</span>
        <p>{{ t('shell.tipText') }}</p>
      </div>

      <div class="sidebar__user">
        <div class="avatar">{{ auth.state.user?.email?.charAt(0)?.toUpperCase() || 'U' }}</div>
        <div><strong>{{ auth.state.user?.email }}</strong><small>{{ t('common.member') }}</small></div>
        <button class="icon-button" :title="t('shell.signOut')" :aria-label="t('shell.signOut')" @click="logout">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 17l5-5-5-5M15 12H3M14 4h5a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-5"/></svg>
        </button>
      </div>
    </aside>
    <button v-if="menuOpen" class="sidebar-backdrop" :aria-label="t('shell.closeMenu')" @click="menuOpen = false"></button>

    <main class="app-main">
      <header class="mobile-header">
        <button class="icon-button" :aria-label="t('shell.openMenu')" @click="menuOpen = true">☰</button>
        <AppLogo />
        <LanguageSwitcher />
      </header>
      <RouterView />
    </main>
  </div>
</template>

<style scoped>
.app-shell { min-height: 100vh; background: var(--canvas); }
.sidebar { position: fixed; inset: 0 auto 0 0; z-index: 20; width: 252px; padding: 1.6rem 1rem; display: flex; flex-direction: column; background: var(--navy); color: white; }
.sidebar__top { display: flex; justify-content: space-between; align-items: center; padding: 0 .55rem 2rem; }
.sidebar__top-actions { display: flex; align-items: center; gap: .3rem; }
.sidebar__close { display: none; color: white; }
.sidebar__nav { display: grid; gap: .4rem; }
.sidebar__nav a { display: flex; align-items: center; gap: .8rem; padding: .8rem .9rem; border-radius: 10px; color: #bfcbd0; text-decoration: none; font-weight: 650; font-size: .9rem; }
.sidebar__nav a:hover, .sidebar__nav a.router-link-active { color: white; background: rgba(255,255,255,.1); }
.sidebar__nav svg, .sidebar__user svg { width: 20px; fill: none; stroke: currentColor; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }
.sidebar__tip { margin-top: auto; padding: 1rem; border: 1px solid rgba(255,255,255,.1); border-radius: 12px; background: rgba(255,255,255,.05); }
.sidebar__tip span { color: var(--mint); font-size: .78rem; font-weight: 750; }
.sidebar__tip p { margin: .45rem 0 0; color: #bfcbd0; font-size: .78rem; line-height: 1.5; }
.sidebar__user { display: grid; grid-template-columns: auto 1fr auto; align-items: center; gap: .7rem; margin-top: 1rem; padding: .8rem .55rem 0; border-top: 1px solid rgba(255,255,255,.1); }
.sidebar__user strong { display: block; max-width: 125px; overflow: hidden; text-overflow: ellipsis; font-size: .78rem; }
.sidebar__user small { color: #92a5ad; font-size: .7rem; }
.avatar { width: 34px; height: 34px; display: grid; place-items: center; flex: none; border-radius: 10px; background: var(--mint); color: var(--navy); font-size: .78rem; font-weight: 850; }
.avatar--small { width: 30px; height: 30px; }
.app-main { min-height: 100vh; margin-left: 252px; }
.mobile-header { display: none; }
.sidebar-backdrop { display: none; }
@media (max-width: 760px) {
  .sidebar { transform: translateX(-100%); transition: transform .2s ease; }
  .sidebar--open { transform: none; }
  .sidebar__close { display: grid; }
  .sidebar-backdrop { display: block; position: fixed; inset: 0; z-index: 15; border: 0; background: rgba(13,35,43,.45); }
  .app-main { margin-left: 0; }
  .mobile-header { height: 64px; padding: 0 1rem; display: flex; align-items: center; justify-content: space-between; background: white; border-bottom: 1px solid var(--line); }
}
</style>
