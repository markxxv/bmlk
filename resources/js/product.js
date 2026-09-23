import EmblaCarousel from 'embla-carousel';

const initProductGallery = (root) => {
    const viewport = root.querySelector('[data-embla-viewport]');

    if (! viewport) {
        return;
    }

    const thumbs = [...root.querySelectorAll('[data-embla-thumb]')];

    const embla = EmblaCarousel(viewport, {
        align: 'start',
        containScroll: 'trimSnaps',
        loop: false,
        skipSnaps: false,
    });

    const selectThumb = () => {
        const selectedIndex = embla.selectedScrollSnap();

        thumbs.forEach((thumb, index) => {
            const active = index === selectedIndex;

            thumb.classList.toggle('ring-2', active);
            thumb.classList.toggle('ring-[#DDA1AA]', active);
            thumb.classList.toggle('ring-1', ! active);
            thumb.classList.toggle('ring-zinc-200', ! active);
            thumb.setAttribute('aria-current', active ? 'true' : 'false');
        });
    };

    thumbs.forEach((thumb, index) => {
        thumb.addEventListener('click', () => embla.scrollTo(index));
    });

    embla.on('select', selectThumb);
    embla.on('reInit', selectThumb);

    selectThumb();
};

document.querySelectorAll('[data-product-gallery]').forEach(initProductGallery);
