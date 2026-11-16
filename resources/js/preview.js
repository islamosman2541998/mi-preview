/*
 * Shared preview behaviour (all templates): preloader, demo bar, mobile nav,
 * dropdowns. Tracking lives separately in public/js/t.js.
 */
(() => {
    const html = document.documentElement;
    const store = (() => { try { return window.sessionStorage; } catch { return null; } })();
    const remember = (key) => { try { store?.setItem(key, '1'); } catch { /* private mode */ } };

    // ----------------------------------------------------------- preloader
    // Shown once per browser session; hides on load or after 2.5s at most.
    const ready = () => requestAnimationFrame(() => requestAnimationFrame(() => html.classList.add('is-ready')));
    const preloader = document.querySelector('[data-preloader]');

    if (preloader && !html.classList.contains('pl-seen')) {
        let done = false;
        const hide = () => {
            if (done) return;
            done = true;
            remember('mi_pl');
            preloader.classList.add('is-hidden');
            setTimeout(() => preloader.remove(), 450);
            ready();
        };
        if (document.readyState === 'complete') hide();
        else window.addEventListener('load', hide, { once: true });
        setTimeout(hide, 2500);
    } else {
        ready();
    }

    // ------------------------------------------------------------ demo bar
    document.querySelector('[data-demo-bar-close]')?.addEventListener('click', () => {
        html.classList.add('db-hidden');
        remember('mi_db');
    });

    // ---------------------------------------------------------- mobile nav
    const masthead = document.querySelector('[data-masthead]');
    const toggle = document.querySelector('[data-nav-toggle]');

    const setNav = (open) => {
        masthead?.classList.toggle('nav-open', open);
        toggle?.setAttribute('aria-expanded', String(open));
    };

    toggle?.addEventListener('click', () => setNav(!masthead.classList.contains('nav-open')));
    masthead?.querySelectorAll('.nav a').forEach((a) => a.addEventListener('click', () => setNav(false)));

    // ----------------------------------------------------------- dropdowns
    const subs = [...document.querySelectorAll('[data-sub-toggle]')];
    const closeSubs = (except) => subs.forEach((b) => {
        if (b === except) return;
        b.closest('.has-sub')?.classList.remove('is-open');
        b.setAttribute('aria-expanded', 'false');
    });

    subs.forEach((button) => button.addEventListener('click', () => {
        const item = button.closest('.has-sub');
        const open = !item.classList.contains('is-open');
        closeSubs(button);
        item.classList.toggle('is-open', open);
        button.setAttribute('aria-expanded', String(open));
    }));

    document.addEventListener('click', (e) => {
        if (!e.target.closest('.has-sub')) closeSubs();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key !== 'Escape') return;
        closeSubs();
        if (masthead?.classList.contains('nav-open')) {
            setNav(false);
            toggle?.focus();
        }
    });
})();
