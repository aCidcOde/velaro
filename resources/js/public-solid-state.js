const toggleSolidStateMenu = (shouldShow) => {
    document.body.classList.toggle('is-menu-visible', shouldShow);
};

window.addEventListener('load', () => {
    window.setTimeout(() => {
        document.body.classList.remove('is-preload');
    }, 100);
});

document.addEventListener('DOMContentLoaded', () => {
    const menu = document.getElementById('menu');
    const header = document.getElementById('header');
    const banner = document.getElementById('banner');

    document.addEventListener('click', (event) => {
        const toggle = event.target.closest('[data-menu-toggle]');
        const close = event.target.closest('[data-menu-close]');
        const linkInsideMenu = event.target.closest('#menu a');
        const insideInner = event.target.closest('#menu .inner');

        if (toggle) {
            event.preventDefault();
            toggleSolidStateMenu(!document.body.classList.contains('is-menu-visible'));
            return;
        }

        if (close) {
            event.preventDefault();
            toggleSolidStateMenu(false);
            return;
        }

        if (linkInsideMenu) {
            toggleSolidStateMenu(false);
            return;
        }

        if (menu && document.body.classList.contains('is-menu-visible') && !insideInner) {
            toggleSolidStateMenu(false);
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            toggleSolidStateMenu(false);
        }
    });

    if (header && banner && header.classList.contains('alt') && 'IntersectionObserver' in window) {
        const observer = new IntersectionObserver(
            ([entry]) => {
                header.classList.toggle('alt', entry.isIntersecting);
            },
            {
                rootMargin: `-${header.offsetHeight}px 0px 0px 0px`,
                threshold: 0.05,
            },
        );

        observer.observe(banner);
    }
});
