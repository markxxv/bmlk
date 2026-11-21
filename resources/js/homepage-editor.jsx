import React, { useEffect, useLayoutEffect, useRef, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { createPortal } from 'react-dom';
import { editTranslation, editDiscount, formatDiscount, editBenefitIcon } from './homepage-editor-state';

const languageNames = { fr: 'French', en: 'English', es: 'Spanish' };
const longFields = new Set(['description', 'meta_description', 'eyebrow']);

function PlainInput({ field, value, onChange, onFocus, onBlur, id, disabled, required = true, autoFocus = false }) {
    const props = {
        id, value, disabled, autoFocus, maxLength: field.max, required,
        onChange: event => onChange(event.target.value), onFocus, onBlur,
        onKeyDown: event => {
            if (event.key === 'Enter' && !event.nativeEvent.isComposing) event.preventDefault();
        },
    };

    return field.max >= 100 && field.label !== 'Meta title'
        ? <textarea {...props} rows={field.max > 200 ? 3 : 2} />
        : <input {...props} type="text" />;
}

function DiscountInput({ value, onChange, ...props }) {
    return <input {...props} type="text" inputMode="numeric" maxLength={3} pattern="[0-9]{1,3}" value={value ?? ''} aria-invalid={!Number.isInteger(value)} onChange={event => onChange(event.target.value)} onKeyDown={event => { if (event.key === 'Enter') event.preventDefault(); }} />;
}

function IconGlyph({ asset }) {
    return asset ? <span aria-hidden="true" className="he-lucide-glyph" style={{ maskImage: `url("data:image/svg+xml,${encodeURIComponent(asset)}")` }} /> : <span aria-hidden="true" className="he-lucide-placeholder">…</span>;
}

function IconPicker({ label, selected, url, onSelect, onClose }) {
    const dialog = useRef(null);
    const search = useRef(null);
    const [query, setQuery] = useState('');
    const [page, setPage] = useState(1);
    const [result, setResult] = useState(null);
    const [error, setError] = useState(false);
    const [retry, setRetry] = useState(0);

    useEffect(() => {
        const element = dialog.current;
        element.showModal();
        search.current.focus();
        return () => { if (element.open) element.close(); };
    }, []);

    useEffect(() => {
        const controller = new AbortController();
        setResult(null);
        setError(false);
        const timer = setTimeout(async () => {
            try {
                const endpoint = new URL(url, window.location.origin);
                endpoint.searchParams.set('q', query);
                endpoint.searchParams.set('page', page);
                const response = await fetch(endpoint, { signal: controller.signal, headers: { Accept: 'application/json' } });
                if (!response.ok) throw new Error('Could not load icons');
                const data = await response.json();
                if (!controller.signal.aborted) setResult(data);
            } catch (exception) {
                if (!controller.signal.aborted) setError(true);
            }
        }, query ? 180 : 0);
        return () => { clearTimeout(timer); controller.abort(); };
    }, [url, query, page, retry]);

    const pages = result ? Math.max(1, Math.ceil(result.total / result.per_page)) : 1;
    return createPortal(<dialog ref={dialog} className="he-icon-dialog" aria-labelledby="he-icon-dialog-title" onCancel={event => { event.preventDefault(); onClose(); }} onClick={event => { if (event.target === dialog.current) onClose(); }}>
        <div className="he-icon-dialog-body">
            <header className="he-icon-header">
                <div><strong id="he-icon-dialog-title">Choose icon · {label}</strong><small>Lucide icons · Current: {selected}</small></div>
                <button type="button" className="he-icon-close" aria-label="Close icon picker" onClick={onClose}>×</button>
            </header>
            <input ref={search} className="he-icon-search" type="search" maxLength={80} placeholder="Search icons (e.g. heart, shield, leaf)" aria-label="Search Lucide icons" value={query} onChange={event => { setQuery(event.target.value); setPage(1); }} onKeyDown={event => { if (event.key === 'Enter') event.preventDefault(); }} />
            <div className="he-icon-results" aria-busy={!result && !error}>
                {error ? <div className="he-icon-empty" role="alert">Could not load icons.<button type="button" onClick={() => setRetry(retry + 1)}>Try again</button></div>
                    : !result ? <div className="he-icon-empty" role="status">Loading icons…</div>
                    : !result.total ? <div className="he-icon-empty" role="status">No icons found. Try another keyword.</div>
                    : <div className="he-icon-grid">{result.icons.map(icon => <button type="button" className="he-icon-option" key={icon.name} title={icon.name} aria-label={icon.name} aria-pressed={selected === icon.name} onClick={() => onSelect(icon)}><IconGlyph asset={icon.svg} /><span>{icon.name}</span></button>)}</div>}
            </div>
            <footer className="he-icon-footer">
                <span aria-live="polite">{result ? `${result.total} icons · Page ${result.page} of ${pages}` : 'Current: '+selected}</span>
                <div><button type="button" aria-label="Previous page" disabled={!result || result.page <= 1} onClick={() => setPage(page - 1)}>‹</button><button type="button" aria-label="Next page" disabled={!result || result.page >= pages} onClick={() => setPage(page + 1)}>›</button></div>
            </footer>
        </div>
    </dialog>, window.document.body);
}

function Preview({ settings, locale, config, onEdit, highlighted, onHighlight, disabled, iconSvgs, onIconPick }) {
    const iframe = useRef(null);
    const viewport = useRef(null);
    const [device, setDevice] = useState('desktop');
    const [document, setDocument] = useState(null);
    const [failed, setFailed] = useState(false);
    const [width, setWidth] = useState(0);
    const [height, setHeight] = useState(760);
    const [inline, setInline] = useState(null);
    const widthPx = device === 'desktop' ? 1366 : 390;
    const scale = Math.min(1, (width || 1) / widthPx);
    const copy = settings.translations[locale];

    useLayoutEffect(() => {
        const observer = new ResizeObserver(([entry]) => setWidth(entry.contentRect.width));
        observer.observe(viewport.current);
        return () => observer.disconnect();
    }, []);

    useEffect(() => {
        if (!document) return;
        const hero = document.querySelector('[data-home-hero]');
        if (!hero) return;

        hero.querySelectorAll('[data-home-field]').forEach(element => {
            element.tabIndex = 0;
            element.setAttribute('role', 'button');
            element.setAttribute('aria-label', `Edit ${config.fields[element.dataset.homeField]?.label}`);
        });
        hero.querySelectorAll('[data-home-icon]').forEach(element => {
            element.tabIndex = 0;
            element.setAttribute('role', 'button');
            element.setAttribute('aria-label', `Change icon for ${config.fields[element.dataset.homeIcon]?.label}`);
        });
        hero.querySelectorAll('a').forEach(link => {
            // Never navigate away from the unsaved editor.
            link.removeAttribute('href');
            link.tabIndex = -1;
        });

        const observer = new ResizeObserver(() => setHeight(Math.ceil(hero.getBoundingClientRect().height)));
        observer.observe(hero);
        return () => observer.disconnect();
    }, [document, config.fields]);

    useLayoutEffect(() => {
        const hero = document?.querySelector('[data-home-hero]');
        if (!hero) return;
        hero.dataset.theme = settings.theme;
        hero.dataset.campaign = String(settings.badge_enabled);
        hero.querySelectorAll('[data-home-badge]').forEach(element => { element.hidden = !settings.badge_enabled; });
        const note = hero.querySelector('[data-home-offer-note]');
        if (note) note.hidden = !settings.badge_enabled || !copy.badge_note?.trim();
        document.documentElement.lang = locale;
        hero.querySelectorAll('[data-home-field]').forEach(element => {
            const key = element.dataset.homeField;
            const value = key === 'badge_percent' ? formatDiscount(settings.badge_percent) : (copy[key] ?? '');
            if (element.textContent !== value) element.textContent = value;
            element.toggleAttribute('data-editing', highlighted === key);
        });
    }, [document, settings.theme, settings.badge_enabled, settings.badge_percent, copy, locale, highlighted]);

    useLayoutEffect(() => {
        document?.querySelectorAll('[data-home-icon]').forEach(element => {
            const name = settings.benefit_icons[element.dataset.homeIcon];
            const asset = iconSvgs[name];
            if (!asset || element.dataset.iconName === name) return;
            // The SVG comes exclusively from the installed, server-validated Lucide catalogue.
            const svg = new DOMParser().parseFromString(asset, 'image/svg+xml').documentElement;
            if (svg.localName !== 'svg') return;
            svg.setAttribute('class', 'h-full w-full');
            svg.setAttribute('stroke-width', '1.4');
            element.replaceChildren(document.importNode(svg, true));
            element.dataset.iconName = name;
        });
    }, [document, settings.benefit_icons, iconSvgs]);

    useEffect(() => {
        if (!document) return;
        const select = event => {
            if (event.type === 'keydown' && !['Enter', ' '].includes(event.key)) return;
            const icon = event.target.closest?.('[data-home-icon]');
            if (icon && !disabled) {
                event.preventDefault();
                event.stopPropagation();
                setInline(null);
                onIconPick(icon.dataset.homeIcon);
                return;
            }
            const element = event.target.closest?.('[data-home-field]');
            if (!element || disabled) return;
            event.preventDefault();
            event.stopPropagation();
            const key = element.dataset.homeField;
            const rect = element.getBoundingClientRect();
            const popupWidth = Math.min(400, document.documentElement.clientWidth - 24);
            onHighlight(key);
            setInline({ key, left: Math.max(12, Math.min(rect.left, document.documentElement.clientWidth - popupWidth - 12)), top: Math.max(12, Math.min(rect.top, document.documentElement.clientHeight - 132)), width: popupWidth });
        };
        const close = event => {
            if (!event.target.closest?.('[data-home-field], #hero-inline-editor')) setInline(null);
        };
        document.addEventListener('click', select);
        document.addEventListener('keydown', select);
        document.addEventListener('pointerdown', close);
        return () => {
            document.removeEventListener('click', select);
            document.removeEventListener('keydown', select);
            document.removeEventListener('pointerdown', close);
        };
    }, [document, disabled, onHighlight, onIconPick]);

    useEffect(() => setInline(null), [locale, device, disabled, settings.badge_enabled]);

    const edit = (key, value) => onEdit(key, value);
    const portal = document?.getElementById('hero-inline-editor');

    return <div className="he-preview">
        <div className="he-toolbar">
            <div><strong>Live preview</strong><small>Click any text or benefit icon to edit it.</small></div>
            <div className="he-segmented" aria-label="Preview device">
                <button type="button" aria-pressed={device === 'desktop'} onClick={() => setDevice('desktop')}>Desktop</button>
                <button type="button" aria-pressed={device === 'mobile'} onClick={() => setDevice('mobile')}>Mobile</button>
            </div>
        </div>
        <div className="he-canvas" ref={viewport}>
            {failed && <p role="alert">Could not load the storefront preview. Reload this page.</p>}
            <div className="he-iframe-wrap" style={{ width: widthPx * scale, height: height * scale }}>
                <iframe ref={iframe} title="Editable homepage hero" src={config.previewUrl} style={{ width: widthPx, height, transform: `scale(${scale})` }} onLoad={() => {
                    const doc = iframe.current.contentDocument;
                    if (doc?.querySelector('[data-home-hero]')) { setDocument(doc); setFailed(false); }
                    else setFailed(true);
                }} />
            </div>
        </div>
        <div className="he-preview-footer"><span><i /> Changes are previewed instantly</span><span>{widthPx}px · {Math.round(scale * 100)}%</span></div>
        {inline && portal && !disabled && createPortal(
            <div className="hero-inline-box" style={{ position: 'fixed', left: inline.left, top: inline.top, width: inline.width, zIndex: 1000, padding: 12, borderRadius: 12, boxShadow: '0 10px 40px #0003', background: '#fff', color: '#3A2E30', fontFamily: 'Hanken Grotesk, sans-serif' }} onKeyDown={event => {
                if (event.key === 'Enter' || event.key === 'Escape') { event.preventDefault(); event.stopPropagation(); setInline(null); }
            }}>
                <label style={{ display: 'block', fontSize: 13, fontWeight: 600, marginBottom: 5 }} htmlFor="hero-inline-value">{config.fields[inline.key].label}</label>
                {inline.key === 'badge_percent'
                    ? <DiscountInput id="hero-inline-value" autoFocus value={settings.badge_percent} onChange={value => edit(inline.key, value)} onBlur={() => setInline(null)} style={{ width: '100%', background: '#F7F5F6', padding: '10px 12px', fontSize: Math.min(28, 16 / scale), borderRadius: 8, outline: '2px solid #A9636F' }} />
                    : <input id="hero-inline-value" type="text" autoFocus value={copy[inline.key] ?? ''} maxLength={config.fields[inline.key].max} onChange={event => edit(inline.key, event.target.value)} onBlur={() => setInline(null)} style={{ width: '100%', background: '#F7F5F6', padding: '10px 12px', fontSize: Math.min(28, 16 / scale), borderRadius: 8, outline: '2px solid #A9636F' }} />}
                <small style={{ display: 'block', marginTop: 7, fontSize: 12 }}>{inline.key === 'badge_percent' ? '1–100% · all languages' : 'Enter to finish · plain text only'}</small>
            </div>, portal,
        )}
    </div>;
}

function HomepageEditor(props) {
    const config = props.configuration;
    const [settings, setSettings] = useState(props.value);
    const current = useRef(props.value);
    const [locale, setLocale] = useState(config.locales[0]);
    const [highlighted, setHighlighted] = useState(null);
    const [iconPicker, setIconPicker] = useState(null);
    const [iconSvgs, setIconSvgs] = useState(() => Object.fromEntries(config.selectedIcons.map(icon => [icon.name, icon.svg])));
    const disabled = props.isDisabled || props.isReadOnly;

    useEffect(() => {
        if (props.value && JSON.stringify(props.value) !== JSON.stringify(current.current)) {
            current.current = props.value;
            setSettings(props.value);
        }
    }, [props.value]);

    useEffect(() => {
        setIconSvgs(previous => ({ ...previous, ...Object.fromEntries(config.selectedIcons.map(icon => [icon.name, icon.svg])) }));
    }, [config.selectedIcons]);

    if (!settings?.translations?.[locale]) return <p role="alert">Homepage settings could not be loaded.</p>;

    function emit(next) {
        if (disabled) return;
        current.current = next;
        setSettings(next);
        props.onChange(next);
    }

    function edit(key, value) {
        emit(key === 'badge_percent' ? editDiscount(current.current, value) : editTranslation(current.current, locale, key, value, config.fields));
    }

    function field(key) {
        const definition = config.fields[key];
        const id = `${props.id}-${locale}-${key}`;
        const value = key === 'badge_percent' ? settings.badge_percent : (settings.translations[locale][key] ?? '');
        const inputProps = { id, value, disabled, onChange: next => edit(key, next), onFocus: () => setHighlighted(key), onBlur: props.onBlur };
        const iconName = settings.benefit_icons[key];
        return <div key={key} className={`he-field ${highlighted === key ? 'he-field-selected' : ''}`}>
            <label htmlFor={id}>{definition.label}{longFields.has(key) && <small>{Array.from(value).length}/{definition.max}</small>}</label>
            <div className={iconName ? 'he-benefit-input' : undefined}>
                {iconName && <button type="button" className="he-icon-trigger" title={`Change icon: ${iconName}`} aria-label={`Change icon for ${definition.label}: ${iconName}`} aria-haspopup="dialog" disabled={disabled} onClick={() => { setHighlighted(key); setIconPicker(key); }}><IconGlyph asset={iconSvgs[iconName]} /><span aria-hidden="true" className="he-icon-caret" /></button>}
                {key === 'badge_percent' ? <DiscountInput {...inputProps} /> : <PlainInput {...inputProps} field={definition} required={key !== 'badge_note'} />}
            </div>
        </div>;
    }

    return <div className="he-editor" id={props.id} aria-describedby={props.ariaDescribedBy} aria-invalid={props.isInvalid || undefined}>
        <div className="he-grid">
            <Preview settings={settings} locale={locale} config={config} onEdit={edit} highlighted={highlighted} onHighlight={setHighlighted} disabled={disabled} iconSvgs={iconSvgs} onIconPick={key => { setHighlighted(key); setIconPicker(key); }} />
            <aside className="he-panel">
                <div className="he-toolbar">
                    <div><strong>Hero settings</strong><small>{languageNames[locale] ?? locale.toUpperCase()} copy</small></div>
                    <div className="he-segmented" aria-label="Content language">
                        {config.locales.map(lang => <button key={lang} type="button" disabled={disabled} aria-pressed={lang === locale} onClick={() => { setLocale(lang); setHighlighted(null); }}>{lang.toUpperCase()}</button>)}
                    </div>
                </div>
                <div className="he-panel-body">
                    <div className="he-section"><h3>Colour scheme</h3>
                        <div className="he-themes">
                            {Object.entries(config.themes).map(([key, theme]) => <button key={key} type="button" disabled={disabled} className="he-theme" aria-pressed={settings.theme === key} onClick={() => emit({ ...current.current, theme: key })}>
                                <span className="he-swatch" style={{ background: theme.background, color: theme.text }}><b>Aa</b><i style={{ background: theme.script }} /></span>
                                <span>{theme.label}</span>{settings.theme === key && <span className="he-check">✓</span>}
                            </button>)}
                        </div>
                    </div>
                    <div className="he-section"><h3>Campaign</h3>
                        <label className="he-toggle"><span>Show promotional offer</span><input type="checkbox" role="switch" checked={settings.badge_enabled} disabled={disabled} onChange={event => emit({ ...current.current, badge_enabled: event.target.checked })} /></label>
                        {settings.badge_enabled && <>
                            <div className="he-campaign-fields">{field('badge')}{field('badge_percent')}</div>
                            <p className="he-field-help">One discount for the text and round badge, in every language.</p>
                            {field('badge_note')}
                        </>}
                    </div>
                    <div className="he-section"><h3>Hero copy</h3>
                        {field('eyebrow')}{field('headline_script')}{field('headline')}{field('description')}
                        <div className="he-pair">{field('primary_button')}{field('secondary_button')}</div>
                    </div>
                    <div className="he-section"><h3>Benefits</h3>
                        <div className="he-pair">{field('feature_one')}{field('feature_one_detail')}{field('feature_two')}{field('feature_three')}</div>
                        <p className="he-field-help">Click an icon to change it for all languages.</p>
                    </div>
                </div>
            </aside>
        </div>
        <details className="he-seo">
            <summary><span><strong>Search engine optimisation</strong><small>Homepage meta title and description · {locale.toUpperCase()}</small></span><span className="he-chevron"><IconGlyph asset={config.chevronDown} /></span></summary>
            <div className="he-seo-body">{field('meta_title')}{field('meta_description')}</div>
        </details>
        {iconPicker && !disabled && <IconPicker key={iconPicker} label={config.fields[iconPicker].label} selected={settings.benefit_icons[iconPicker]} url={config.iconCatalogUrl} onClose={() => setIconPicker(null)} onSelect={icon => {
            setIconSvgs(previous => ({ ...previous, [icon.name]: icon.svg }));
            emit(editBenefitIcon(current.current, iconPicker, icon.name));
            setIconPicker(null);
        }} />}
    </div>;
}

export default function mountHomepageEditor({ host, props: initialProps }) {
    const root = createRoot(host);
    const update = props => root.render(<HomepageEditor {...props} />);
    update(initialProps);
    return { update, destroy: () => root.unmount() };
}
