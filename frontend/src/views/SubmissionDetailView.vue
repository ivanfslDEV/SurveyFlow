<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { apiRequest, getErrorMessage } from '@/api/client'
import { useI18n } from 'vue-i18n'

const route = useRoute()
const submission = ref(null)
const survey = ref(null)
const loading = ref(true)
const error = ref('')
const { t, locale } = useI18n({ useScope: 'global' })
const questionTypes = computed(() => ({
  text: t('surveyDetail.types.text'),
  single_choice: t('surveyDetail.types.single_choice'),
  multiple_choice: t('surveyDetail.types.multiple_choice'),
  rating: t('surveyDetail.types.rating'),
}))

function formatDate(value) {
  return new Intl.DateTimeFormat(locale.value === 'pt' ? 'pt-PT' : 'en-US', { dateStyle: 'long', timeStyle: 'short' }).format(new Date(value))
}

function formatAnswer(answer) {
  if (Array.isArray(answer.value)) return answer.value.map((item) => item.label ?? item).join(', ')
  if (answer.value && typeof answer.value === 'object') return answer.value.label ?? JSON.stringify(answer.value)
  if (answer.questionType === 'rating') return t('submissionDetail.rating', { value: answer.value })
  return answer.value
}

async function load() {
  loading.value = true
  try {
    submission.value = await apiRequest(`/submissions/${route.params.id}`)
    survey.value = await apiRequest(`/surveys/${submission.value.surveyId}`)
  } catch (requestError) {
    error.value = getErrorMessage(requestError)
  } finally {
    loading.value = false
  }
}

onMounted(load)
</script>

<template>
  <div class="page page--narrow">
    <RouterLink class="back-link" :to="submission ? `/surveys/${submission.surveyId}/submissions` : '/surveys'">{{ t('submissionDetail.back') }}</RouterLink>
    <div v-if="loading" class="answer-list"><div v-for="item in 4" :key="item" class="card panel"><div class="skeleton" style="height: 20px; width: 60%; margin-bottom: 16px"></div><div class="skeleton" style="height: 36px"></div></div></div>
    <template v-else-if="submission">
      <header class="page-header submission-header">
        <div><span class="eyebrow">{{ survey?.title || t('submissionDetail.fallbackSurvey') }}</span><h1>{{ t('submissionDetail.title', { id: submission.id }) }}</h1><p>{{ t('submissionDetail.received', { date: formatDate(submission.createdAt) }) }}</p></div>
        <span class="answer-count">{{ submission.answers.length }} {{ t(submission.answers.length === 1 ? 'common.item' : 'common.items') }}</span>
      </header>
      <div class="answer-list">
        <article v-for="(answer, index) in submission.answers" :key="answer.id" class="card panel answer-card">
          <span class="answer-card__number">{{ String(index + 1).padStart(2, '0') }}</span>
          <div><small>{{ questionTypes[answer.questionType] }}</small><h2>{{ answer.questionTitle }}</h2><p>{{ formatAnswer(answer) }}</p></div>
        </article>
      </div>
    </template>
    <div v-else class="card empty-state"><h2>{{ t('submissionDetail.unavailable') }}</h2><p>{{ error }}</p><RouterLink class="button" to="/surveys">{{ t('submissionDetail.backToSurveys') }}</RouterLink></div>
  </div>
</template>

<style scoped>
.submission-header { align-items: center; }
.answer-count { padding: .5rem .7rem; border-radius: 9px; background: var(--mint-pale); color: var(--teal); font-size: .75rem; font-weight: 800; }
.answer-list { display: grid; gap: .75rem; }
.answer-card { display: grid; grid-template-columns: 48px 1fr; gap: 1rem; }
.answer-card__number { width: 40px; height: 40px; display: grid; place-items: center; border-radius: 11px; background: var(--navy); color: var(--mint); font-size: .72rem; font-weight: 850; }
.answer-card small { display: block; margin-bottom: .35rem; color: var(--teal); font-size: .65rem; font-weight: 800; text-transform: uppercase; }
.answer-card h2 { margin-bottom: .8rem; font-size: 1rem; line-height: 1.4; }
.answer-card p { margin: 0; padding: .75rem; border-radius: 9px; background: var(--surface-muted); color: var(--ink); font-size: .88rem; line-height: 1.55; white-space: pre-wrap; }
</style>
