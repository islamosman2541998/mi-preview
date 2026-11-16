/* MI Preview view tracking — no dependencies. Everything is JS-initiated, so
   link-preview bots (WhatsApp, Facebook…) that don't run JS never create visits. */
(function (w, d) {
    'use strict';
    if (!w.fetch || !w.JSON) return;

    var API = '/_t/', KEY = 'mi_vid', nav = w.navigator;
    var token = null, every = 15, tick = 0, active = 0, depth = 0, sentDepth = 0, starting = false;
    var dwell = {}, seen = {};

    function uuid() {
        if (w.crypto && crypto.randomUUID) return crypto.randomUUID();
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
            var r = Math.random() * 16 | 0;
            return (c === 'x' ? r : (r & 3 | 8)).toString(16);
        });
    }

    function visitor() {
        var v;
        try {
            v = localStorage.getItem(KEY);
            if (!v) { v = uuid(); localStorage.setItem(KEY, v); }
            return v;
        } catch (e) { /* storage blocked → cookie fallback */ }
        var m = d.cookie.match(/(?:^|; )mi_vid=([\w-]+)/);
        if (m) return m[1];
        v = uuid();
        d.cookie = KEY + '=' + v + ';path=/;max-age=31536000;SameSite=Lax';
        return v;
    }

    function send(path, data, beacon) {
        var body = JSON.stringify(data);
        if (beacon && nav.sendBeacon) {
            try { if (nav.sendBeacon(API + path, new Blob([body], { type: 'application/json' }))) return null; } catch (e) {}
        }
        return fetch(API + path, {
            method: 'POST', body: body, keepalive: true, credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' }
        }).catch(function () {});
    }

    function start() {
        if (starting) return;
        starting = true;
        var p = send('start', { uuid: visitor(), path: location.pathname, referrer: d.referrer || null });
        p && p.then(function (r) { return r && r.ok ? r.json() : null; }).then(function (j) {
            starting = false;
            if (j && j.track) { token = j.token; every = j.interval || 15; }
        }, function () { starting = false; });
    }

    function payload() {
        var data = { token: token, active: active, sections: dwell, path: location.pathname };
        if (depth > sentDepth) { data.depth = depth; sentDepth = depth; }
        active = 0; dwell = {};
        return data;
    }

    function ping() {
        if (!token) return;
        var p = send('ping', payload());
        p && p.then(function (r) {
            if (r && r.status === 410) { token = null; start(); } // visit closed → new/resumed visit
        });
    }

    function flush() {
        if (token && (active || Object.keys(dwell).length || depth > sentDepth)) send('end', payload(), true);
    }

    // one-second clock: counts only while the tab is visible
    setInterval(function () {
        if (d.visibilityState !== 'visible') return;
        active++;
        for (var k in seen) if (seen[k]) dwell[k] = (dwell[k] || 0) + 1;
        if (++tick >= every) { tick = 0; ping(); }
    }, 1000);

    // section dwell: visible when half of it is on screen, or it fills half the screen
    if ('IntersectionObserver' in w) {
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (e) {
                var key = e.target.getAttribute('data-section');
                seen[key] = e.isIntersecting &&
                    (e.intersectionRatio >= 0.5 || e.intersectionRect.height >= w.innerHeight * 0.5);
            });
        }, { threshold: [0, 0.25, 0.5, 0.75, 1] });
        d.querySelectorAll('[data-section]').forEach(function (el) { io.observe(el); });
    }

    // scroll depth milestones: 25 / 50 / 75 / 100
    var raf = 0;
    w.addEventListener('scroll', function () {
        if (raf) return;
        raf = requestAnimationFrame(function () {
            raf = 0;
            var h = d.documentElement.scrollHeight - w.innerHeight;
            var pct = h > 0 ? (w.scrollY / h) * 100 : 100;
            var m = pct >= 98 ? 100 : pct >= 75 ? 75 : pct >= 50 ? 50 : pct >= 25 ? 25 : 0;
            if (m > depth) depth = m;
        });
    }, { passive: true });

    // high-intent clicks
    d.addEventListener('click', function (e) {
        var el = e.target.closest && e.target.closest('[data-track]');
        if (!el || !token) return;
        send('event', {
            token: token,
            type: el.getAttribute('data-track'),
            label: el.getAttribute('data-track-label') || null,
            path: location.pathname
        }, true);
    }, true);

    d.addEventListener('visibilitychange', function () {
        if (d.visibilityState === 'hidden') flush();
        else if (!token) start();
    });
    w.addEventListener('pagehide', flush);

    start();
})(window, document);
