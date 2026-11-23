const prefersReducedMotion = () =>
    window.matchMedia('(prefers-reduced-motion: reduce)').matches;

const scrollByItem = (track, direction) => {
    const item = track.querySelector('.carousel-item');
    const gap = 12;
    const step = item
        ? item.getBoundingClientRect().width + gap
        : Math.max(240, track.clientWidth * 0.8);

    track.scrollBy({
        left: step * direction,
        behavior: prefersReducedMotion() ? 'auto' : 'smooth',
    });
};

export const initCarousels = (root = document) => {
    root.querySelectorAll('[data-carousel]').forEach((carousel) => {
        const track = carousel.querySelector('[data-carousel-track]');
        const previous = carousel.querySelector('[data-carousel-prev]');
        const next = carousel.querySelector('[data-carousel-next]');

        if (!track || !previous || !next) {
            return;
        }

        const updateButtons = () => {
            const maxScroll = track.scrollWidth - track.clientWidth;
            previous.disabled = track.scrollLeft <= 2;
            next.disabled = track.scrollLeft >= maxScroll - 2;
        };

        previous.addEventListener('click', () => scrollByItem(track, -1));
        next.addEventListener('click', () => scrollByItem(track, 1));
        track.addEventListener('scroll', updateButtons, { passive: true });
        window.addEventListener('resize', updateButtons);

        updateButtons();
    });
};
