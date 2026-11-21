// Keep state as plain JSON, with no HTML, CSS, URLs or markup supplied by the editor.
export function plainText(value, max) {
    return Array.from(value.replace(/<[^>]*>/g, '').replace(/[\r\n\t]+/g, ' ').replace(/[<>\p{Cc}]/gu, '')).slice(0, max).join('');
}

export function editTranslation(settings, locale, key, value, fields) {
    if (!Object.hasOwn(settings.translations, locale) || !Object.hasOwn(settings.translations[locale], key) || !Object.hasOwn(fields, key)) return settings;

    return {
        ...settings,
        translations: {
            ...settings.translations,
            [locale]: { ...settings.translations[locale], [key]: plainText(value, fields[key].max) },
        },
    };
}

export function editDiscount(settings, rawValue) {
    if (rawValue === '') return { ...settings, badge_percent: null };
    if (!/^\d{1,3}$/.test(rawValue)) return settings;
    const value = Number(rawValue);
    return value >= 1 && value <= 100 ? { ...settings, badge_percent: value } : settings;
}

export function formatDiscount(value) {
    return Number.isInteger(value) && value >= 1 && value <= 100 ? `−${value}\u00a0%` : '—';
}
