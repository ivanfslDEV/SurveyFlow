<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useRoute } from 'vue-router'
import AppLogo from '@/components/AppLogo.vue'
import LanguageSwitcher from '@/components/LanguageSwitcher.vue'
import { apiRequest, getErrorMessage } from '@/api/client'
import { useI18n } from 'vue-i18n'

const route = useRoute()
const survey = ref(null)
const answers = reactive({})
const loading = ref(true)
const submitting = ref(false)
const submitted = ref(null)
const error = ref('')
const fieldErrors = reactive({})
const { t } = useI18n({ useScope: 'global' })

function initializeAnswers() {
  for (const question of survey.value.questions) {
    answers[question.id] = question.type === 'multiple_choice' ? [] : null
  }
}

function isAnswered(question) {
  const value = answers[question.id]
  if (Array.isArray(value)) return value.length > 0
  if (typeof value === 'string') return value.trim().length > 0
  return value !== null && value !== undefined && value !== ''
}

function validate() {
  let valid = true
  for (const question of survey.value.questions) {
    fieldErrors[question.id] = ''
    if (question.required && !isAnswered(question)) {
      fieldErrors[question.id] = t('publicSurvey.requiredError')
      valid = false
    }
  }
  return valid
}

async function load() {
  loading.value = true
  error.value = ''
  try {
    survey.value = await apiRequest(`/public/surveys/${route.params.id}`, { authenticated: false })
    initializeAnswers()
  } catch (requestError) {
    error.value = requestError.status === 404
      ? t('publicSurvey.notPublished')
      : getErrorMessage(requestError)
  } finally {
    loading.value = false
  }
}

async function submit() {
  if (!validate()) {
    document.querySelector('.public-question--error')?.scrollIntoView({ behavior: 'smooth', block: 'center' })
    return
  }

  submitting.value = true
  error.value = ''
  try {
    const payload = survey.value.questions
      .filter(isAnswered)
      .map((question) => ({
        questionId: question.id,
        value: question.type === 'text' ? answers[question.id].trim() : answers[question.id],
      }))
    submitted.value = await apiRequest(`/public/surveys/${survey.value.id}/submissions`, {
      method: 'POST', body: { answers: payload }, authenticated: false,
    })
    window.scrollTo({ top: 0, behavior: 'smooth' })
  } catch (requestError) {
    error.value = getErrorMessage(requestError)
  } finally {
    submitting.value = false
  }
}

onMounted(load)
</script>

<template>
  <main class="public-page">
    <header class="public-header"><AppLogo /><div><span>{{ t('publicSurvey.secure') }}</span><LanguageSwitcher /></div></header>

    <div v-if="loading" class="public-wrap public-loading">
      <div class="skeleton" style="height: 150px"></div>
      <div v-for="item in 3" :key="item" class="skeleton" style="height: 180px"></div>
    </div>

    <div v-else-if="submitted" class="public-wrap success-wrap">
      <section class="success-card">
        <div class="success-mark">✓</div>
        <span class="eyebrow">{{ t('publicSurvey.sentEyebrow') }}</span>
        <h1>{{ t('publicSurvey.thanks') }}</h1>
        <p>{{ t('publicSurvey.successText') }}</p>
        <small>{{ t('publicSurvey.reference', { id: submitted.id }) }}</small>
      </section>
    </div>

    <div v-else-if="survey" class="public-wrap">
      <section class="public-intro">
        <span class="public-intro__label">{{ t('publicSurvey.label') }}</span>
        <h1>{{ survey.title }}</h1>
        <p>{{ survey.description || t('publicSurvey.defaultDescription') }}</p>
        <div><span>{{ survey.questions.length }} {{ t(survey.questions.length === 1 ? 'common.question' : 'common.questions') }}</span><span>•</span><span>{{ t('publicSurvey.fieldHint') }}</span></div>
      </section>

      <div v-if="error" class="alert public-alert" role="alert">{{ error }}</div>

      <form class="public-form" @submit.prevent="submit">
        <section v-for="(question, index) in survey.questions" :key="question.id" class="public-question" :class="{ 'public-question--error': fieldErrors[question.id] }">
          <div class="public-question__number">{{ String(index + 1).padStart(2, '0') }}</div>
          <div class="public-question__body">
            <label class="public-question__title" :for="`answer-${question.id}`">{{ question.title }} <span v-if="question.required">*</span></label>

            <textarea v-if="question.type === 'text'" :id="`answer-${question.id}`" v-model="answers[question.id]" class="textarea public-textarea" :placeholder="t('publicSurvey.textPlaceholder')" @input="fieldErrors[question.id] = ''"></textarea>

            <div v-else-if="question.type === 'rating'" class="rating-options">
              <label v-for="rating in 5" :key="rating" :class="{ selected: answers[question.id] === rating }">
                <input v-model="answers[question.id]" type="radio" :name="`answer-${question.id}`" :value="rating" @change="fieldErrors[question.id] = ''" />
                <strong>{{ rating }}</strong><small>{{ rating === 1 ? t('publicSurvey.poor') : rating === 5 ? t('publicSurvey.excellent') : '' }}</small>
              </label>
            </div>

            <div v-else-if="question.type === 'single_choice'" class="choice-options">
              <label v-for="option in question.options" :key="option.id" :class="{ selected: answers[question.id] === option.id }">
                <input v-model="answers[question.id]" type="radio" :name="`answer-${question.id}`" :value="option.id" @change="fieldErrors[question.id] = ''" />
                <span>{{ option.label }}</span><i></i>
              </label>
            </div>

            <div v-else class="choice-options">
              <label v-for="option in question.options" :key="option.id" :class="{ selected: answers[question.id].includes(option.id) }">
                <input v-model="answers[question.id]" type="checkbox" :value="option.id" @change="fieldErrors[question.id] = ''" />
                <span>{{ option.label }}</span><i>✓</i>
              </label>
            </div>

            <small v-if="fieldErrors[question.id]" class="field-error">{{ fieldErrors[question.id] }}</small>
          </div>
        </section>

        <footer class="public-submit">
          <p>{{ t('publicSurvey.privacy') }}</p>
          <button class="button button--dark" :disabled="submitting">
            <span v-if="submitting" class="spinner"></span>{{ submitting ? t('publicSurvey.submitting') : t('publicSurvey.submit') }}
          </button>
        </footer>
      </form>
    </div>

    <div v-else class="public-wrap unavailable-wrap">
      <div class="unavailable-mark">!</div><h1>{{ t('publicSurvey.unavailable') }}</h1><p>{{ error }}</p>
    </div>

    <footer class="public-footer">{{ t('publicSurvey.poweredBy') }} <strong>SurveyFlow</strong></footer>
  </main>
</template>

<style scoped>
.public-page { min-height: 100vh; background: var(--canvas); }
.public-header { height: 68px; display: flex; justify-content: space-between; align-items: center; padding: 0 clamp(1rem, 4vw, 3rem); border-bottom: 1px solid var(--line); background: rgba(255,255,255,.9); }
.public-header > div { display: flex; align-items: center; gap: .8rem; }
.public-header > div > span { color: var(--ink-soft); font-size: .7rem; font-weight: 700; }
.public-wrap { width: min(760px, calc(100% - 2rem)); margin: 0 auto; padding: 3.5rem 0; }
.public-loading { display: grid; gap: 1rem; }
.public-intro { position: relative; overflow: hidden; margin-bottom: 1rem; padding: clamp(1.5rem, 4vw, 2.5rem); border-radius: 19px; background: var(--navy); color: white; }
.public-intro::after { content: ''; position: absolute; width: 170px; height: 170px; right: -85px; top: -95px; border: 35px solid rgba(156,232,207,.09); border-radius: 50%; }
.public-intro__label { display: block; margin-bottom: 1rem; color: var(--mint); font-size: .69rem; font-weight: 850; letter-spacing: .13em; text-transform: uppercase; }
.public-intro h1 { max-width: 620px; margin-bottom: .8rem; font-size: clamp(1.8rem, 5vw, 3rem); line-height: 1.08; }
.public-intro p { max-width: 590px; color: #c0d0d4; line-height: 1.6; }
.public-intro > div { display: flex; gap: .55rem; color: #90a8ae; font-size: .7rem; }
.public-alert { margin-top: 1rem; }
.public-form { display: grid; gap: 1rem; }
.public-question { display: grid; grid-template-columns: 48px 1fr; gap: 1.1rem; padding: clamp(1.2rem, 4vw, 2rem); border: 1px solid var(--line); border-radius: 16px; background: white; transition: border-color .15s ease; }
.public-question--error { border-color: #e99e97; }
.public-question__number { width: 43px; height: 43px; display: grid; place-items: center; border-radius: 12px; background: var(--mint-pale); color: var(--teal); font-size: .72rem; font-weight: 850; }
.public-question__title { display: block; margin-bottom: 1.15rem; font-size: 1.03rem; font-weight: 750; line-height: 1.45; }
.public-question__title span { color: var(--coral); }
.public-textarea { min-height: 105px; }
.choice-options { display: grid; gap: .55rem; }
.choice-options label { min-height: 46px; display: grid; grid-template-columns: auto 1fr auto; align-items: center; gap: .75rem; padding: .65rem .8rem; border: 1px solid var(--line); border-radius: 10px; cursor: pointer; color: var(--ink-soft); font-size: .84rem; }
.choice-options label:hover, .choice-options label.selected { border-color: var(--teal); background: #f4fbf8; color: var(--ink); }
.choice-options input { width: 16px; height: 16px; accent-color: var(--teal); }
.choice-options i { width: 19px; height: 19px; display: grid; place-items: center; border: 1px solid #c9d3d0; border-radius: 5px; color: transparent; font-size: .7rem; font-style: normal; }
.choice-options label.selected i { border-color: var(--teal); background: var(--teal); color: white; }
.rating-options { display: grid; grid-template-columns: repeat(5, 1fr); gap: .55rem; }
.rating-options label { min-height: 68px; display: grid; place-items: center; align-content: center; gap: .2rem; border: 1px solid var(--line); border-radius: 11px; cursor: pointer; }
.rating-options label:hover, .rating-options label.selected { border-color: var(--teal); background: var(--mint-pale); color: var(--teal); }
.rating-options input { position: absolute; opacity: 0; pointer-events: none; }
.rating-options strong { font-size: 1.05rem; }.rating-options small { min-height: 12px; font-size: .6rem; }
.field-error { display: block; margin-top: .6rem; color: var(--danger); font-size: .72rem; font-weight: 650; }
.public-submit { display: flex; justify-content: space-between; align-items: center; gap: 2rem; padding: 1.2rem 0; }
.public-submit p { max-width: 410px; margin: 0; color: var(--ink-soft); font-size: .7rem; line-height: 1.5; }
.public-submit .spinner { width: 16px; height: 16px; border-color: rgba(255,255,255,.25); border-top-color: white; }
.success-wrap, .unavailable-wrap { min-height: calc(100vh - 130px); display: grid; place-items: center; }
.success-card, .unavailable-wrap { text-align: center; }
.success-card { width: 100%; padding: clamp(2rem, 7vw, 4rem); border: 1px solid var(--line); border-radius: 20px; background: white; box-shadow: var(--shadow); }
.success-mark, .unavailable-mark { width: 68px; height: 68px; display: grid; place-items: center; margin: 0 auto 1.5rem; border-radius: 22px; background: var(--mint-pale); color: var(--teal); font-size: 1.7rem; font-weight: 850; transform: rotate(-4deg); }
.success-card h1 { font-size: clamp(2rem, 5vw, 3.2rem); }.success-card p, .unavailable-wrap p { max-width: 470px; margin: 0 auto 1.3rem; color: var(--ink-soft); line-height: 1.6; }.success-card > small { color: var(--ink-soft); }
.unavailable-mark { background: #fff0ee; color: var(--coral); }
.public-footer { padding: 1.5rem; color: #8b9b9f; text-align: center; font-size: .68rem; }
.public-footer strong { color: var(--ink); }
@media (max-width: 590px) {
  .public-wrap { padding-top: 1.5rem; }
  .public-question { grid-template-columns: 1fr; }
  .public-question__number { width: 34px; height: 34px; }
  .rating-options { gap: .3rem; }
  .rating-options label { min-height: 58px; }
  .public-submit { align-items: stretch; flex-direction: column; }
  .public-submit .button { width: 100%; }
}
</style>
