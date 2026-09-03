<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute } from 'vue-router'
import { apiRequest, getErrorMessage } from '@/api/client'
import QuestionForm from '@/components/QuestionForm.vue'
import StatusBadge from '@/components/StatusBadge.vue'
import { useI18n } from 'vue-i18n'

const route = useRoute()
const survey = ref(null)
const questions = ref([])
const loading = ref(true)
const savingDetails = ref(false)
const savingQuestion = ref(false)
const changingStatus = ref(false)
const deletingQuestionId = ref(null)
const editorOpen = ref(false)
const editingQuestion = ref(null)
const error = ref('')
const { t } = useI18n({ useScope: 'global' })
const notice = ref(route.query.created ? t('surveyDetail.created') : '')
const form = reactive({ title: '', description: '' })

const isArchived = computed(() => survey.value?.status === 'archived')
const nextPosition = computed(() => questions.value.length
  ? Math.max(...questions.value.map((question) => question.position)) + 1
  : 1)
const publicUrl = computed(() => `${window.location.origin}/s/${survey.value?.id || ''}`)
const questionTypes = computed(() => ({
  text: t('surveyDetail.types.text'),
  single_choice: t('surveyDetail.types.single_choice'),
  multiple_choice: t('surveyDetail.types.multiple_choice'),
  rating: t('surveyDetail.types.rating'),
}))

async function load() {
  loading.value = true
  error.value = ''
  try {
    const [surveyResult, questionResult] = await Promise.all([
      apiRequest(`/surveys/${route.params.id}`),
      apiRequest(`/surveys/${route.params.id}/questions?page=1&limit=100`),
    ])
    survey.value = surveyResult
    questions.value = questionResult.data
    form.title = surveyResult.title
    form.description = surveyResult.description || ''
  } catch (requestError) {
    error.value = getErrorMessage(requestError)
  } finally {
    loading.value = false
  }
}

async function saveDetails() {
  savingDetails.value = true
  error.value = ''
  notice.value = ''
  try {
    survey.value = await apiRequest(`/surveys/${survey.value.id}`, {
      method: 'PATCH',
      body: { title: form.title, description: form.description || null },
    })
    notice.value = t('surveyDetail.detailsSaved')
  } catch (requestError) {
    error.value = getErrorMessage(requestError)
  } finally {
    savingDetails.value = false
  }
}

async function changeStatus(statusName) {
  changingStatus.value = true
  error.value = ''
  notice.value = ''
  try {
    survey.value = await apiRequest(`/surveys/${survey.value.id}/status`, {
      method: 'PATCH',
      body: { statusName },
    })
    notice.value = statusName === 'published'
      ? t('surveyDetail.publishedNotice')
      : statusName === 'archived' ? t('surveyDetail.archivedNotice') : t('surveyDetail.draftNotice')
    if (statusName === 'archived') closeEditor()
  } catch (requestError) {
    error.value = getErrorMessage(requestError)
  } finally {
    changingStatus.value = false
  }
}

function openNewQuestion() {
  editingQuestion.value = null
  editorOpen.value = true
  notice.value = ''
}

function openEditQuestion(question) {
  editingQuestion.value = question
  editorOpen.value = true
  notice.value = ''
}

function closeEditor() {
  editorOpen.value = false
  editingQuestion.value = null
}

async function saveQuestion(payload) {
  savingQuestion.value = true
  error.value = ''
  try {
    if (editingQuestion.value) {
      const updated = await apiRequest(`/questions/${editingQuestion.value.id}`, { method: 'PATCH', body: payload })
      questions.value = questions.value.map((question) => question.id === updated.id ? updated : question)
      notice.value = t('surveyDetail.questionUpdated')
    } else {
      const created = await apiRequest(`/surveys/${survey.value.id}/questions`, { method: 'POST', body: [payload] })
      questions.value.push(...created)
      notice.value = t('surveyDetail.questionAdded')
    }
    questions.value.sort((left, right) => left.position - right.position)
    closeEditor()
  } catch (requestError) {
    error.value = getErrorMessage(requestError)
  } finally {
    savingQuestion.value = false
  }
}

async function removeQuestion(question) {
  if (!window.confirm(t('surveyDetail.deleteQuestionConfirm', { title: question.title }))) return
  deletingQuestionId.value = question.id
  error.value = ''
  notice.value = ''
  try {
    await apiRequest(`/questions/${question.id}`, { method: 'DELETE' })
    questions.value = questions.value.filter((item) => item.id !== question.id)
    notice.value = t('surveyDetail.questionDeleted')
  } catch (requestError) {
    error.value = getErrorMessage(requestError)
  } finally {
    deletingQuestionId.value = null
  }
}

async function copyPublicLink() {
  try {
    await navigator.clipboard.writeText(publicUrl.value)
    notice.value = t('surveyDetail.linkCopied')
  } catch {
    error.value = t('surveyDetail.copyFailed')
  }
}

onMounted(load)
</script>

<template>
  <div class="page">
    <RouterLink class="back-link" to="/surveys">{{ t('surveyDetail.back') }}</RouterLink>

    <div v-if="loading" class="detail-loading">
      <div class="skeleton" style="height: 40px; width: 45%"></div>
      <div class="skeleton" style="height: 210px"></div>
      <div class="skeleton" style="height: 280px"></div>
    </div>

    <template v-else-if="survey">
      <header class="page-header detail-header">
        <div>
          <div class="detail-header__meta"><StatusBadge :status="survey.status" /><span>#{{ survey.id }}</span></div>
          <h1>{{ survey.title }}</h1>
          <p>{{ survey.description || t('surveyDetail.noDescription') }}</p>
        </div>
        <div class="page-actions">
          <RouterLink class="button button--soft" :to="`/surveys/${survey.id}/submissions`">{{ t('surveyDetail.viewResponses') }}</RouterLink>
          <button v-if="survey.status === 'published'" class="button" @click="copyPublicLink">{{ t('surveyDetail.copyLink') }}</button>
        </div>
      </header>

      <div v-if="error" class="alert" role="alert">{{ error }}</div>
      <div v-if="notice" class="alert alert--info" role="status">{{ notice }}</div>

      <section class="status-strip card">
        <div>
          <span class="status-strip__label">{{ t('surveyDetail.statusLabel') }}</span>
          <p v-if="survey.status === 'draft'">{{ t('surveyDetail.draftHelp') }}</p>
          <p v-else-if="survey.status === 'published'">{{ t('surveyDetail.publishedHelp') }}</p>
          <p v-else>{{ t('surveyDetail.archivedHelp') }}</p>
        </div>
        <div class="status-strip__actions">
          <a v-if="survey.status === 'published'" class="button button--soft button--small" :href="publicUrl" target="_blank" rel="noopener">{{ t('surveyDetail.openForm') }}</a>
          <button v-if="survey.status !== 'draft'" class="button button--soft button--small" :disabled="changingStatus" @click="changeStatus('draft')">{{ t('surveyDetail.moveToDraft') }}</button>
          <button v-if="survey.status !== 'published'" class="button button--small" :disabled="changingStatus" @click="changeStatus('published')">{{ t('surveyDetail.publish') }}</button>
          <button v-if="survey.status !== 'archived'" class="button button--danger button--small" :disabled="changingStatus" @click="changeStatus('archived')">{{ t('surveyDetail.archive') }}</button>
        </div>
      </section>

      <div class="detail-grid">
        <section class="card panel details-panel">
          <div class="section-heading"><div><h2>{{ t('surveyDetail.details') }}</h2><p>{{ t('surveyDetail.detailsHelp') }}</p></div></div>
          <form @submit.prevent="saveDetails">
            <div class="field">
              <label for="detail-title">{{ t('surveyDetail.titleLabel') }}</label>
              <input id="detail-title" v-model.trim="form.title" class="input" maxlength="255" :disabled="isArchived" required />
            </div>
            <div class="field">
              <label for="detail-description">{{ t('surveyDetail.descriptionLabel') }}</label>
              <textarea id="detail-description" v-model.trim="form.description" class="textarea" :disabled="isArchived"></textarea>
            </div>
            <div class="form-actions">
              <button class="button button--dark" :disabled="savingDetails || isArchived || !form.title">{{ savingDetails ? t('surveyDetail.saving') : t('surveyDetail.saveDetails') }}</button>
            </div>
          </form>
        </section>

        <aside class="card panel share-panel">
          <span class="eyebrow">{{ t('surveyDetail.share') }}</span>
          <h2>{{ t('surveyDetail.collect') }}</h2>
          <p>{{ t('surveyDetail.shareHelp') }}</p>
          <div class="share-link" :class="{ 'share-link--disabled': survey.status !== 'published' }"><span>{{ publicUrl }}</span><button :disabled="survey.status !== 'published'" :aria-label="t('surveyDetail.copyLink')" @click="copyPublicLink">⧉</button></div>
          <small v-if="survey.status !== 'published'">{{ t('surveyDetail.linkUnavailable') }}</small>
        </aside>
      </div>

      <section class="questions-section">
        <div class="section-heading">
          <div><h2>{{ t('common.questions') }} <span class="count">{{ questions.length }}</span></h2><p>{{ t('surveyDetail.questionsHelp') }}</p></div>
          <button class="button button--small" :disabled="isArchived || editorOpen" @click="openNewQuestion">＋ {{ t('surveyDetail.addQuestion') }}</button>
        </div>

        <div v-if="isArchived" class="alert alert--info">{{ t('surveyDetail.archivedWarning') }}</div>

        <div v-if="editorOpen" class="card panel question-editor">
          <div class="section-heading"><div><h2>{{ editingQuestion ? t('surveyDetail.editQuestion') : t('surveyDetail.newQuestion') }}</h2><p>{{ t('surveyDetail.editorHelp') }}</p></div></div>
          <QuestionForm :key="editingQuestion?.id || `new-${nextPosition}`" :question="editingQuestion" :position="nextPosition" :loading="savingQuestion" @save="saveQuestion" @cancel="closeEditor" />
        </div>

        <div v-if="questions.length" class="questions-list">
          <article v-for="question in questions" :key="question.id" class="card question-card">
            <span class="question-card__position">{{ String(question.position).padStart(2, '0') }}</span>
            <div class="question-card__content">
              <div class="question-card__meta"><span>{{ questionTypes[question.type] }}</span><span v-if="question.required">{{ t('surveyDetail.required') }}</span></div>
              <h3>{{ question.title }}</h3>
              <div v-if="question.options.length" class="option-chips"><span v-for="option in question.options" :key="option.id">{{ option.label }}</span></div>
            </div>
            <div class="question-card__actions">
              <button class="button button--soft button--small" :disabled="isArchived" @click="openEditQuestion(question)">{{ t('surveyDetail.edit') }}</button>
              <button class="icon-button delete-question" :disabled="isArchived || deletingQuestionId === question.id" :title="t('surveyDetail.deleteQuestion')" :aria-label="t('surveyDetail.deleteQuestion')" @click="removeQuestion(question)">×</button>
            </div>
          </article>
        </div>
        <div v-else-if="!editorOpen" class="card empty-state">
          <div class="empty-state__icon">?</div>
          <h2>{{ t('surveyDetail.emptyQuestions') }}</h2>
          <p>{{ t('surveyDetail.emptyQuestionsText') }}</p>
          <button class="button" :disabled="isArchived" @click="openNewQuestion">{{ t('surveyDetail.firstQuestion') }}</button>
        </div>
      </section>
    </template>

    <div v-else class="card empty-state"><h2>{{ t('surveyDetail.unavailable') }}</h2><p>{{ error || t('surveyDetail.unavailableText') }}</p><RouterLink class="button" to="/surveys">{{ t('common.back') }}</RouterLink></div>
  </div>
</template>

<style scoped>
.detail-loading { display: grid; gap: 1rem; }
.detail-header { align-items: end; }
.detail-header__meta { display: flex; align-items: center; gap: .7rem; margin-bottom: .7rem; }
.detail-header__meta > span { color: var(--ink-soft); font-size: .72rem; font-weight: 750; }
.status-strip { display: flex; justify-content: space-between; align-items: center; gap: 1.5rem; margin-bottom: 1rem; padding: 1rem 1.2rem; border-left: 4px solid var(--teal); }
.status-strip__label { display: block; margin-bottom: .25rem; font-size: .77rem; font-weight: 800; }
.status-strip p { margin: 0; color: var(--ink-soft); font-size: .78rem; line-height: 1.45; }
.status-strip__actions { display: flex; justify-content: flex-end; flex-wrap: wrap; gap: .5rem; }
.detail-grid { display: grid; grid-template-columns: minmax(0, 1.65fr) minmax(260px, .8fr); gap: 1rem; }
.details-panel .textarea { min-height: 90px; }
.share-panel { align-self: start; background: var(--navy); color: white; border-color: var(--navy); }
.share-panel .eyebrow { color: var(--mint); }
.share-panel h2 { margin-bottom: .6rem; font-size: 1.25rem; }
.share-panel p { color: #b7c9ce; font-size: .82rem; line-height: 1.55; }
.share-panel > small { display: block; margin-top: .6rem; color: #829ba2; font-size: .7rem; }
.share-link { display: flex; align-items: center; gap: .5rem; margin-top: 1.2rem; padding: .45rem .45rem .45rem .7rem; border: 1px solid rgba(255,255,255,.14); border-radius: 9px; background: rgba(255,255,255,.07); }
.share-link span { overflow: hidden; flex: 1; color: #d4e0e3; font-size: .72rem; text-overflow: ellipsis; white-space: nowrap; }
.share-link button { width: 31px; height: 31px; flex: none; border: 0; border-radius: 7px; background: var(--mint); color: var(--navy); cursor: pointer; }
.share-link--disabled { opacity: .45; }
.questions-section { margin-top: 2.3rem; }
.count { display: inline-grid; place-items: center; min-width: 24px; height: 24px; margin-left: .4rem; border-radius: 8px; background: #e2e7e4; color: var(--ink-soft); font-size: .72rem; vertical-align: 2px; }
.question-editor { margin-bottom: 1rem; border-color: #b9d8cf; box-shadow: var(--shadow); }
.questions-list { display: grid; gap: .7rem; }
.question-card { display: grid; grid-template-columns: 48px minmax(0, 1fr) auto; align-items: center; gap: 1rem; padding: 1rem; }
.question-card__position { width: 42px; height: 42px; display: grid; place-items: center; border-radius: 12px; background: var(--mint-pale); color: var(--teal); font-size: .78rem; font-weight: 850; }
.question-card__meta { display: flex; gap: .45rem; margin-bottom: .4rem; }
.question-card__meta span { padding: .25rem .45rem; border-radius: 6px; background: #eef1ef; color: var(--ink-soft); font-size: .66rem; font-weight: 750; }
.question-card__meta span:last-child:not(:first-child) { background: #fff3d0; color: #81651b; }
.question-card h3 { margin: 0; font-size: .95rem; line-height: 1.35; }
.question-card__actions { display: flex; align-items: center; gap: .35rem; }
.delete-question { color: var(--danger); font-size: 1.2rem; }
.option-chips { display: flex; flex-wrap: wrap; gap: .35rem; margin-top: .6rem; }
.option-chips span { padding: .25rem .45rem; border: 1px solid var(--line); border-radius: 7px; color: var(--ink-soft); font-size: .67rem; }
@media (max-width: 980px) { .detail-grid { grid-template-columns: 1fr; } }
@media (max-width: 720px) {
  .status-strip { align-items: stretch; flex-direction: column; }
  .status-strip__actions { justify-content: flex-start; }
  .question-card { grid-template-columns: 42px 1fr; align-items: start; }
  .question-card__actions { grid-column: 2; }
}
</style>
