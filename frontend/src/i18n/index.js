import { createI18n } from 'vue-i18n'
import en from './messages/en'
import pt from './messages/pt'

const LOCALE_KEY = 'surveyflow_locale'
const supportedLocales = ['pt', 'en']

function detectLocale() {
  const storedLocale = localStorage.getItem(LOCALE_KEY)
  if (supportedLocales.includes(storedLocale)) return storedLocale
  return navigator.language?.toLowerCase().startsWith('pt') ? 'pt' : 'en'
}

export const i18n = createI18n({
  legacy: false,
  locale: detectLocale(),
  fallbackLocale: 'pt',
  messages: { pt, en },
})

export function setLocale(locale) {
  if (!supportedLocales.includes(locale)) return
  i18n.global.locale.value = locale
  localStorage.setItem(LOCALE_KEY, locale)
  document.documentElement.lang = locale === 'pt' ? 'pt-PT' : 'en'
  document.title = 'SurveyFlow'
}

export function translate(key, parameters) {
  return i18n.global.t(key, parameters)
}

setLocale(i18n.global.locale.value)
