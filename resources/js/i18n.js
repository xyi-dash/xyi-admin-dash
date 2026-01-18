import { createI18n } from 'vue-i18n'
import en from './lang/en.json'
import ru from './lang/ru.json'

const savedLocale = localStorage.getItem('locale') || 'ru'

const i18n = createI18n({
  legacy: false,
  locale: savedLocale,
  fallbackLocale: 'en',
  messages: { en, ru }
})

export function setLocale(locale) {
  i18n.global.locale.value = locale
  localStorage.setItem('locale', locale)
}

export function getLocale() {
  return i18n.global.locale.value
}

export default i18n

