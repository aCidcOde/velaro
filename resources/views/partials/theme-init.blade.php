{{--
[resources/views/partials]
@Author: André Gomes ( @acidcode )
@since 2026-02-22
Inicializacao unica de tema (dark/light) para evitar flicker e conflitos entre layouts.
--}}
<script>
    (() => {
        const normalizeTheme = (theme) => {
            return theme === 'dark' || theme === 'light' ? theme : null;
        };

        const syncThemeCookie = (theme) => {
            document.cookie = `theme=${theme}; path=/; max-age=31536000; SameSite=Lax`;
        };

        const prefersDark = () => {
            return window.matchMedia('(prefers-color-scheme: dark)').matches;
        };

        const resolveTheme = () => {
            const storedTheme = normalizeTheme(window.localStorage.getItem('theme'));

            if (storedTheme !== null) {
                return storedTheme;
            }

            return prefersDark() ? 'dark' : 'light';
        };

        const applyTheme = (theme, persist = false) => {
            const resolvedTheme = theme === 'dark' ? 'dark' : 'light';
            const htmlElement = document.documentElement;

            htmlElement.classList.toggle('dark', resolvedTheme === 'dark');
            htmlElement.setAttribute('data-bs-theme', resolvedTheme);
            window.localStorage.setItem('flux.appearance', resolvedTheme);
            syncThemeCookie(resolvedTheme);

            if (persist) {
                window.localStorage.setItem('theme', resolvedTheme);
            }

            return resolvedTheme;
        };

        const enforceResolvedTheme = () => {
            applyTheme(resolveTheme(), false);
        };

        window.resolveTheme = resolveTheme;
        window.applyTheme = applyTheme;
        window.toggleTheme = (theme) => {
            applyTheme(theme, true);
        };

        enforceResolvedTheme();

        if (!window.__themeNavigationEventsBound) {
            document.addEventListener('livewire:navigate', enforceResolvedTheme);
            document.addEventListener('livewire:navigating', enforceResolvedTheme);
            document.addEventListener('livewire:navigated', enforceResolvedTheme);
            window.__themeNavigationEventsBound = true;
        }

        if (!window.__themeMutationObserverBound) {
            const observer = new MutationObserver(() => {
                const resolvedTheme = resolveTheme();
                const htmlElement = document.documentElement;
                const isDark = htmlElement.classList.contains('dark');
                const dataTheme = htmlElement.getAttribute('data-bs-theme');
                const isConsistent = resolvedTheme === 'dark'
                    ? isDark && dataTheme === 'dark'
                    : !isDark && dataTheme === 'light';

                if (!isConsistent) {
                    applyTheme(resolvedTheme, false);
                }
            });

            observer.observe(document.documentElement, {
                attributes: true,
                attributeFilter: ['class', 'data-bs-theme'],
            });

            window.__themeMutationObserverBound = true;
        }
    })();
</script>
