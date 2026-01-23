import { createI18n } from 'vue-i18n';
import en from './en.json';
import ru from './ru.json';

// locale persistence. if it breaks, blame localStorage. or me. probably me.
const getStoredLocale = () => {
    try {
        return localStorage.getItem('locale') || 'ru';
    } catch {
        return 'ru';
    }
};

const i18n = createI18n({
    legacy: false,
    locale: getStoredLocale(),
    fallbackLocale: 'en',
    messages: { en, ru }
});

export function setLocale(locale) {
    i18n.global.locale.value = locale;
    try {
        localStorage.setItem('locale', locale);
    } catch {
        // localStorage said no. it happens.
    }
}

export function getLocale() {
    return i18n.global.locale.value;
}

export default i18n;
