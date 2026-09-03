<script setup>
import { useI18n } from 'vue-i18n'

const props = defineProps({ status: { type: String, required: true } })
const { t } = useI18n({ useScope: 'global' })
</script>

<template>
  <span class="status-badge" :class="`status-badge--${props.status}`">
    <span class="status-badge__dot"></span>{{ t(`status.${props.status}`, props.status) }}
  </span>
</template>

<style scoped>
.status-badge { display: inline-flex; align-items: center; gap: .45rem; padding: .35rem .65rem; border-radius: 999px; font-size: .75rem; font-weight: 750; line-height: 1; background: var(--surface-muted); color: var(--ink-soft); }
.status-badge__dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
.status-badge--draft { color: #886b22; background: #fff5cf; }
.status-badge--published { color: #137260; background: #dcf8ef; }
.status-badge--archived { color: #6f7180; background: #eeeef2; }
</style>
