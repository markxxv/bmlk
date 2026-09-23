import EmblaCarousel from 'embla-carousel';

const initProductGallery = (root) => {
    const viewport = root.querySelector('[data-embla-viewport]');

    if (! viewport) {
        return;
    }

    const thumbs = [...root.querySelectorAll('[data-embla-thumb]')];
    const prevButton = root.querySelector('[data-embla-prev]');
    const nextButton = root.querySelector('[data-embla-next]');

    const embla = EmblaCarousel(viewport, {
        align: 'start',
        containScroll: 'trimSnaps',
        loop: false,
        skipSnaps: false,
    });

    const updateControls = () => {
        const selectedIndex = embla.selectedScrollSnap();

        thumbs.forEach((thumb, index) => {
            const active = index === selectedIndex;

            thumb.classList.toggle('ring-2', active);
            thumb.classList.toggle('ring-[#DDA1AA]', active);
            thumb.classList.toggle('ring-1', ! active);
            thumb.classList.toggle('ring-zinc-200', ! active);
            thumb.setAttribute('aria-current', active ? 'true' : 'false');
        });

        if (prevButton) {
            prevButton.disabled = ! embla.canScrollPrev();
        }

        if (nextButton) {
            nextButton.disabled = ! embla.canScrollNext();
        }
    };

    thumbs.forEach((thumb, index) => {
        thumb.addEventListener('click', () => embla.scrollTo(index));
    });

    prevButton?.addEventListener('click', () => embla.scrollPrev());
    nextButton?.addEventListener('click', () => embla.scrollNext());

    embla.on('select', updateControls);
    embla.on('reInit', updateControls);

    updateControls();
};

document.querySelectorAll('[data-product-gallery]').forEach(initProductGallery);
