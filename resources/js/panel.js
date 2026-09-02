import * as bootstrap from 'bootstrap';

window.bootstrap = bootstrap;

const closePanelSidebar = () => {
    document.querySelector('[data-panel-shell]')?.classList.remove('is-sidebar-open');
};

const closePanelPopovers = () => {
    document.querySelectorAll('[data-panel-popover].is-open').forEach((element) => {
        element.classList.remove('is-open');
    });
};

const togglePanelPopover = (targetName) => {
    const target = document.querySelector(`[data-panel-popover="${targetName}"]`);

    if (!target) {
        return;
    }

    const shouldOpen = !target.classList.contains('is-open');
    closePanelPopovers();

    if (shouldOpen) {
        target.classList.add('is-open');
    }
};

const togglePanelGroup = (trigger) => {
    const item = trigger.closest('[data-panel-group]');
    const panel = item?.querySelector('[data-panel-group-panel]');

    if (!item || !panel) {
        return;
    }

    const isOpen = item.classList.toggle('is-open');
    panel.classList.toggle('hidden', !isOpen);
    trigger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
};

const focusPanelSearch = () => {
    document.querySelector('[data-panel-search-input]')?.focus();
};

document.addEventListener('click', (event) => {
    const sidebarToggle = event.target.closest('[data-panel-sidebar-toggle]');
    const overlay = event.target.closest('[data-panel-overlay]');
    const groupToggle = event.target.closest('[data-panel-group-toggle]');
    const popoverToggle = event.target.closest('[data-panel-popover-toggle]');
    const themeSwitch = event.target.closest('[data-panel-theme-switch]');
    const insidePopoverRoot = event.target.closest('[data-panel-popover-root]');
    const navigationLink = event.target.closest('.panel-subnav-link, .panel-nav-link[href]');

    if (sidebarToggle) {
        document.querySelector('[data-panel-shell]')?.classList.toggle('is-sidebar-open');
        return;
    }

    if (overlay) {
        closePanelSidebar();
        return;
    }

    if (groupToggle) {
        togglePanelGroup(groupToggle);
        return;
    }

    if (popoverToggle) {
        event.preventDefault();
        togglePanelPopover(popoverToggle.getAttribute('data-panel-popover-toggle'));
        return;
    }

    if (themeSwitch) {
        event.preventDefault();

        if (typeof window.toggleTheme === 'function' && typeof window.resolveTheme === 'function') {
            window.toggleTheme(window.resolveTheme() === 'dark' ? 'light' : 'dark');
        }

        return;
    }

    if (navigationLink && window.innerWidth < 1024) {
        closePanelSidebar();
    }

    if (!insidePopoverRoot) {
        closePanelPopovers();
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        closePanelSidebar();
        closePanelPopovers();
    }

    if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
        event.preventDefault();
        focusPanelSearch();
    }

    if (event.key === '/' && document.activeElement !== document.querySelector('[data-panel-search-input]')) {
        event.preventDefault();
        focusPanelSearch();
    }
});

if (!window.orderModalUtils) {
    window.orderModalUtils = {
        resolveModalElementById(modalId) {
            if (typeof modalId !== 'string' || modalId.trim() === '') {
                return null;
            }

            const modalCandidates = Array.from(document.querySelectorAll(`[id="${modalId}"]`));

            if (modalCandidates.length === 0) {
                return null;
            }

            const modalElement = modalCandidates.find((candidate) => candidate.parentElement === document.body)
                ?? modalCandidates[modalCandidates.length - 1];

            if (modalElement.parentElement !== document.body) {
                document.body.appendChild(modalElement);
            }

            modalCandidates.forEach((candidate) => {
                if (candidate !== modalElement) {
                    candidate.remove();
                }
            });

            return modalElement;
        },
        cleanupModalSurface(modalElement) {
            if (!modalElement) {
                return;
            }

            modalElement.style.removeProperty('z-index');
            modalElement.style.removeProperty('position');
            modalElement.style.removeProperty('inset');
            modalElement.style.removeProperty('overflow-y');
            document.querySelectorAll('.modal-backdrop.fallback-backdrop').forEach((backdrop) => backdrop.remove());
        },
        showModal(modalElement) {
            if (!modalElement) {
                return false;
            }

            this.cleanupModalSurface(modalElement);

            const bootstrapModal = window.bootstrap?.Modal;

            if (!bootstrapModal) {
                return false;
            }

            bootstrapModal.getOrCreateInstance(modalElement).show();

            return true;
        },
    };
}
