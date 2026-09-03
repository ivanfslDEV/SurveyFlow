<script setup>
import { onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { apiRequest, getErrorMessage } from '@/api/client'
import PaginationControls from '@/components/PaginationControls.vue'
import StatusBadge from '@/components/StatusBadge.vue'
import { useI18n } from 'vue-i18n'

const route = useRoute()
const survey = ref(null)
const submissions = ref([])
const pagination = ref({ page: 1, limit: 15, total: 0, totalPages: 0 })
const loading = ref(true)
const error = ref('')
const { t, locale } = useI18n({ useScope: 'global' })

function formatDate(value) {
  return new Intl.DateTimeFormat(locale.value === 'pt' ? 'pt-PT' : 'en-US', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value))
}

async function load(page = 1) {
  loading.value = true
  error.value = ''
  try {
    const [surveyResult, submissionResult] = await Promise.all([
      survey.value ? Promise.resolve(survey.value) : apiRequest(`/surveys/${route.params.id}`),
      apiRequest(`/surveys/${route.params.id}/submissions?page=${page}&limit=${pagination.value.limit}`),
    ])
    survey.value = surveyResult
    submissions.value = submissionResult.data
    pagination.value = submissionResult.pagination
  } catch (requestError) {
    error.value = getErrorMessage(requestError)
  } finally {
    loading.value = false
  }
}

onMounted(() => load())
</script>

<template>
  <div class="page">
    <RouterLink class="back-link" :to="`/surveys/${route.params.id}`">{{ t('submissions.back') }}</RouterLink>
    <header class="page-header responses-header">
      <div>
        <span class="eyebrow">{{ t('submissions.eyebrow') }}</span>
        <h1>{{ survey?.title || t('submissions.fallbackTitle') }}</h1>
        <p>{{ t('submissions.subtitle') }}</p>
      </div>
      <StatusBadge v-if="survey" :status="survey.status" />
    </header>

    <div v-if="error" class="alert" role="alert">{{ error }}</div>

    <section class="response-summary card">
      <div><small>{{ t('submissions.total') }}</small><strong>{{ pagination.total }}</strong></div>
      <div class="response-summary__visual" aria-hidden="true"><span></span><span></span><span></span><span></span><span></span></div>
      <p v-if="survey?.status === 'published'">{{ t('submissions.online') }}</p>
      <p v-else>{{ t('submissions.offline') }}</p>
    </section>

    <section class="responses-section">
      <div class="section-heading"><div><h2>{{ t('submissions.sectionTitle') }}</h2><p>{{ t('submissions.sectionText') }}</p></div></div>
      <div v-if="loading" class="response-list">
        <div v-for="item in 5" :key="item" class="card response-row"><div class="skeleton" style="width: 42px; height: 42px"></div><div class="skeleton" style="width: 38%; height: 18px"></div></div>
      </div>
      <div v-else-if="submissions.length" class="response-list">
        <RouterLink v-for="submission in submissions" :key="submission.id" class="card response-row" :to="`/submissions/${submission.id}`">
          <span class="response-row__id">#{{ submission.id }}</span>
          <div><strong>{{ t('submissions.responseNumber', { id: submission.id }) }}</strong><small>{{ formatDate(submission.createdAt) }}</small></div>
          <span class="response-row__answers">{{ submission.answers.length }} {{ t(submission.answers.length === 1 ? 'common.response' : 'common.responses') }}</span>
          <span class="response-row__arrow">→</span>
        </RouterLink>
      </div>
      <div v-else class="card empty-state">
        <div class="empty-state__icon">⌁</div>
        <h2>{{ t('submissions.emptyTitle') }}</h2>
        <p v-if="survey?.status === 'published'">{{ t('submissions.emptyOnline') }}</p>
        <p v-else>{{ t('submissions.emptyOffline') }}</p>
        <RouterLink class="button" :to="`/surveys/${route.params.id}`">{{ t('submissions.manage') }}</RouterLink>
      </div>
      <PaginationControls v-if="!loading" :pagination="pagination" @change="load" />
    </section>
  </div>
</template>

<style scoped>
.responses-header { align-items: center; }
.response-summary { display: grid; grid-template-columns: auto 1fr auto; align-items: center; gap: 2rem; padding: 1.35rem 1.5rem; margin-bottom: 2.2rem; background: var(--navy); color: white; }
.response-summary small { display: block; margin-bottom: .25rem; color: #adc1c6; font-size: .72rem; }
.response-summary strong { font-size: 2rem; }
.response-summary p { max-width: 260px; margin: 0; color: #b9cbd0; font-size: .77rem; line-height: 1.5; text-align: right; }
.response-summary__visual { height: 42px; display: flex; align-items: end; gap: 6px; }
.response-summary__visual span { width: 7px; border-radius: 4px 4px 1px 1px; background: var(--mint); opacity: .35; }
.response-summary__visual span:nth-child(1) { height: 25%; }.response-summary__visual span:nth-child(2) { height: 55%; }.response-summary__visual span:nth-child(3) { height: 40%; }.response-summary__visual span:nth-child(4) { height: 85%; opacity: 1; }.response-summary__visual span:nth-child(5) { height: 65%; }
.response-list { display: grid; gap: .6rem; }
.response-row { min-height: 72px; display: grid; grid-template-columns: 48px minmax(0, 1fr) auto 30px; align-items: center; gap: .9rem; padding: .85rem 1rem; color: var(--ink); text-decoration: none; transition: border-color .15s ease, transform .15s ease; }
.response-row:hover { border-color: #b8cbc5; transform: translateX(3px); }
.response-row__id { width: 43px; height: 43px; display: grid; place-items: center; border-radius: 12px; background: var(--mint-pale); color: var(--teal); font-size: .72rem; font-weight: 850; }
.response-row strong { display: block; margin-bottom: .25rem; font-size: .86rem; }
.response-row small, .response-row__answers { color: var(--ink-soft); font-size: .72rem; }
.response-row__answers { padding: .35rem .55rem; border-radius: 7px; background: var(--surface-muted); }
.response-row__arrow { color: var(--teal); font-weight: 800; }
@media (max-width: 650px) {
  .response-summary { grid-template-columns: auto 1fr; gap: 1rem; }
  .response-summary p { grid-column: 1 / -1; text-align: left; }
  .response-row { grid-template-columns: 45px 1fr 24px; }
  .response-row__answers { display: none; }
}
</style>
