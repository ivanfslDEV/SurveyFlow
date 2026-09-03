<script setup>
import { useI18n } from 'vue-i18n'

defineProps({ pagination: { type: Object, required: true } })
defineEmits(['change'])
const { t } = useI18n({ useScope: 'global' })
</script>

<template>
  <nav v-if="pagination.totalPages > 1" class="pagination" :aria-label="t('pagination.label')">
    <button class="button button--soft button--small" :disabled="pagination.page <= 1" @click="$emit('change', pagination.page - 1)">{{ t('pagination.previous') }}</button>
    <span>{{ t('pagination.pageOf', { page: pagination.page, total: pagination.totalPages }) }}</span>
    <button class="button button--soft button--small" :disabled="pagination.page >= pagination.totalPages" @click="$emit('change', pagination.page + 1)">{{ t('pagination.next') }}</button>
  </nav>
</template>

<style scoped>
.pagination { display: flex; justify-content: space-between; align-items: center; gap: 1rem; padding-top: 1.25rem; color: var(--ink-soft); font-size: .88rem; }
</style>
