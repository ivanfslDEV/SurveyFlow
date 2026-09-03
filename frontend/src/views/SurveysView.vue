<script setup>
import { computed, onMounted, ref } from 'vue'
import { apiRequest, getErrorMessage } from '@/api/client'
import { auth } from '@/auth/session'
import PaginationControls from '@/components/PaginationControls.vue'
import StatusBadge from '@/components/StatusBadge.vue'
import { useI18n } from 'vue-i18n'

const surveys = ref([])
const pagination = ref({ page: 1, limit: 9, total: 0, totalPages: 0 })
const loading = ref(true)
const error = ref('')
const deletingId = ref(null)
const { t } = useI18n({ useScope: 'global' })

const firstName = computed(() => auth.state.user?.email?.split('@')[0] || 'por aqui')
const publishedCount = computed(() => surveys.value.filter((survey) => survey.status === 'published').length)
const draftCount = computed(() => surveys.value.filter((survey) => survey.status === 'draft').length)

async function loadSurveys(page = 1) {
  loading.value = true
  error.value = ''
  try {
    const result = await apiRequest(`/surveys?page=${page}&limit=${pagination.value.limit}`)
    surveys.value = result.data
    pagination.value = result.pagination
  } catch (requestError) {
    error.value = getErrorMessage(requestError)
  } finally {
    loading.value = false
  }
}

async function removeSurvey(survey) {
  if (!window.confirm(t('dashboard.archiveConfirm', { title: survey.title }))) return

  deletingId.value = survey.id
  error.value = ''
  try {
    await apiRequest(`/surveys/${survey.id}`, { method: 'DELETE' })
    const targetPage = surveys.value.length === 1 && pagination.value.page > 1
      ? pagination.value.page - 1
      : pagination.value.page
    await loadSurveys(targetPage)
  } catch (requestError) {
    error.value = getErrorMessage(requestError)
  } finally {
    deletingId.value = null
  }
}

onMounted(() => loadSurveys())
</script>

<template>
  <div class="page">
    <header class="page-header dashboard-header">
      <div>
        <span class="eyebrow">{{ t('dashboard.eyebrow') }}</span>
        <h1>{{ t('dashboard.greeting', { name: firstName }) }}</h1>
        <p>{{ t('dashboard.subtitle') }}</p>
      </div>
      <RouterLink class="button" to="/surveys/new"><span aria-hidden="true">＋</span> {{ t('dashboard.newSurvey') }}</RouterLink>
    </header>

    <div class="metrics" :aria-label="t('dashboard.summaryLabel')">
      <article class="metric metric--total">
        <span class="metric__icon">▤</span>
        <div><small>{{ t('dashboard.total') }}</small><strong>{{ pagination.total }}</strong></div>
      </article>
      <article class="metric">
        <span class="metric__icon metric__icon--green">●</span>
        <div><small>{{ t('dashboard.publishedOnPage') }}</small><strong>{{ publishedCount }}</strong></div>
      </article>
      <article class="metric">
        <span class="metric__icon metric__icon--yellow">✎</span>
        <div><small>{{ t('dashboard.draftsOnPage') }}</small><strong>{{ draftCount }}</strong></div>
      </article>
    </div>

    <div v-if="error" class="alert" role="alert">{{ error }}</div>

    <section>
      <div class="section-heading">
        <div><h2>{{ t('dashboard.sectionTitle') }}</h2><p>{{ t('dashboard.sectionSubtitle') }}</p></div>
        <span v-if="!loading" class="survey-total">{{ pagination.total }} {{ t(pagination.total === 1 ? 'common.item' : 'common.items') }}</span>
      </div>

      <div v-if="loading" class="survey-grid" :aria-label="t('dashboard.loading')">
        <div v-for="item in 6" :key="item" class="card survey-card survey-card--loading">
          <div class="skeleton" style="width: 76px; height: 24px"></div>
          <div class="skeleton" style="width: 72%; height: 22px"></div>
          <div class="skeleton" style="width: 100%; height: 42px"></div>
        </div>
      </div>

      <div v-else-if="surveys.length" class="survey-grid">
        <article v-for="survey in surveys" :key="survey.id" class="card survey-card">
          <div class="survey-card__top">
            <StatusBadge :status="survey.status" />
            <button class="icon-button survey-card__delete" :disabled="deletingId === survey.id" :title="t('dashboard.archive')" :aria-label="t('dashboard.archiveLabel')" @click="removeSurvey(survey)">•••</button>
          </div>
          <div class="survey-card__body">
            <h3>{{ survey.title }}</h3>
            <p>{{ survey.description || t('dashboard.noDescription') }}</p>
          </div>
          <div class="survey-card__footer">
            <RouterLink :to="`/surveys/${survey.id}`">{{ t('dashboard.editSurvey') }} <span>→</span></RouterLink>
            <RouterLink v-if="survey.status === 'published'" class="responses-link" :to="`/surveys/${survey.id}/submissions`">{{ t('dashboard.responses') }}</RouterLink>
          </div>
        </article>
      </div>

      <div v-else class="card empty-state">
        <div class="empty-state__icon">＋</div>
        <h2>{{ t('dashboard.emptyTitle') }}</h2>
        <p>{{ t('dashboard.emptyText') }}</p>
        <RouterLink class="button" to="/surveys/new">{{ t('dashboard.emptyAction') }}</RouterLink>
      </div>

      <PaginationControls v-if="!loading" :pagination="pagination" @change="loadSurveys" />
    </section>
  </div>
</template>

<style scoped>
.dashboard-header { align-items: end; }
.metrics { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 2.5rem; }
.metric { min-height: 105px; display: flex; align-items: center; gap: 1rem; padding: 1.2rem; border: 1px solid var(--line); border-radius: 14px; background: white; }
.metric--total { color: white; border-color: var(--navy); background: var(--navy); }
.metric__icon { width: 40px; height: 40px; display: grid; place-items: center; flex: none; border-radius: 11px; background: rgba(255,255,255,.1); color: var(--mint); font-size: 1.1rem; }
.metric__icon--green { background: var(--mint-pale); color: var(--teal); }
.metric__icon--yellow { background: #fff5cf; color: #987824; }
.metric small { display: block; margin-bottom: .25rem; color: currentColor; opacity: .7; font-size: .72rem; font-weight: 650; }
.metric strong { font-size: 1.65rem; line-height: 1; }
.survey-total { padding: .3rem .55rem; border-radius: 8px; background: #e9ece9; color: var(--ink-soft); font-size: .74rem; font-weight: 750; }
.survey-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1rem; }
.survey-card { min-height: 224px; display: flex; flex-direction: column; padding: 1.15rem; transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease; }
.survey-card:hover:not(.survey-card--loading) { transform: translateY(-3px); border-color: #c8d5d1; box-shadow: var(--shadow); }
.survey-card--loading { gap: 1.4rem; }
.survey-card__top { display: flex; justify-content: space-between; align-items: center; }
.survey-card__delete { color: var(--ink-soft); font-weight: 850; letter-spacing: .1em; }
.survey-card__body { margin: 1.2rem 0; }
.survey-card h3 { margin-bottom: .55rem; font-size: 1.06rem; line-height: 1.3; }
.survey-card p { display: -webkit-box; overflow: hidden; margin: 0; color: var(--ink-soft); font-size: .8rem; line-height: 1.55; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }
.survey-card__footer { display: flex; justify-content: space-between; align-items: center; gap: .75rem; margin-top: auto; padding-top: .9rem; border-top: 1px solid #edf0ee; }
.survey-card__footer a { color: var(--ink); text-decoration: none; font-size: .78rem; font-weight: 750; }
.survey-card__footer a span { margin-left: .25rem; color: var(--teal); }
.survey-card__footer .responses-link { color: var(--teal); }
@media (max-width: 1000px) { .survey-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 620px) {
  .metrics { grid-template-columns: 1fr 1fr; }
  .metric--total { grid-column: 1 / -1; }
  .survey-grid { grid-template-columns: 1fr; }
}
</style>
