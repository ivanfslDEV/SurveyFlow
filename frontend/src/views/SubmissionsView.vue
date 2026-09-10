<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { apiRequest, getErrorMessage } from '@/api/client'
import PaginationControls from '@/components/PaginationControls.vue'
import StatusBadge from '@/components/StatusBadge.vue'
import { useI18n } from 'vue-i18n'

const route = useRoute()
const survey = ref(null)
const submissions = ref([])
const surveyQuestions = ref([])
const activeView = ref('summary')
const page = ref(1)
const pageSize = 15
const pagination = computed(() => ({ page: page.value, limit: pageSize, total: submissions.value.length, totalPages: Math.ceil(submissions.value.length / pageSize) }))
const loading = ref(true)
const error = ref('')
const { t, locale } = useI18n({ useScope: 'global' })

const questions = computed(() => {
  const columns = new Map(surveyQuestions.value.map((question) => [question.id, { id: question.id, title: question.title }]))
  for (const submission of submissions.value) {
    for (const answer of submission.answers) {
      if (!columns.has(answer.questionId)) {
        columns.set(answer.questionId, { id: answer.questionId, title: answer.questionTitle })
      }
    }
  }
  return [...columns.values()]
})

const questionSummaries = computed(() => {
  const groups = new Map(questions.value.map((question) => [question.id, { ...question, answers: [] }]))
  for (const submission of submissions.value) {
    for (const answer of submission.answers) {
      if (hasAnswer(answer)) groups.get(answer.questionId).answers.push({ ...answer, submissionId: submission.id })
    }
  }
  return [...groups.values()]
})

const rows = computed(() => submissions.value.slice((page.value - 1) * pageSize, page.value * pageSize).map((submission) => ({
  ...submission,
  answersByQuestion: new Map(submission.answers.map((answer) => [answer.questionId, answer])),
})))

function hasAnswer(answer) {
  return answer && answer.value != null && answer.value !== '' && (!Array.isArray(answer.value) || answer.value.length > 0)
}

function formatAnswer(answer) {
  if (!hasAnswer(answer)) return t('submissions.noAnswer')
  if (Array.isArray(answer.value)) return answer.value.map((item) => item.label ?? item).join(', ')
  if (typeof answer.value === 'object') return answer.value.label ?? JSON.stringify(answer.value)
  if (answer.questionType === 'rating') return t('submissionDetail.rating', { value: answer.value })
  return answer.value
}

function formatDate(value) {
  return new Intl.DateTimeFormat(locale.value === 'pt' ? 'pt-PT' : 'en-US', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value))
}

async function loadAll(path) {
  const items = new Map()
  let currentPage = 1
  let totalPages = 1
  do {
    const result = await apiRequest(`${path}?page=${currentPage}&limit=100`)
    for (const item of result.data) items.set(item.id, item)
    totalPages = result.pagination.totalPages
    currentPage += 1
  } while (currentPage <= totalPages)
  return [...items.values()]
}

async function load() {
  loading.value = true
  error.value = ''
  try {
    const [surveyResult, submissionResult, questionResult] = await Promise.all([
      apiRequest(`/surveys/${route.params.id}`),
      loadAll(`/surveys/${route.params.id}/submissions`),
      loadAll(`/surveys/${route.params.id}/questions`),
    ])
    survey.value = surveyResult
    submissions.value = submissionResult
    surveyQuestions.value = questionResult.sort((left, right) => left.position - right.position)
    page.value = 1
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

    <div v-if="error" class="alert" role="alert">{{ error }} <button class="button button--soft button--small" @click="load">{{ t('submissions.retry') }}</button></div>

    <section class="response-summary card">
      <div class="response-summary__heading">
        <h2>{{ loading ? t('submissions.loading') : t('submissions.answerCount', pagination.total) }}</h2>
        <RouterLink class="button button--soft button--small" :to="`/surveys/${route.params.id}`">{{ t('submissions.manage') }}</RouterLink>
      </div>
      <p v-if="survey">{{ t(survey.status === 'published' ? 'submissions.online' : 'submissions.offline') }}</p>
      <nav class="response-views" :aria-label="t('submissions.views')">
        <button v-for="view in ['summary', 'table', 'individual']" :key="view" type="button" :aria-pressed="activeView === view" :class="{ 'is-active': activeView === view }" @click="activeView = view">{{ t(`submissions.${view}`) }}</button>
      </nav>
    </section>

    <section class="responses-section">
      <div v-if="loading" class="response-list">
        <div v-for="item in 3" :key="item" class="card panel"><div class="skeleton" style="width: 38%; height: 20px; margin-bottom: 24px"></div><div class="skeleton" style="height: 100px"></div></div>
      </div>
      <template v-else-if="!error && submissions.length">
      <div v-if="activeView === 'summary'" class="question-summaries">
        <article v-for="question in questionSummaries" :key="question.id" class="card panel question-summary">
          <h2>{{ question.title }}</h2>
          <p class="question-summary__count">{{ t('submissions.answerCount', question.answers.length) }}</p>
          <ul v-if="question.answers.length" class="question-summary__answers">
            <li v-for="answer in question.answers" :key="answer.id">{{ formatAnswer(answer) }}</li>
          </ul>
          <p v-else class="question-summary__empty">{{ t('submissions.noAnswer') }}</p>
        </article>
      </div>
      <div v-else-if="activeView === 'table'" class="card response-table-scroll" role="region" :aria-label="t('submissions.tableLabel')" tabindex="0">
        <table class="response-table">
          <caption>{{ t('submissions.tableCaption') }}</caption>
          <thead>
            <tr>
              <th scope="col">{{ t('submissions.submission') }}</th>
              <th v-for="question in questions" :key="question.id" scope="col">{{ question.title }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="submission in rows" :key="submission.id">
              <th scope="row">
                <RouterLink :to="`/submissions/${submission.id}`">{{ t('submissions.responseNumber', { id: submission.id }) }}</RouterLink>
                <time :datetime="submission.createdAt">{{ formatDate(submission.createdAt) }}</time>
              </th>
              <td v-for="question in questions" :key="question.id">{{ formatAnswer(submission.answersByQuestion.get(question.id)) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else class="response-list">
        <RouterLink v-for="submission in rows" :key="submission.id" class="card response-row" :to="`/submissions/${submission.id}`">
          <div><strong>{{ t('submissions.responseNumber', { id: submission.id }) }}</strong><time :datetime="submission.createdAt">{{ formatDate(submission.createdAt) }}</time></div>
          <span>{{ t('submissions.answerCount', submission.answers.length) }} <span aria-hidden="true">→</span></span>
        </RouterLink>
      </div>
      <PaginationControls v-if="activeView !== 'summary'" :pagination="pagination" @change="page = $event" />
      </template>
      <div v-else-if="!error" class="card empty-state">
        <div class="empty-state__icon">⌁</div>
        <h2>{{ t('submissions.emptyTitle') }}</h2>
        <p v-if="survey?.status === 'published'">{{ t('submissions.emptyOnline') }}</p>
        <p v-else>{{ t('submissions.emptyOffline') }}</p>
        <RouterLink class="button" :to="`/surveys/${route.params.id}`">{{ t('submissions.manage') }}</RouterLink>
      </div>
    </section>
  </div>
</template>

<style scoped>
.responses-header { align-items: center; }
.response-summary { padding: 1.5rem 1.5rem 0; margin-bottom: 1rem; }
.response-summary__heading { display: flex; align-items: center; justify-content: space-between; gap: 1rem; }
.response-summary h2 { margin: 0; font-size: 1.6rem; font-weight: 500; }
.response-summary p { margin: .8rem 0 1.5rem; color: var(--ink-soft); font-size: .85rem; line-height: 1.5; }
.response-views { display: flex; gap: 1rem; margin-top: 1.5rem; }
.response-views button { flex: 1; padding: .85rem .25rem; border: 0; border-bottom: 3px solid transparent; background: transparent; color: var(--ink-soft); cursor: pointer; font-size: .85rem; }
.response-views button.is-active { border-bottom-color: var(--teal); color: var(--teal); font-weight: 750; }
.response-views button:focus-visible { outline: 2px solid var(--teal); outline-offset: -2px; }
.question-summaries { display: grid; gap: 1rem; }
.question-summary h2 { margin-bottom: .5rem; font-size: 1rem; font-weight: 600; overflow-wrap: anywhere; }
.question-summary__count { margin-bottom: 1.8rem; color: var(--ink-soft); font-size: .77rem; }
.question-summary__answers { display: grid; gap: .3rem; margin: 0; padding: 0; list-style: none; }
.question-summary__answers li, .question-summary__empty { margin: 0; padding: .7rem .85rem; border-radius: 5px; background: var(--surface-muted); white-space: pre-wrap; overflow-wrap: anywhere; font-size: .86rem; line-height: 1.5; }
.question-summary__empty { color: var(--ink-soft); }
.response-list { display: grid; gap: .6rem; }
.response-row { min-height: 72px; display: flex; justify-content: space-between; align-items: center; gap: .9rem; padding: 1rem; color: var(--ink); text-decoration: none; }
.response-row:hover { border-color: var(--teal); }
.response-row strong { font-size: .86rem; }
.response-row time { display: block; margin-top: .3rem; color: var(--ink-soft); font-size: .75rem; }
.response-row > span { color: var(--teal); font-size: .8rem; }
.response-table-scroll { max-width: 100%; overflow-x: auto; }
.response-table-scroll:focus-visible { outline: 2px solid var(--teal); outline-offset: 3px; }
.response-table { width: 100%; border-collapse: collapse; text-align: left; font-size: .85rem; }
.response-table caption { padding: 1rem; text-align: left; color: var(--ink-soft); font-size: .77rem; }
.response-table th, .response-table td { min-width: 220px; max-width: 360px; padding: 1rem; vertical-align: top; border-bottom: 1px solid var(--line); overflow-wrap: anywhere; white-space: pre-wrap; line-height: 1.55; }
.response-table thead th { background: var(--surface-muted); font-weight: 800; }
.response-table tbody th { min-width: 180px; background: var(--surface); }
.response-table tbody tr:last-child > * { border-bottom: 0; }
.response-table a { color: var(--teal); font-weight: 800; }
.response-table time { display: block; margin-top: .3rem; color: var(--ink-soft); font-size: .72rem; font-weight: 400; }
@media (max-width: 650px) {
  .response-summary { padding: 1rem 1rem 0; }
  .response-summary__heading { flex-wrap: wrap; }
  .response-views { gap: .25rem; }
}
</style>
