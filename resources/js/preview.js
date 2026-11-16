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

    // ------------------------------------------------------ masthead shadow
    if (masthead) {
        const onScroll = () => masthead.classList.toggle('is-scrolled', window.scrollY > 8);
        window.addEventListener('scroll', onScroll, { passive: true });
        onScroll();
    }

    const reduceMotion = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;
    const canObserve = 'IntersectionObserver' in window;

    // ------------------------------------------------------ reveal on scroll
    // Elements are hidden only once this script is running (html.rv), so a failed
    // script never leaves content invisible.
    const reveals = [...document.querySelectorAll('[data-reveal]')];
    if (reveals.length && canObserve && !reduceMotion) {
        const io = new IntersectionObserver((entries) => entries.forEach((entry) => {
            if (!entry.isIntersecting) return;
            entry.target.classList.add('is-in');
            io.unobserve(entry.target);
        }), { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });
        html.classList.add('rv');
        reveals.forEach((el) => io.observe(el));
    }

    // ------------------------------------------------------------- count up
    // <span data-count="1400">1,400</span> — the final value is in the markup already.
    const counters = [...document.querySelectorAll('[data-count]')];
    if (counters.length && canObserve && !reduceMotion) {
        const format = (n) => Math.round(n).toLocaleString('en-US');
        const run = (el) => {
            const target = Number(el.dataset.count);
            if (!Number.isFinite(target) || target <= 0) return;
            const started = performance.now();
            const duration = 1400;
            const step = (now) => {
                const t = Math.min(1, (now - started) / duration);
                el.textContent = format(target * (1 - (1 - t) ** 3));
                if (t < 1) requestAnimationFrame(step);
            };
            requestAnimationFrame(step);
        };
        const io = new IntersectionObserver((entries) => entries.forEach((entry) => {
            if (!entry.isIntersecting) return;
            io.unobserve(entry.target);
            run(entry.target);
        }), { threshold: 0.6 });
        counters.forEach((el) => io.observe(el));
    }

    // -------------------------------------------------------- category filter
    document.querySelectorAll('[data-filter-scope]').forEach((scope) => {
        const buttons = [...scope.querySelectorAll('[data-filter]')];
        const items = [...scope.querySelectorAll('[data-category]')];
        buttons.forEach((button) => button.addEventListener('click', () => {
            const value = button.dataset.filter;
            buttons.forEach((b) => b.setAttribute('aria-pressed', String(b === button)));
            items.forEach((item) => { item.hidden = value !== '*' && item.dataset.category !== value; });
        }));
    });

    // ---------------------------------------------------------------- lightbox
    // <div data-gallery><a href="full.jpg" data-lightbox><img …></a>…</div>
    let box = null;
    let boxLinks = [];
    let boxIndex = 0;
    let boxOpener = null;

    const showSlide = (index) => {
        boxIndex = (index + boxLinks.length) % boxLinks.length;
        const link = boxLinks[boxIndex];
        const img = box.querySelector('.lb__img');
        img.src = link.href;
        img.alt = link.querySelector('img')?.alt ?? '';
        box.querySelector('.lb__count').textContent = `${boxIndex + 1} / ${boxLinks.length}`;
    };

    const closeBox = () => {
        if (!box?.classList.contains('is-open')) return;
        box.classList.remove('is-open');
        html.classList.remove('lb-open');
        boxOpener?.focus();
    };

    const buildBox = () => {
        box = document.createElement('div');
        box.className = 'lb';
        box.setAttribute('role', 'dialog');
        box.setAttribute('aria-modal', 'true');
        box.innerHTML = '<button type="button" class="lb__btn lb__close" data-lb="close" aria-label="Close">&#10005;</button>'
            + '<button type="button" class="lb__btn lb__prev" data-lb="prev" aria-label="Previous">&#8249;</button>'
            + '<img class="lb__img" alt="">'
            + '<button type="button" class="lb__btn lb__next" data-lb="next" aria-label="Next">&#8250;</button>'
            + '<span class="lb__count" aria-live="polite"></span>';
        box.addEventListener('click', (e) => {
            const action = e.target.closest('[data-lb]')?.dataset.lb;
            if (action === 'prev') showSlide(boxIndex - 1);
            else if (action === 'next') showSlide(boxIndex + 1);
            else if (action === 'close' || e.target === box) closeBox();
        });
        document.body.appendChild(box);
    };

    document.addEventListener('click', (e) => {
        const link = e.target.closest?.('a[data-lightbox]');
        if (!link) return;
        e.preventDefault();
        if (!box) buildBox();
        boxLinks = [...(link.closest('[data-gallery]') ?? document).querySelectorAll('a[data-lightbox]')];
        boxOpener = link;
        box.classList.toggle('lb--single', boxLinks.length < 2);
        showSlide(boxLinks.indexOf(link));
        box.classList.add('is-open');
        html.classList.add('lb-open');
        box.querySelector('.lb__close').focus();
    });

    document.addEventListener('keydown', (e) => {
        if (!box?.classList.contains('is-open')) return;
        if (e.key === 'Escape') closeBox();
        else if (e.key === 'ArrowRight') showSlide(boxIndex + 1);
        else if (e.key === 'ArrowLeft') showSlide(boxIndex - 1);
    });

    // ------------------------------------------------------- WhatsApp forms
    // <form data-wa-form data-wa="2010…" data-wa-intro="…" data-track-label="contact_form">
    // Builds the message from the labelled fields and opens the chat. The click goes
    // through a [data-track] link so t.js records it like any other WhatsApp click.
    document.querySelectorAll('form[data-wa-form]').forEach((form) => form.addEventListener('submit', (e) => {
        const digits = (form.dataset.wa || '').replace(/\D+/g, '');
        if (!digits) return;
        e.preventDefault();

        const lines = form.dataset.waIntro ? [form.dataset.waIntro] : [];
        form.querySelectorAll('[data-wa-field]').forEach((field) => {
            const value = field.value.trim();
            if (value) lines.push(`${field.dataset.waField}: ${value}`);
        });

        const link = document.createElement('a');
        link.href = `https://wa.me/${digits}?text=${encodeURIComponent(lines.join('\n'))}`;
        link.target = '_blank';
        link.rel = 'noopener';
        link.dataset.track = 'whatsapp';
        link.dataset.trackLabel = form.dataset.trackLabel || 'form';
        link.hidden = true;
        form.appendChild(link);
        link.click();
        link.remove();
    }));
})();
