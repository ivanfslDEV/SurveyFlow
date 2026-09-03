<script setup>
import { computed, reactive, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'

const props = defineProps({
  question: { type: Object, default: null },
  position: { type: Number, default: 1 },
  loading: Boolean,
})
const emit = defineEmits(['save', 'cancel'])
const localError = ref('')
const { t } = useI18n({ useScope: 'global' })
const form = reactive({ title: '', type: 'text', required: false, position: 1, options: [] })
const isChoice = computed(() => ['single_choice', 'multiple_choice'].includes(form.type))

const typeLabels = computed(() => ({
  text: t('questionForm.types.text'),
  single_choice: t('questionForm.types.single_choice'),
  multiple_choice: t('questionForm.types.multiple_choice'),
  rating: t('questionForm.types.rating'),
}))

function resetForm() {
  form.title = props.question?.title || ''
  form.type = props.question?.type || 'text'
  form.required = props.question?.required ?? false
  form.position = props.question?.position || props.position
  form.options = props.question?.options?.map((option) => ({ label: option.label })) || []
  if (isChoice.value && form.options.length < 2) form.options = [{ label: '' }, { label: '' }]
  localError.value = ''
}

function syncType() {
  if (isChoice.value && form.options.length < 2) form.options = [{ label: '' }, { label: '' }]
  if (!isChoice.value) form.options = []
}

function addOption() {
  form.options.push({ label: '' })
}

function removeOption(index) {
  if (form.options.length > 2) form.options.splice(index, 1)
}

function submit() {
  localError.value = ''
  const options = form.options.map((option) => option.label.trim()).filter(Boolean)

  if (isChoice.value && options.length < 2) {
    localError.value = t('questionForm.minOptions')
    return
  }

  if (new Set(options.map((label) => label.toLocaleLowerCase())).size !== options.length) {
    localError.value = t('questionForm.uniqueOptions')
    return
  }

  emit('save', {
    title: form.title.trim(),
    type: form.type,
    required: form.required,
    position: Number(form.position),
    options: isChoice.value
      ? options.map((label, index) => ({ label, position: index + 1 }))
      : [],
  })
}

watch(() => [props.question, props.position], resetForm, { immediate: true })
</script>

<template>
  <form class="question-form" @submit.prevent="submit">
    <div v-if="localError" class="alert" role="alert">{{ localError }}</div>
    <div class="field">
      <label for="question-title">{{ t('questionForm.titleLabel') }}</label>
      <input id="question-title" v-model="form.title" class="input" maxlength="255" :placeholder="t('questionForm.titlePlaceholder')" required autofocus />
    </div>
    <div class="question-form__row">
      <div class="field">
        <label for="question-type">{{ t('questionForm.typeLabel') }}</label>
        <select id="question-type" v-model="form.type" class="select" @change="syncType">
          <option v-for="(label, value) in typeLabels" :key="value" :value="value">{{ label }}</option>
        </select>
      </div>
      <div class="field">
        <label for="question-position">{{ t('questionForm.positionLabel') }}</label>
        <input id="question-position" v-model.number="form.position" class="input" type="number" min="1" required />
      </div>
    </div>

    <div v-if="isChoice" class="options-editor">
      <span class="field__label">{{ t('questionForm.optionsLabel') }}</span>
      <div v-for="(option, index) in form.options" :key="index" class="option-row">
        <span>{{ index + 1 }}</span>
        <input v-model="option.label" class="input" :placeholder="t('questionForm.optionPlaceholder', { number: index + 1 })" required />
        <button class="icon-button" type="button" :disabled="form.options.length <= 2" :aria-label="t('questionForm.removeOption')" @click="removeOption(index)">×</button>
      </div>
      <button class="add-option" type="button" @click="addOption">＋ {{ t('questionForm.addOption') }}</button>
    </div>

    <label class="checkbox required-check">
      <input v-model="form.required" type="checkbox" /> {{ t('questionForm.required') }}
    </label>

    <div class="form-actions">
      <button class="button button--soft" type="button" @click="emit('cancel')">{{ t('common.cancel') }}</button>
      <button class="button" :disabled="loading || !form.title.trim()">
        <span v-if="loading" class="spinner"></span>{{ loading ? t('questionForm.saving') : question ? t('questionForm.saveChanges') : t('questionForm.addQuestion') }}
      </button>
    </div>
  </form>
</template>

<style scoped>
.question-form__row { display: grid; grid-template-columns: minmax(0, 1fr) 105px; gap: 1rem; margin-top: 1rem; }
.question-form__row .field { margin: 0; }
.options-editor { display: grid; gap: .6rem; margin-top: 1.15rem; padding: 1rem; border-radius: 12px; background: var(--surface-muted); }
.option-row { display: grid; grid-template-columns: 25px 1fr 34px; align-items: center; gap: .5rem; }
.option-row > span { width: 24px; height: 24px; display: grid; place-items: center; border-radius: 7px; background: white; color: var(--ink-soft); font-size: .7rem; font-weight: 800; }
.option-row .input { min-height: 39px; background: white; }
.add-option { width: max-content; margin: .15rem 0 0 2.1rem; padding: .2rem; border: 0; background: none; color: var(--teal); cursor: pointer; font-size: .78rem; font-weight: 750; }
.required-check { margin-top: 1rem; }
.form-actions .spinner { width: 16px; height: 16px; border-color: rgba(255,255,255,.25); border-top-color: white; }
@media (max-width: 520px) { .question-form__row { grid-template-columns: 1fr; } }
</style>
