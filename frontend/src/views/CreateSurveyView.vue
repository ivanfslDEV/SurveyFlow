<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { apiRequest, getErrorMessage } from '@/api/client'
import { useI18n } from 'vue-i18n'

const router = useRouter()
const form = reactive({ title: '', description: '' })
const loading = ref(false)
const error = ref('')
const { t } = useI18n({ useScope: 'global' })

async function submit() {
  loading.value = true
  error.value = ''
  try {
    const survey = await apiRequest('/surveys', {
      method: 'POST',
      body: { title: form.title, description: form.description || null, statusName: 'draft' },
    })
    await router.push({ name: 'survey-detail', params: { id: survey.id }, query: { created: '1' } })
  } catch (requestError) {
    error.value = getErrorMessage(requestError)
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="page page--narrow">
    <RouterLink class="back-link" to="/surveys">{{ t('createSurvey.back') }}</RouterLink>
    <header class="page-header">
      <div>
        <span class="eyebrow">{{ t('createSurvey.eyebrow') }}</span>
        <h1>{{ t('createSurvey.title') }}</h1>
        <p>{{ t('createSurvey.subtitle') }}</p>
      </div>
    </header>

    <section class="card panel create-card">
      <div class="create-card__number">01</div>
      <div class="section-heading">
        <div><h2>{{ t('createSurvey.sectionTitle') }}</h2><p>{{ t('createSurvey.sectionText') }}</p></div>
      </div>
      <div v-if="error" class="alert" role="alert">{{ error }}</div>
      <form @submit.prevent="submit">
        <div class="field">
          <label for="survey-title">{{ t('createSurvey.titleLabel') }}</label>
          <input id="survey-title" v-model.trim="form.title" class="input" maxlength="255" :placeholder="t('createSurvey.titlePlaceholder')" autofocus required />
          <small class="field__hint">{{ t('createSurvey.characterCount', { count: form.title.length }) }}</small>
        </div>
        <div class="field">
          <label for="survey-description">{{ t('createSurvey.descriptionLabel') }} <span class="muted">({{ t('common.optional') }})</span></label>
          <textarea id="survey-description" v-model.trim="form.description" class="textarea" :placeholder="t('createSurvey.descriptionPlaceholder')"></textarea>
        </div>
        <div class="draft-note"><span>✦</span><p>{{ t('createSurvey.draftNote') }}</p></div>
        <div class="form-actions">
          <RouterLink class="button button--soft" to="/surveys">{{ t('common.cancel') }}</RouterLink>
          <button class="button button--dark" :disabled="loading || !form.title">
            <span v-if="loading" class="spinner"></span>{{ loading ? t('createSurvey.submitting') : t('createSurvey.submit') }}
          </button>
        </div>
      </form>
    </section>
  </div>
</template>

<style scoped>
.create-card { position: relative; overflow: hidden; }
.create-card__number { position: absolute; right: 1.2rem; top: .4rem; color: #eef1ee; font-size: 5rem; font-weight: 900; line-height: 1; pointer-events: none; }
.section-heading, form { position: relative; }
.draft-note { display: flex; align-items: flex-start; gap: .7rem; margin-top: 1.25rem; padding: .85rem; border-radius: 10px; background: var(--mint-pale); color: #245c54; }
.draft-note span { color: var(--teal); }
.draft-note p { margin: 0; font-size: .8rem; line-height: 1.5; }
.form-actions .spinner { width: 16px; height: 16px; border-color: rgba(255,255,255,.25); border-top-color: white; }
</style>
