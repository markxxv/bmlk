import { animate, cubicBezier, inView, scroll, stagger } from 'motion';

const editorialEase = cubicBezier(0.16, 1, 0.3, 1);
const parallaxEase = cubicBezier(0.22, 1, 0.36, 1);
const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

const number = (value, fallback) => {
    const parsed = Number.parseFloat(value);

    return Number.isFinite(parsed) ? parsed : fallback;
};

const boolean = (value, fallback = false) => {
    if (value === undefined) return fallback;

    return value === 'true';
};

const once = (element, key) => {
    const attribute = `animation${key}`;

    if (element.dataset[attribute] === 'true') return false;

    element.dataset[attribute] = 'true';

    return true;
};

const resetStyles = (element, styles = {}) => {
    Object.entries(styles).forEach(([property, value]) => {
        element.style[property] = value;
    });
};

const primeLazyImages = (elements) => {
    const targets = Array.isArray(elements) ? elements : [elements];

    targets.forEach((target) => {
        const images = target.matches?.('img[loading="lazy"]')
            ? [target]
            : [...target.querySelectorAll?.('img[loading="lazy"]') ?? []];

        images.forEach((image) => {
            image.loading = 'eager';

            if (!image.complete) {
                const src = image.getAttribute('src');

                if (src) {
                    image.src = src;
                }
            }
        });
    });
};

const preloadLazyImagesNearViewport = (elements) => {
    const targets = Array.isArray(elements) ? elements : [elements];

    if (!('IntersectionObserver' in window)) {
        primeLazyImages(targets);
        return;
    }

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) return;

            primeLazyImages(entry.target);
            observer.unobserve(entry.target);
        });
    }, {
        rootMargin: '600px 0px',
        threshold: 0,
    });

    targets.forEach((target) => observer.observe(target));
};

const transitionGuard = (elements) => {
    const targets = Array.isArray(elements) ? elements : [elements];
    const originalTransitions = new Map(targets.map((target) => [target, target.style.transition]));
    let restoreTimer = null;

    const disable = () => {
        window.clearTimeout(restoreTimer);

        targets.forEach((target) => {
            target.style.transition = 'none';
        });
    };

    const restore = () => {
        targets.forEach((target) => {
            target.style.transition = originalTransitions.get(target) ?? '';
        });
    };

    const restoreAfter = (seconds = 0) => {
        window.clearTimeout(restoreTimer);
        restoreTimer = window.setTimeout(restore, Math.max(seconds * 1000, 0) + 50);
    };

    return {
        disable,
        restore,
        restoreAfter,
    };
};

export function initReveal(root = document) {
    root.querySelectorAll('[data-reveal]').forEach((element) => {
        if (!once(element, 'Reveal')) return;

        const duration = number(element.dataset.revealDuration, 0.9);
        const delay = number(element.dataset.revealDelay, 0);
        const x = number(element.dataset.revealX, 0);
        const y = number(element.dataset.revealY, 24);
        const scale = number(element.dataset.revealScale, 1);
        const repeat = !boolean(element.dataset.revealOnce, false);
        const transitions = transitionGuard(element);

        if (reducedMotion.matches) {
            resetStyles(element, {
                opacity: '1',
                transform: 'none',
            });

            return;
        }

        const hidden = {
            opacity: 0,
            transform: `translate3d(${x}px, ${y}px, 0) scale(${scale})`,
        };

        transitions.disable();
        resetStyles(element, hidden);

        let animation = null;

        inView(element, () => {
            transitions.disable();
            animation?.stop();

            animation = animate(
                element,
                {
                    opacity: [0, 1],
                    transform: [
                        `translate3d(${x}px, ${y}px, 0) scale(${scale})`,
                        'translate3d(0, 0, 0) scale(1)',
                    ],
                },
                {
                    duration,
                    delay,
                    ease: editorialEase,
                },
            );

            transitions.restoreAfter(delay + duration);

            if (!repeat) return;

            return () => {
                animation?.stop();
                transitions.disable();
                resetStyles(element, hidden);
                transitions.restoreAfter();
            };
        }, {
            amount: number(element.dataset.revealAmount, 0.2),
        });
    });
}

export function initBlurReveal(root = document) {
    root.querySelectorAll('[data-blur-reveal]').forEach((container) => {
        if (!once(container, 'BlurReveal')) return;

        const items = container.querySelectorAll('[data-blur-reveal-item]');
        const targets = items.length ? [...items] : [container];
        const delay = number(container.dataset.blurDelay, 0);
        const staggerDelay = number(container.dataset.blurStagger, 0.1);
        const duration = number(container.dataset.blurDuration, 0.85);
        const amount = number(container.dataset.blurAmount, 0.2);
        const blur = number(container.dataset.blurPixels, 14);
        const y = number(container.dataset.blurY, 22);
        const repeat = !boolean(container.dataset.blurOnce, false);
        const perItem = boolean(container.dataset.blurPerItem, false);
        const transitions = transitionGuard(targets);

        preloadLazyImagesNearViewport(targets);

        if (reducedMotion.matches) {
            animate(targets, {
                opacity: 1,
                filter: 'blur(0px)',
                y: 0,
            }, {
                duration: 0,
            });

            return;
        }

        if (perItem && targets.length > 1) {
            targets.forEach((target) => {
                const targetTransitions = transitionGuard(target);
                let animation = null;

                const resetTarget = () => {
                    targetTransitions.disable();

                    animate(target, {
                        opacity: 0,
                        filter: `blur(${blur}px)`,
                        y,
                    }, {
                        duration: 0,
                    });
                };

                resetTarget();

                inView(target, () => {
                    targetTransitions.disable();
                    animation?.stop();
                    primeLazyImages(target);

                    animation = animate(target, {
                        opacity: 1,
                        filter: 'blur(0px)',
                        y: 0,
                    }, {
                        duration,
                        delay,
                        ease: editorialEase,
                    });

                    targetTransitions.restoreAfter(delay + duration);

                    if (!repeat) return;

                    return () => {
                        animation?.stop();
                        resetTarget();
                        targetTransitions.restoreAfter();
                    };
                }, { amount });
            });

            return;
        }

        const reset = () => {
            transitions.disable();

            animate(targets, {
                opacity: 0,
                filter: `blur(${blur}px)`,
                y,
            }, {
                duration: 0,
            });
        };

        reset();

        let animation = null;

        inView(container, () => {
            transitions.disable();
            animation?.stop();
            primeLazyImages(targets);

            animation = animate(
                targets,
                {
                    opacity: 1,
                    filter: 'blur(0px)',
                    y: 0,
                },
                {
                    duration,
                    delay: stagger(staggerDelay, { startDelay: delay }),
                    ease: editorialEase,
                },
            );

            const totalDuration = delay + duration + Math.max(targets.length - 1, 0) * staggerDelay;
            transitions.restoreAfter(totalDuration);

            if (!repeat) return;

            return () => {
                animation?.stop();
                reset();
                transitions.restoreAfter();
            };
        }, { amount });
    });
}

const prepareTitleLines = (title) => {
    const explicit = [...title.querySelectorAll(':scope > [data-reveal-title-line]')];

    let lines = explicit;

    if (!lines.length) {
        const directSpans = [...title.children].filter((child) => child.tagName === 'SPAN');

        if (!directSpans.length) {
            const inner = document.createElement('span');
            inner.dataset.revealTitleLine = '';
            inner.className = 'block';
            inner.innerHTML = title.innerHTML;

            title.innerHTML = '';
            title.append(inner);

            lines = [inner];
        } else {
            directSpans.forEach((line) => {
                line.dataset.revealTitleLine = '';
            });

            lines = directSpans;
        }
    }

    lines.forEach((line) => {
        if (line.parentElement?.dataset.revealTitleMask !== undefined) return;

        const display = getComputedStyle(line).display;
        const mask = document.createElement('span');

        mask.dataset.revealTitleMask = '';
        mask.style.display = display === 'block' ? 'block' : 'inline-block';
        mask.style.overflow = 'hidden';
        mask.style.paddingBlock = '0.14em';
        mask.style.marginBlock = '-0.14em';
        mask.style.verticalAlign = 'bottom';

        line.before(mask);
        mask.append(line);
    });

    return lines;
};

export function initTitleReveal(root = document) {
    root.querySelectorAll('[data-reveal-title]').forEach((title) => {
        if (!once(title, 'TitleReveal')) return;

        const lines = prepareTitleLines(title);
        const duration = number(title.dataset.revealTitleDuration, 1.15);
        const staggerDelay = number(title.dataset.revealTitleStagger, 0.09);
        const amount = number(title.dataset.revealTitleAmount, 0.2);
        const repeat = !boolean(title.dataset.revealTitleOnce, false);

        lines.forEach((line) => {
            line.style.willChange = 'transform';
        });

        if (reducedMotion.matches) {
            lines.forEach((line) => {
                line.style.transform = 'none';
            });

            return;
        }

        const hide = () => {
            lines.forEach((line) => {
                line.style.transform = 'translate3d(0, 115%, 0) rotate(1.5deg)';
            });
        };

        title.style.overflow = 'visible';
        hide();

        inView(title, () => {
            animate(
                lines,
                {
                    transform: [
                        'translate3d(0, 115%, 0) rotate(1.5deg)',
                        'translate3d(0, 0, 0) rotate(0deg)',
                    ],
                },
                {
                    duration,
                    delay: stagger(staggerDelay),
                    ease: editorialEase,
                },
            );

            if (!repeat) return;

            return hide;
        }, { amount });
    });
}

export function initImageReveal(root = document) {
    root.querySelectorAll('[data-image-reveal]').forEach((element) => {
        if (!once(element, 'ImageReveal')) return;

        const direction = element.dataset.imageReveal || 'bottom';
        const duration = number(element.dataset.imageRevealDuration, 1.05);
        const amount = number(element.dataset.imageRevealAmount, 0.2);
        const repeat = !boolean(element.dataset.imageRevealOnce, false);

        const clips = {
            bottom: ['inset(100% 0 0 0)', 'inset(0% 0 0 0)'],
            top: ['inset(0 0 100% 0)', 'inset(0% 0 0 0)'],
            left: ['inset(0 100% 0 0)', 'inset(0% 0 0 0)'],
            right: ['inset(0 0 0 100%)', 'inset(0% 0 0 0)'],
        };

        const [hidden, visible] = clips[direction] ?? clips.bottom;

        if (reducedMotion.matches) {
            element.style.clipPath = visible;

            return;
        }

        element.style.clipPath = hidden;

        inView(element, () => {
            animate(
                element,
                { clipPath: [hidden, visible] },
                {
                    duration,
                    ease: editorialEase,
                },
            );

            if (!repeat) return;

            return () => {
                element.style.clipPath = hidden;
            };
        }, { amount });
    });
}

export function initLineReveal(root = document) {
    root.querySelectorAll('[data-line-reveal]').forEach((line) => {
        if (!once(line, 'LineReveal')) return;

        const vertical = line.dataset.lineReveal === 'vertical';
        const duration = number(line.dataset.lineRevealDuration, 0.9);
        const delay = number(line.dataset.lineRevealDelay, 0);
        const amount = number(line.dataset.lineRevealAmount, 0.5);
        const repeat = !boolean(line.dataset.lineRevealOnce, false);
        const property = vertical ? 'scaleY' : 'scaleX';

        line.style.transformOrigin = line.dataset.lineOrigin
            || (vertical ? 'top' : 'left');

        if (reducedMotion.matches) {
            line.style.transform = `${property}(1)`;

            return;
        }

        const hide = () => {
            line.style.transform = `${property}(0)`;
        };

        hide();

        inView(line, () => {
            animate(
                line,
                {
                    transform: [`${property}(0)`, `${property}(1)`],
                },
                {
                    duration,
                    delay,
                    ease: editorialEase,
                },
            );

            if (!repeat) return;

            return hide;
        }, { amount });
    });
}

export function initParallax(root = document) {
    if (reducedMotion.matches) return;

    root.querySelectorAll('[data-parallax]').forEach((box) => {
        if (!once(box, 'Parallax')) return;

        const image = box.querySelector('[data-parallax-image]');

        if (!image) return;

        const distance = number(box.dataset.parallaxDistance, 8);
        const scale = number(box.dataset.parallaxScale, 1.16);

        image.style.willChange = 'transform';

        scroll((progress) => {
            const eased = parallaxEase(progress);
            const y = -distance / 2 + eased * distance;

            image.style.transform = `translate3d(0, ${y}%, 0) scale(${scale})`;
        }, {
            target: box,
            offset: ['start end', 'end start'],
            trackContentSize: true,
        });
    });
}

export function initOptionTabs(root = document) {
    root.querySelectorAll('[data-option-tabs]').forEach((group) => {
        if (!once(group, 'OptionTabs')) return;

        const indicator = group.querySelector('[data-option-indicator]');
        const tabs = [...group.querySelectorAll('[data-option-tab]')];

        if (!indicator || !tabs.length) return;

        let currentTab = null;
        let animation = null;

        const geometry = (tab) => {
            const groupRect = group.getBoundingClientRect();
            const tabRect = tab.getBoundingClientRect();

            return {
                x: tabRect.left - groupRect.left,
                y: tabRect.top - groupRect.top,
                width: tabRect.width,
                height: tabRect.height,
            };
        };

        const move = (tab, immediate = false) => {
            if (!tab) return;

            currentTab = tab;
            animation?.stop();

            const target = geometry(tab);

            animation = animate(indicator, target, immediate || reducedMotion.matches
                ? { duration: 0 }
                : {
                    type: 'spring',
                    stiffness: 460,
                    damping: 38,
                    mass: 0.7,
                });
        };

        const selectedTab = () => tabs.find((tab) => tab.dataset.selected === 'true') ?? tabs[0];

        requestAnimationFrame(() => requestAnimationFrame(() => move(selectedTab(), true)));

        tabs.forEach((tab) => {
            tab.addEventListener('click', () => move(tab));
        });

        const observer = new MutationObserver(() => {
            const selected = selectedTab();

            if (selected !== currentTab) {
                move(selected);
            }
        });

        tabs.forEach((tab) => observer.observe(tab, {
            attributes: true,
            attributeFilter: ['data-selected'],
        }));

        const resizeObserver = new ResizeObserver(() => {
            if (currentTab) {
                move(currentTab, true);
            }
        });

        resizeObserver.observe(group);
    });
}

export function initNumberTrend(root = document) {
    root.querySelectorAll('[data-number-trend]').forEach((element) => {
        if (!once(element, 'NumberTrend')) return;

        let animations = [];
        let cleanupTimer = null;
        let lockedHeight = null;

        const lockHeight = () => {
            if (lockedHeight !== null) return;

            const height = element.getBoundingClientRect().height;

            if (height > 0) {
                lockedHeight = height;
                element.style.height = `${height}px`;
            }
        };

        const stop = () => {
            animations.forEach((animation) => animation.stop());
            animations = [];
            window.clearTimeout(cleanupTimer);
        };

        const measureWidth = (text) => {
            const probe = document.createElement('span');

            probe.textContent = text;
            probe.style.position = 'absolute';
            probe.style.visibility = 'hidden';
            probe.style.whiteSpace = 'nowrap';
            probe.style.pointerEvents = 'none';

            element.append(probe);

            const width = probe.getBoundingClientRect().width;

            probe.remove();

            return width;
        };

        const format = (value, locale, currency) => {
            const number = Number(value);
            const formatter = new Intl.NumberFormat(locale, {
                style: 'currency',
                currency,
                minimumFractionDigits: Number.isInteger(number) ? 0 : 2,
                maximumFractionDigits: 2,
            });
            const parts = formatter.formatToParts(number);
            const numericTypes = new Set(['integer', 'group', 'decimal', 'fraction']);
            const firstNumeric = parts.findIndex((part) => numericTypes.has(part.type));
            const lastNumeric = parts.findLastIndex((part) => numericTypes.has(part.type));

            return {
                text: formatter.format(number),
                prefix: parts.slice(0, firstNumeric).map((part) => part.value).join(''),
                number: parts.slice(firstNumeric, lastNumeric + 1).map((part) => part.value).join(''),
                suffix: parts.slice(lastNumeric + 1).map((part) => part.value).join(''),
            };
        };

        const render = (from, to, trend) => {
            const fromNumberWidth = measureWidth(from.number);
            const toNumberWidth = measureWidth(to.number);
            const suffixOffset = fromNumberWidth - toNumberWidth;
            const length = Math.max(from.number.length, to.number.length);
            const fromChars = from.number.padStart(length, ' ').split('');
            const toChars = to.number.padStart(length, ' ').split('');
            const fragment = document.createDocumentFragment();

            if (to.prefix) {
                fragment.append(document.createTextNode(to.prefix));
            }

            fromChars.forEach((fromChar, index) => {
                const toChar = toChars[index];

                if (fromChar === toChar) {
                    fragment.append(document.createTextNode(toChar));
                    return;
                }

                const slot = document.createElement('span');
                const sizer = document.createElement('span');
                const previous = document.createElement('span');
                const next = document.createElement('span');
                const sizeChar = toChar.trim() ? toChar : fromChar;

                slot.style.position = 'relative';
                slot.style.display = 'inline-block';
                slot.style.overflow = 'hidden';
                slot.style.verticalAlign = '-0.04em';

                sizer.textContent = sizeChar || '\u00A0';
                sizer.style.visibility = 'hidden';

                [previous, next].forEach((layer) => {
                    layer.style.position = 'absolute';
                    layer.style.inset = '0';
                    layer.style.display = 'flex';
                    layer.style.alignItems = 'center';
                    layer.style.justifyContent = 'center';
                    layer.style.willChange = 'transform, opacity';
                });

                previous.textContent = fromChar.trim() ? fromChar : '\u00A0';
                next.textContent = toChar.trim() ? toChar : '\u00A0';

                slot.append(sizer, previous, next);
                fragment.append(slot);

                const offset = trend >= 0 ? 110 : -110;

                animate(previous, {
                    y: ['0%', `${-offset}%`],
                    opacity: [1, 0.35],
                }, {
                    duration: 0.46,
                    delay: index * 0.018,
                    ease: editorialEase,
                });

                const nextAnimation = animate(next, {
                    y: [`${offset}%`, '0%'],
                    opacity: [0.35, 1],
                }, {
                    duration: 0.46,
                    delay: index * 0.018,
                    ease: editorialEase,
                });

                animations.push(nextAnimation);
            });

            let suffix = null;

            if (to.suffix) {
                suffix = document.createElement('span');
                suffix.textContent = to.suffix;
                suffix.style.display = 'inline-block';
                suffix.style.willChange = 'transform, opacity';

                fragment.append(suffix);
            }

            element.replaceChildren(fragment);

            if (suffix) {
                const suffixAnimation = animate(suffix, {
                    x: [suffixOffset, 0],
                    opacity: [0.72, 1],
                }, {
                    duration: 0.5,
                    ease: editorialEase,
                });

                animations.push(suffixAnimation);
            }
        };

        element.addEventListener('motion-number-trend', (event) => {
            const fromValue = Number(event.detail?.from);
            const toValue = Number(event.detail?.to);
            const locale = event.detail?.locale || document.documentElement.lang || 'fr-FR';
            const currency = event.detail?.currency || 'EUR';

            if (!Number.isFinite(toValue)) return;

            stop();
            lockHeight();

            const to = format(toValue, locale, currency);

            if (reducedMotion.matches || !Number.isFinite(fromValue) || fromValue === toValue) {
                element.textContent = to.text;
                return;
            }

            const from = format(fromValue, locale, currency);
            const trend = toValue > fromValue ? 1 : -1;

            render(from, to, trend);

            const maxLength = Math.max(from.number.length, to.number.length);
            cleanupTimer = window.setTimeout(() => {
                stop();
                element.textContent = to.text;
            }, 520 + maxLength * 18);
        });
    });
}

export function initCountUp(root = document) {
    root.querySelectorAll('[data-count-up]').forEach((element) => {
        if (!once(element, 'CountUp')) return;

        const raw = element.dataset.countTo ?? element.textContent.trim();
        const target = number(raw.replace(/[^0-9+\-.,]/g, '').replace(',', '.'), 0);
        const decimals = Number.parseInt(element.dataset.countDecimals ?? '0', 10);
        const duration = number(element.dataset.countDuration, 1.5);
        const prefix = element.dataset.countPrefix ?? '';
        const suffix = element.dataset.countSuffix ?? '';
        const repeat = !boolean(element.dataset.countOnce, false);

        const render = (value) => {
            element.textContent = `${prefix}${value.toFixed(decimals)}${suffix}`;
        };

        if (reducedMotion.matches) {
            render(target);

            return;
        }

        render(0);

        inView(element, () => {
            const controls = animate(0, target, {
                duration,
                ease: editorialEase,
                onUpdate: render,
            });

            if (!repeat) return;

            return () => {
                controls.stop();
                render(0);
            };
        }, {
            amount: number(element.dataset.countAmount, 0.6),
        });
    });
}

export function initFilteredReveal(root = document) {
    root.querySelectorAll('[data-filter-reveal]').forEach((container) => {
        if (!once(container, 'FilterReveal')) return;

        const duration = number(container.dataset.filterRevealDuration, 0.8);
        const staggerDelay = number(container.dataset.filterRevealStagger, 0.08);
        const y = number(container.dataset.filterRevealY, 18);
        const blur = number(container.dataset.filterRevealBlur, 8);
        const amount = number(container.dataset.filterRevealAmount, 0.15);
        const perItem = boolean(container.dataset.filterRevealPerItem, false);
        const allItems = [...container.querySelectorAll('[data-filter-reveal-item]')];

        const isVisible = (item) => getComputedStyle(item).display !== 'none' && !item.hidden;

        const isInViewport = (item) => {
            if (!isVisible(item)) return false;

            const rect = item.getBoundingClientRect();

            return rect.bottom > 0
                && rect.top < window.innerHeight
                && rect.right > 0
                && rect.left < window.innerWidth;
        };

        if (reducedMotion.matches) {
            allItems.forEach((item) => resetStyles(item, {
                opacity: '1',
                filter: 'none',
                transform: 'none',
            }));

            return;
        }

        if (perItem) {
            const states = new Map(allItems.map((item) => [
                item,
                {
                    animation: null,
                    transitions: transitionGuard(item),
                },
            ]));

            const resetItem = (item) => {
                const state = states.get(item);

                state.transitions.disable();
                state.animation?.stop();

                animate(item, {
                    opacity: 0,
                    filter: `blur(${blur}px)`,
                    y,
                }, {
                    duration: 0,
                });

                state.transitions.restoreAfter();
            };

            const revealItem = (item, itemDelay = 0) => {
                if (!isVisible(item)) return;

                const state = states.get(item);

                state.transitions.disable();
                state.animation?.stop();

                state.animation = animate(item, {
                    opacity: 1,
                    filter: 'blur(0px)',
                    y: 0,
                }, {
                    duration,
                    delay: itemDelay,
                    ease: editorialEase,
                });

                state.transitions.restoreAfter(itemDelay + duration);
            };

            allItems.forEach((item) => {
                resetItem(item);

                inView(item, () => {
                    if (!isVisible(item)) return;

                    revealItem(item);

                    return () => {
                        resetItem(item);
                    };
                }, { amount });
            });

            const replayVisible = () => {
                requestAnimationFrame(() => requestAnimationFrame(() => {
                    const visibleInViewport = allItems.filter(isInViewport);

                    allItems
                        .filter((item) => isVisible(item) && !visibleInViewport.includes(item))
                        .forEach(resetItem);

                    visibleInViewport.forEach((item, index) => {
                        resetItem(item);
                        revealItem(item, index * staggerDelay);
                    });
                }));
            };

            window.addEventListener('motion-filter-reveal', replayVisible);

            return;
        }

        const transitions = transitionGuard(allItems);
        let isContainerInView = false;

        const visibleItems = () => allItems.filter(isVisible);

        const hide = (items = visibleItems()) => {
            transitions.disable();

            items.forEach((item) => resetStyles(item, {
                opacity: '0',
                filter: `blur(${blur}px)`,
                transform: `translate3d(0, ${y}px, 0)`,
            }));
        };

        const reveal = () => {
            const items = visibleItems();

            if (!items.length) return;

            hide(items);
            transitions.disable();

            animate(
                items,
                {
                    opacity: [0, 1],
                    filter: [`blur(${blur}px)`, 'blur(0px)'],
                    transform: [
                        `translate3d(0, ${y}px, 0)`,
                        'translate3d(0, 0, 0)',
                    ],
                },
                {
                    duration,
                    delay: stagger(staggerDelay),
                    ease: editorialEase,
                },
            );

            transitions.restoreAfter(duration + Math.max(items.length - 1, 0) * staggerDelay);
        };

        inView(container, () => {
            isContainerInView = true;
            requestAnimationFrame(reveal);

            return () => {
                isContainerInView = false;
                hide();
                transitions.restoreAfter();
            };
        }, { amount });

        window.addEventListener('motion-filter-reveal', () => {
            if (!isContainerInView) return;

            requestAnimationFrame(() => requestAnimationFrame(reveal));
        });
    });
}

export function initToggleReveal(root = document) {
    root.querySelectorAll('[data-toggle-reveal]').forEach((container) => {
        if (!once(container, 'ToggleReveal')) return;

        const duration = number(container.dataset.toggleRevealDuration, 0.7);
        const staggerDelay = number(container.dataset.toggleRevealStagger, 0.07);
        const y = number(container.dataset.toggleRevealY, 18);
        const blur = number(container.dataset.toggleRevealBlur, 8);
        const transitions = transitionGuard([...container.querySelectorAll('[data-toggle-reveal-item]')]);

        const items = () => [...container.querySelectorAll('[data-toggle-reveal-item]')];

        const reveal = () => {
            const elements = items();

            if (!elements.length) return;

            if (reducedMotion.matches) {
                elements.forEach((element) => resetStyles(element, {
                    opacity: '1',
                    filter: 'none',
                    transform: 'none',
                }));

                return;
            }

            transitions.disable();

            elements.forEach((element) => resetStyles(element, {
                opacity: '0',
                filter: `blur(${blur}px)`,
                transform: `translate3d(0, ${y}px, 0)`,
            }));

            requestAnimationFrame(() => {
                animate(
                    elements,
                    {
                        opacity: [0, 1],
                        filter: [`blur(${blur}px)`, 'blur(0px)'],
                        transform: [
                            `translate3d(0, ${y}px, 0)`,
                            'translate3d(0, 0, 0)',
                        ],
                    },
                    {
                        duration,
                        delay: stagger(staggerDelay),
                        ease: editorialEase,
                    },
                );

                transitions.restoreAfter(duration + Math.max(elements.length - 1, 0) * staggerDelay);
            });
        };

        container.addEventListener('motion-toggle-reveal', reveal);
    });
}

export function initHorizontalRail(root = document) {
    if (reducedMotion.matches) return;

    root.querySelectorAll('[data-horizontal-rail]').forEach((rail) => {
        if (!once(rail, 'HorizontalRail')) return;

        const track = rail.querySelector('[data-horizontal-rail-track]');

        if (!track) return;

        const speed = number(rail.dataset.railSpeed, 36);
        const direction = rail.dataset.railDirection === 'right' ? 1 : -1;

        const distance = () => Math.max(track.scrollWidth - rail.clientWidth, 0);

        let animation = null;

        const start = () => {
            const max = distance();

            if (!max) return;

            animation?.stop();

            animation = animate(
                track,
                {
                    transform: [
                        'translate3d(0, 0, 0)',
                        `translate3d(${direction * max}px, 0, 0)`,
                    ],
                },
                {
                    duration: Math.max(max / speed, 1),
                    ease: 'linear',
                    repeat: Infinity,
                    repeatType: 'reverse',
                },
            );
        };

        start();

        const observer = new ResizeObserver(start);
        observer.observe(rail);
        observer.observe(track);

        rail.addEventListener('pointerenter', () => animation?.pause());
        rail.addEventListener('pointerleave', () => animation?.play());
        rail.addEventListener('focusin', () => animation?.pause());
        rail.addEventListener('focusout', () => animation?.play());
    });
}

export function initScrollAccordion(root = document) {
    root.querySelectorAll('[data-scroll-accordion]').forEach((accordion) => {
        if (!once(accordion, 'ScrollAccordion')) return;

        const items = [...accordion.querySelectorAll('[data-scroll-accordion-item]')];

        if (!items.length) return;

        let manualUntil = 0;
        let current = null;
        let frame = null;

        const activate = (item) => {
            if (!item || item === current) return;

            current = item;

            accordion.dispatchEvent(new CustomEvent('scroll-accordion-activate', {
                bubbles: true,
                detail: {
                    id: item.dataset.scrollAccordionItem,
                    item,
                },
            }));
        };

        accordion.querySelectorAll('[data-scroll-accordion-trigger]').forEach((trigger) => {
            trigger.addEventListener('click', () => {
                manualUntil = performance.now() + 1800;
            });
        });

        const update = () => {
            frame = null;

            if (performance.now() < manualUntil) return;

            const viewportCenter = window.innerHeight / 2;

            const closest = items
                .map((item) => {
                    const rect = item.getBoundingClientRect();
                    const center = rect.top + rect.height / 2;

                    return {
                        item,
                        distance: Math.abs(center - viewportCenter),
                    };
                })
                .sort((a, b) => a.distance - b.distance)[0]?.item;

            activate(closest);
        };

        const requestUpdate = () => {
            if (frame) return;

            frame = requestAnimationFrame(update);
        };

        window.addEventListener('scroll', requestUpdate, { passive: true });
        window.addEventListener('resize', requestUpdate, { passive: true });

        requestUpdate();
    });
}

export function initHoverText(root = document) {
    root.querySelectorAll('[data-hover-text]').forEach((element) => {
        if (!once(element, 'HoverText')) return;

        const current = element.querySelector('[data-hover-text-current]');
        const next = element.querySelector('[data-hover-text-next]');

        if (!current || !next || reducedMotion.matches) return;

        const enter = () => {
            animate(current, { transform: ['translateY(0%)', 'translateY(-120%)'] }, {
                duration: 0.45,
                ease: editorialEase,
            });

            animate(next, { transform: ['translateY(120%)', 'translateY(0%)'] }, {
                duration: 0.45,
                ease: editorialEase,
            });
        };

        const leave = () => {
            animate(current, { transform: ['translateY(-120%)', 'translateY(0%)'] }, {
                duration: 0.45,
                ease: editorialEase,
            });

            animate(next, { transform: ['translateY(0%)', 'translateY(120%)'] }, {
                duration: 0.45,
                ease: editorialEase,
            });
        };

        element.addEventListener('pointerenter', enter);
        element.addEventListener('pointerleave', leave);
        element.addEventListener('focusin', enter);
        element.addEventListener('focusout', leave);
    });
}

export function initAnimations(root = document) {
    initReveal(root);
    initBlurReveal(root);
    initTitleReveal(root);
    initImageReveal(root);
    initLineReveal(root);
    initOptionTabs(root);
    initNumberTrend(root);
    initCountUp(root);
    initFilteredReveal(root);
    initToggleReveal(root);
    initHorizontalRail(root);
    initScrollAccordion(root);
    initHoverText(root);

    const initScrollEffects = () => {
        initParallax(root);
    };

    if (document.fonts?.ready) {
        document.fonts.ready.then(initScrollEffects);
    } else {
        initScrollEffects();
    }
}
