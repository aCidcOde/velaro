

document.addEventListener('DOMContentLoaded', () => {
    const normalizeTheme = (theme) => {
        return theme === 'dark' || theme === 'light' ? theme : null;
    };

    const syncThemeCookie = (theme) => {
        document.cookie = `theme=${theme}; path=/; max-age=31536000; SameSite=Lax`;
    };

    const resolveTheme = () => {
        if (typeof window.resolveTheme === 'function') {
            return window.resolveTheme();
        }

        const storedTheme = normalizeTheme(window.localStorage.getItem('theme'));

        if (storedTheme !== null) {
            return storedTheme;
        }

        return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    };

    const applyTheme = (theme, persist = false) => {
        if (typeof window.applyTheme === 'function') {
            return window.applyTheme(theme, persist);
        }

        const resolvedTheme = theme === 'dark' ? 'dark' : 'light';

        document.documentElement.classList.toggle('dark', resolvedTheme === 'dark');
        document.documentElement.setAttribute('data-bs-theme', resolvedTheme);
        window.localStorage.setItem('flux.appearance', resolvedTheme);
        syncThemeCookie(resolvedTheme);

        if (persist) {
            window.localStorage.setItem('theme', resolvedTheme);
        }

        return resolvedTheme;
    };

    applyTheme(resolveTheme(), false);

    const themeToggleButtons = document.querySelectorAll('[data-theme-toggle]');
    themeToggleButtons.forEach((button) => {
        button.addEventListener('click', (event) => {
            event.preventDefault();
            const theme = button.getAttribute('data-theme-toggle');
            if (!theme) {
                return;
            }

            applyTheme(theme, true);
        });
    });

    const reapplyThemeAfterNavigation = () => {
        applyTheme(resolveTheme(), false);
    };

    document.addEventListener('livewire:navigate', reapplyThemeAfterNavigation);
    document.addEventListener('livewire:navigating', reapplyThemeAfterNavigation);
    document.addEventListener('livewire:navigated', reapplyThemeAfterNavigation);

    const formatPhone = (digits) => {
        if (!digits) {
            return '';
        }

        const area = digits.slice(0, 2);
        const rest = digits.slice(2);

        if (!rest.length) {
            return `(${area}`;
        }

        if (rest.length <= 4) {
            return `(${area}) ${rest}`;
        }

        if (rest.length <= 8) {
            return `(${area}) ${rest.slice(0, 4)}-${rest.slice(4)}`;
        }

        return `(${area}) ${rest.slice(0, 5)}-${rest.slice(5, 9)}`;
    };

    const formatCpf = (digits) => {
        if (!digits) {
            return '';
        }

        if (digits.length <= 3) {
            return digits;
        }

        if (digits.length <= 6) {
            return `${digits.slice(0, 3)}.${digits.slice(3)}`;
        }

        if (digits.length <= 9) {
            return `${digits.slice(0, 3)}.${digits.slice(3, 6)}.${digits.slice(6)}`;
        }

        return `${digits.slice(0, 3)}.${digits.slice(3, 6)}.${digits.slice(6, 9)}-${digits.slice(9, 11)}`;
    };

    const formatCnpj = (digits) => {
        if (!digits) {
            return '';
        }

        if (digits.length <= 2) {
            return digits;
        }

        if (digits.length <= 5) {
            return `${digits.slice(0, 2)}.${digits.slice(2)}`;
        }

        if (digits.length <= 8) {
            return `${digits.slice(0, 2)}.${digits.slice(2, 5)}.${digits.slice(5)}`;
        }

        if (digits.length <= 12) {
            return `${digits.slice(0, 2)}.${digits.slice(2, 5)}.${digits.slice(5, 8)}/${digits.slice(8)}`;
        }

        return `${digits.slice(0, 2)}.${digits.slice(2, 5)}.${digits.slice(5, 8)}/${digits.slice(8, 12)}-${digits.slice(12, 14)}`;
    };

    const formatCpfCnpj = (digits) => {
        if (!digits) {
            return '';
        }

        return digits.length <= 11 ? formatCpf(digits) : formatCnpj(digits);
    };

    const formatBrlMoney = (digits) => {
        if (!digits) {
            return '';
        }

        const normalizedDigits = digits.padStart(3, '0');
        const integerDigits = normalizedDigits.slice(0, -2);
        const decimalDigits = normalizedDigits.slice(-2);
        const integerValue = Number.parseInt(integerDigits, 10);

        return `${integerValue.toLocaleString('pt-BR')},${decimalDigits}`;
    };

    const maskConfigs = {
        phone: {
            normalize: (value) => value.replace(/\D/g, '').slice(0, 11),
            formatter: formatPhone,
        },
        cpf: {
            normalize: (value) => value.replace(/\D/g, '').slice(0, 11),
            formatter: formatCpf,
        },
        cnpj: {
            normalize: (value) => value.replace(/\D/g, '').slice(0, 14),
            formatter: formatCnpj,
        },
        cpf_cnpj: {
            normalize: (value) => value.replace(/\D/g, '').slice(0, 14),
            formatter: formatCpfCnpj,
        },
        brl_money: {
            normalize: (value) => value.replace(/\D/g, ''),
            formatter: formatBrlMoney,
        },
    };

    const applyMask = (input) => {
        const maskType = input.getAttribute('data-mask');
        const config = maskConfigs[maskType];

        if (!config) {
            return;
        }

        input.value = config.formatter(config.normalize(input.value));
    };

    const applyMasks = () => {
        document.querySelectorAll('input[data-mask]').forEach((input) => {
            applyMask(input);
        });
    };

    document.addEventListener('input', (event) => {
        const target = event.target;

        if (!(target instanceof HTMLInputElement)) {
            return;
        }

        if (!target.hasAttribute('data-mask')) {
            return;
        }

        applyMask(target);
    });

    applyMasks();
    document.addEventListener('livewire:morphed', applyMasks);
});

if (!window.registerOrderErrorModalListener) {
    window.registerOrderErrorModalListener = () => {
        if (window.orderErrorModalListenerRegistered || !window.Livewire) {
            return;
        }

        window.orderErrorModalListenerRegistered = true;

        window.Livewire.on('order-error-modal', (payload) => {
            const modalElement = window.orderModalUtils?.resolveModalElementById('order-error-modal')
                ?? document.getElementById('order-error-modal');
            const messageElement = modalElement?.querySelector('#order-error-modal-message');

            if (!modalElement || !messageElement) {
                return;
            }

            const detail = Array.isArray(payload) ? payload[0] : payload;
            const message = typeof detail?.message === 'string' && detail.message.trim() !== ''
                ? detail.message.trim()
                : 'Verifique os dados informados e tente novamente.';

            messageElement.textContent = message;

            if (window.orderModalUtils?.showModal(modalElement)) {
                return;
            }

            window.alert(message);
        });
    };
}

window.registerOrderErrorModalListener();
document.addEventListener('livewire:init', window.registerOrderErrorModalListener);
document.addEventListener('livewire:navigated', window.registerOrderErrorModalListener);

if (!window.ibgeLocationSelector) {
    window.ibgeLocationSelector = function ibgeLocationSelector(stateRef, cityRef) {
        return {
            ufs: [],
            cities: [],
            state: stateRef,
            city: cityRef,
            loadingUfs: false,
            loadingCities: false,
            async init() {
                this.$watch('state', async () => {
                    await this.fetchCities();
                });
                await this.fetchUfs();
                if (this.state) {
                    await this.fetchCities();
                }
            },
            async fetchUfs() {
                this.loadingUfs = true;
                try {
                    const response = await fetch('https://servicodados.ibge.gov.br/api/v1/localidades/estados');
                    const data = await response.json();
                    this.ufs = data
                        .map((uf) => ({
                            code: uf.sigla,
                            name: uf.nome,
                        }))
                        .sort((a, b) => a.name.localeCompare(b.name));
                } catch (error) {
                    this.ufs = [];
                } finally {
                    this.loadingUfs = false;
                }
            },
            async fetchCities() {
                if (!this.state) {
                    this.cities = [];
                    this.city = '';
                    return;
                }
                this.loadingCities = true;
                try {
                    const response = await fetch(
                        `https://servicodados.ibge.gov.br/api/v1/localidades/estados/${this.state}/municipios`,
                    );
                    const data = await response.json();
                    this.cities = data.map((city) => city.nome).sort((a, b) => a.localeCompare(b));

                    if (this.city && !this.cities.includes(this.city)) {
                        this.city = '';
                    }
                } catch (error) {
                    this.cities = [];
                } finally {
                    this.loadingCities = false;
                }
            },
        };
    };
}
