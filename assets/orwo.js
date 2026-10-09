/* ORWO Family theme interactions. All content is visible without JavaScript; motion respects reduced-motion. */
(() => {
  const d = document;
  const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  // Parallax only on larger screens: phones scroll smoother without it.
  const wide = window.matchMedia('(min-width: 768px)');
  const header = d.querySelector('[data-header]');
  const bar = d.querySelector('[data-progress]');
  const adminBar = d.getElementById('wpadminbar');

  // Header: solid once scrolled, sits under the WordPress admin bar while it is visible, scroll progress bar.
  let ticking = false;
  const parallax = [...d.querySelectorAll('[data-speed]')];
  const heroPhoto = d.querySelector('.hero-photo');
  const line = d.querySelector('[data-line]');
  const timeline = d.querySelector('[data-timeline]');
  const frame = () => {
    ticking = false;
    const y = window.scrollY;
    const vh = window.innerHeight;
    if (header) {
      header.classList.toggle('scrolled', y > 24);
      header.style.top = adminBar ? Math.max(0, adminBar.getBoundingClientRect().bottom) + 'px' : '';
    }
    if (bar) {
      const max = d.documentElement.scrollHeight - vh;
      bar.style.transform = 'scaleX(' + (max > 0 ? Math.min(1, y / max) : 0) + ')';
    }
    if (reduce || !wide.matches) return;
    if (heroPhoto && y < vh * 1.2) heroPhoto.style.translate = '0 ' + (y * 0.28).toFixed(1) + 'px';
    parallax.forEach((el) => {
      const r = el.getBoundingClientRect();
      if (r.bottom < -200 || r.top > vh + 200) return;
      const offset = (r.top + r.height / 2 - vh / 2) * parseFloat(el.dataset.speed || 0);
      el.style.translate = '0 ' + offset.toFixed(1) + 'px';
    });
    if (line && timeline) {
      const r = timeline.getBoundingClientRect();
      const p = Math.min(1, Math.max(0, (vh * 0.6 - r.top) / r.height));
      line.style.transform = 'scaleY(' + p.toFixed(3) + ')';
    }
  };
  const onScroll = () => { if (!ticking) { ticking = true; window.requestAnimationFrame(frame); } };
  window.addEventListener('scroll', onScroll, { passive: true });
  window.addEventListener('resize', onScroll, { passive: true });
  frame();

  // Mobile menu (full-screen panel with staggered links).
  const toggle = d.querySelector('.menu-toggle');
  const nav = d.getElementById('navigation');
  const closeMenu = () => { d.body.classList.remove('menu-open'); if (toggle) toggle.setAttribute('aria-expanded', 'false'); };
  if (nav) [...nav.querySelectorAll('.menu > li')].forEach((li, i) => li.style.setProperty('--i', String(i)));
  if (toggle) toggle.addEventListener('click', () => {
    if (nav && header) nav.style.top = Math.max(0, header.getBoundingClientRect().bottom) + 'px';
    const open = d.body.classList.toggle('menu-open');
    toggle.setAttribute('aria-expanded', String(open));
  });
  if (nav) nav.addEventListener('click', (e) => { if (e.target.closest('a')) closeMenu(); });
  d.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeMenu(); });

  // Scrollspy: highlight the menu item of the section in view (landing page only).
  const spyLinks = [...d.querySelectorAll('[data-spy]')];
  const spySections = spyLinks.map((a) => d.getElementById(a.dataset.spy)).filter(Boolean);
  if (spySections.length && 'IntersectionObserver' in window) {
    const spy = new IntersectionObserver((entries) => entries.forEach((entry) => {
      if (!entry.isIntersecting) return;
      spyLinks.forEach((a) => a.classList.toggle('active', a.dataset.spy === entry.target.id));
    }), { rootMargin: '-45% 0px -50% 0px' });
    spySections.forEach((s) => spy.observe(s));
  }

  // Word-by-word reveal for the big quotes.
  d.querySelectorAll('.quote-band blockquote, .jungle blockquote, .pull').forEach((el) => {
    let n = 0;
    const walker = d.createTreeWalker(el, NodeFilter.SHOW_TEXT);
    const nodes = [];
    while (walker.nextNode()) nodes.push(walker.currentNode);
    nodes.forEach((node) => {
      const frag = d.createDocumentFragment();
      node.textContent.split(/(\s+)/).forEach((part) => {
        if (!part) return;
        if (/^\s+$/.test(part)) { frag.appendChild(d.createTextNode(part)); return; }
        const span = d.createElement('span');
        span.className = 'word';
        span.style.setProperty('--w', String(n++));
        span.textContent = part;
        frag.appendChild(span);
      });
      node.replaceWith(frag);
    });
    el.dataset.words = '';
  });

  // Count-up numbers.
  const countUp = (el) => {
    const target = parseInt(el.dataset.count, 10);
    const suffix = el.dataset.suffix || '';
    if (reduce || !target) { el.textContent = target.toLocaleString('en-US') + suffix; return; }
    const start = performance.now();
    const from = target >= 1900 && target < 2100 ? 1800 : 0;
    const step = (now) => {
      const t = Math.min(1, (now - start) / 2000);
      const eased = 1 - Math.pow(2, -10 * t);
      const value = Math.round(from + (target - from) * (t === 1 ? 1 : eased));
      el.textContent = (from ? String(value) : value.toLocaleString('en-US')) + suffix;
      if (t < 1) window.requestAnimationFrame(step);
    };
    window.requestAnimationFrame(step);
  };

  // Reveal on scroll (staggered siblings), plus counters and word reveals.
  const items = [...d.querySelectorAll('[data-reveal], [data-words], .count')];
  if (!reduce && 'IntersectionObserver' in window) {
    d.querySelectorAll('.count').forEach((el) => {
      const target = parseInt(el.dataset.count, 10);
      el.textContent = (target >= 1900 && target < 2100 ? '1800' : '0') + (el.dataset.suffix || '');
    });
  }
  const show = (el) => {
    if (el.hasAttribute('data-reveal')) el.classList.add('in');
    if (el.hasAttribute('data-words')) el.classList.add('words-in');
    if (el.classList.contains('count') && !el.dataset.done) { el.dataset.done = '1'; countUp(el); }
  };
  if (reduce || !('IntersectionObserver' in window)) {
    items.forEach(show);
  } else {
    const io = new IntersectionObserver((entries) => entries.forEach((entry) => {
      if (entry.isIntersecting) { show(entry.target); io.unobserve(entry.target); }
    }), { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });
    items.forEach((el) => {
      if (el.hasAttribute('data-reveal') && !el.style.getPropertyValue('--d')) {
        const siblings = [...el.parentElement.children].filter((c) => c.hasAttribute('data-reveal'));
        const index = siblings.indexOf(el);
        if (index > 0) el.style.setProperty('--d', String(Math.min(index, 6)));
      }
      io.observe(el);
    });
    // Safety net for fast jumps (menu anchors, End key): anything already scrolled past is shown.
    let pending = items.slice();
    let sweeping = false;
    const sweep = () => {
      sweeping = false;
      const limit = window.innerHeight * 0.92;
      pending = pending.filter((el) => {
        if (el.getBoundingClientRect().top < limit) { show(el); io.unobserve(el); return false; }
        return true;
      });
      if (!pending.length) window.removeEventListener('scroll', onSweep);
    };
    const onSweep = () => { if (!sweeping) { sweeping = true; window.setTimeout(sweep, 160); } };
    window.addEventListener('scroll', onSweep, { passive: true });
    window.addEventListener('load', sweep);
  }
  window.addEventListener('beforeprint', () => items.forEach(show));

  // Gentle 3D tilt on the family-of-companies card (mouse only).
  const card = d.querySelector('.family-card');
  if (card && !reduce && window.matchMedia('(pointer: fine)').matches) {
    card.addEventListener('pointermove', (e) => {
      const r = card.getBoundingClientRect();
      const x = (e.clientX - r.left) / r.width - 0.5;
      const y = (e.clientY - r.top) / r.height - 0.5;
      card.style.transform = 'perspective(1000px) rotateY(' + (x * 8).toFixed(2) + 'deg) rotateX(' + (-y * 8).toFixed(2) + 'deg)';
    });
    card.addEventListener('pointerleave', () => { card.style.transform = ''; });
  }

  // Gallery lightbox (film strip duplicates open the matching photo).
  const links = [...d.querySelectorAll('[data-lightbox]')];
  if (links.length) {
    const box = d.createElement('dialog');
    box.className = 'lightbox';
    box.innerHTML = '<button class="lb-close" type="button" aria-label="Close">×</button><button class="lb-prev" type="button" aria-label="Previous image">‹</button><img alt=""><button class="lb-next" type="button" aria-label="Next image">›</button>';
    d.body.appendChild(box);
    const img = box.querySelector('img');
    let current = 0;
    const show = (i) => { current = (i + links.length) % links.length; img.src = links[current].href; img.alt = links[current].querySelector('img').alt; };
    const open = (i) => (e) => { e.preventDefault(); show(i); box.showModal(); };
    links.forEach((a, i) => a.addEventListener('click', open(i)));
    d.querySelectorAll('[data-lightbox-dup]').forEach((a, i) => a.addEventListener('click', open(i % links.length)));
    box.querySelector('.lb-close').addEventListener('click', () => box.close());
    box.querySelector('.lb-prev').addEventListener('click', () => show(current - 1));
    box.querySelector('.lb-next').addEventListener('click', () => show(current + 1));
    box.addEventListener('click', (e) => { if (e.target === box) box.close(); });
    box.addEventListener('keydown', (e) => { if (e.key === 'ArrowLeft') show(current - 1); if (e.key === 'ArrowRight') show(current + 1); });
  }

  // Contact form: field validation, then an in-page submit (falls back to a normal post).
  d.querySelectorAll('[data-orwo-form]').forEach((form) => {
    let loaded = Date.now();
    const elapsed = form.querySelector('[data-elapsed]');
    const status = form.querySelector('.form-status');
    const setError = (field, message) => {
      const wrap = field.closest('.field');
      field.setAttribute('aria-invalid', message ? 'true' : 'false');
      if (wrap) wrap.querySelector('.field-error').textContent = message || '';
    };
    const say = (cls, text) => { status.innerHTML = ''; const p = d.createElement('p'); p.className = cls; p.textContent = text; status.appendChild(p); };
    form.addEventListener('submit', async (e) => {
      elapsed.value = String(Date.now() - loaded);
      let first = null;
      form.querySelectorAll('input:not([type=hidden]):not([name=orwo_website]), textarea').forEach((field) => {
        const label = field.closest('.field').querySelector('label').firstChild.textContent.trim();
        let message = '';
        if (field.required && !field.value.trim()) message = label + ' is required.';
        else if (field.type === 'email' && field.value && !field.checkValidity()) message = 'Please enter a valid email address.';
        setError(field, message);
        if (message && !first) first = field;
      });
      e.preventDefault();
      if (first) { say('form-error', 'Please check the highlighted fields.'); first.focus(); return; }
      form.classList.add('sending');
      try {
        const response = await fetch(form.action, { method: 'POST', body: new FormData(form), headers: { Accept: 'application/json' }, credentials: 'same-origin' });
        const result = await response.json();
        if (result.ok) {
          form.reset();
          loaded = Date.now();
          say('form-success', result.message);
        } else {
          say('form-error', result.message);
          Object.entries(result.errors || {}).forEach(([key, message]) => { const field = form.querySelector('[name="orwo_' + key + '"]'); if (field) setError(field, message); });
        }
      } catch (error) {
        HTMLFormElement.prototype.submit.call(form);
        return;
      } finally {
        form.classList.remove('sending');
      }
      status.scrollIntoView({ block: 'nearest', behavior: reduce ? 'auto' : 'smooth' });
    });
  });
})();
