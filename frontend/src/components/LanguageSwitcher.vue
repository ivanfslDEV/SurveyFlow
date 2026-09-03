<script setup>
import { useI18n } from 'vue-i18n'
import { setLocale } from '@/i18n'

defineProps({ light: Boolean })
const { locale, t } = useI18n({ useScope: 'global' })
</script>

<template>
  <div class="language-switcher" :class="{ 'language-switcher--light': light }" role="group" :aria-label="t('language.label')">
    <button type="button" :class="{ active: locale === 'pt' }" :aria-pressed="locale === 'pt'" :title="t('language.portuguese')" @click="setLocale('pt')">PT</button>
    <button type="button" :class="{ active: locale === 'en' }" :aria-pressed="locale === 'en'" :title="t('language.english')" @click="setLocale('en')">EN</button>
  </div>
</template>

<style scoped>
.language-switcher { display: inline-flex; padding: 3px; border: 1px solid var(--line); border-radius: 9px; background: var(--surface-muted); }
.language-switcher button { min-width: 34px; height: 28px; padding: 0 .45rem; border: 0; border-radius: 6px; background: transparent; color: var(--ink-soft); cursor: pointer; font-size: .67rem; font-weight: 850; }
.language-switcher button.active { background: white; color: var(--teal); box-shadow: 0 1px 4px rgba(16,47,56,.12); }
.language-switcher--light { border-color: rgba(255,255,255,.12); background: rgba(255,255,255,.06); }
.language-switcher--light button { color: #9eb2b7; }
.language-switcher--light button.active { background: rgba(255,255,255,.14); color: var(--mint); box-shadow: none; }
</style>
