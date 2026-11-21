import test from 'node:test';
import assert from 'node:assert/strict';
import { plainText, editTranslation } from '../../resources/js/homepage-editor-state.js';

test('limits plain text to a safe length and removes markup delimiters and control characters', () => {
    assert.equal(plainText('Été\nà\tParis', 30), 'Été à Paris');
    assert.equal(plainText('<b>Black Milk</b>', 30), 'Black Milk');
    assert.equal(plainText('💗abcde', 3), '💗ab');
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
