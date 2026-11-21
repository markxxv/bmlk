import test from 'node:test';
import assert from 'node:assert/strict';
import { plainText, editTranslation, editDiscount, formatDiscount } from '../../resources/js/homepage-editor-state.js';

test('limits plain text to a safe length and removes markup delimiters and control characters', () => {
    assert.equal(plainText('Été\nà\tParis', 30), 'Été à Paris');
    assert.equal(plainText('<b>Black Milk</b>', 30), 'Black Milk');
    assert.equal(plainText('💗abcde', 3), '💗ab');
});

test('discount is numeric, restricted to 1–100, and shared by every translation and both surfaces', () => {
    const original = { badge_percent: 70, translations: { fr: { badge: 'Jusqu’à' }, en: { badge: 'Up to' } } };
    const edited = editDiscount(original, '55');
    assert.equal(edited.badge_percent, 55);
    assert.equal(formatDiscount(edited.badge_percent), '−55\u00a0%');
    assert.strictEqual(edited.translations, original.translations);
    assert.equal(original.badge_percent, 70);
    assert.equal(editDiscount(edited, '').badge_percent, null);
    assert.equal(formatDiscount(null), '—');
    assert.equal(editDiscount(edited, '100').badge_percent, 100);
    for (const invalid of ['0', '101', '-5', '20.5', '70%', 'abc']) assert.strictEqual(editDiscount(edited, invalid), edited);
});

test('both editing surfaces update the same immutable locale state', () => {
    const original = { theme: 'signature', badge_enabled: false, translations: { fr: { headline: 'Bonjour' }, en: { headline: 'Hello' } } };
    const fields = { headline: { max: 12 } };
    const changed = editTranslation(original, 'en', 'headline', 'New headline', fields);
    assert.equal(changed.translations.en.headline, 'New headline');
    assert.equal(changed.translations.fr.headline, 'Bonjour');
    assert.equal(original.translations.en.headline, 'Hello');
    assert.equal(editTranslation(original, 'de', 'headline', 'Hallo', fields), original);
    assert.equal(editTranslation(original, 'en', 'unknown', 'hey', fields), original);
});
