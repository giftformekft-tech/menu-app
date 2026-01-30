(function(){
  const qs = (s, r=document)=>r.querySelector(s);
  const qsa = (s, r=document)=>Array.from(r.querySelectorAll(s));

  function openDrawer(){
    const drawer = qs('#fsm-drawer');
    const overlay = qs('.fsm-overlay');
    if(!drawer || !overlay) return;

    overlay.hidden = false;
    drawer.hidden = false;

    // trigger transition
    requestAnimationFrame(()=> drawer.classList.add('is-open'));

    // aria
    qsa('[data-fsm-open]').forEach(btn=>btn.setAttribute('aria-expanded','true'));

    // lock scroll
    document.documentElement.classList.add('fsm-lock');
    document.body.classList.add('fsm-lock');

    // focus close
    const close = qs('[data-fsm-close]', drawer);
    if(close) close.focus({preventScroll:true});
  }

  function closeDrawer(){
    const drawer = qs('#fsm-drawer');
    const overlay = qs('.fsm-overlay');
    if(!drawer || !overlay) return;

    drawer.classList.remove('is-open');

    // aria
    qsa('[data-fsm-open]').forEach(btn=>btn.setAttribute('aria-expanded','false'));

    // unlock scroll
    document.documentElement.classList.remove('fsm-lock');
    document.body.classList.remove('fsm-lock');

    // hide after transition
    window.setTimeout(()=>{
      drawer.hidden = true;
      overlay.hidden = true;
    }, 220);

    // return focus
    const btn = qs('[data-fsm-open]');
    if(btn) btn.focus({preventScroll:true});
  }

  function toggleSection(btn){
    const section = btn.closest('.fsm-section');
    if(!section) return;
    const expanded = btn.getAttribute('aria-expanded') === 'true';
    const panelId = btn.getAttribute('aria-controls');
    const panel = panelId ? qs('#'+CSS.escape(panelId)) : null;

    // close others (accordion)
    qsa('.fsm-section__toggle[aria-expanded="true"]').forEach(b=>{
      if(b===btn) return;
      b.setAttribute('aria-expanded','false');
      const pid = b.getAttribute('aria-controls');
      const p = pid ? qs('#'+CSS.escape(pid)) : null;
      if(p){ p.hidden = true; }
      const s = b.closest('.fsm-section');
      if(s) s.setAttribute('aria-expanded','false');
    });

    btn.setAttribute('aria-expanded', expanded ? 'false' : 'true');
    section.setAttribute('aria-expanded', expanded ? 'false' : 'true');
    if(panel){ panel.hidden = expanded; }
  }

  function toggleMore(btn){
    const panel = btn.closest('.fsm-panel');
    if(!panel) return;
    const chips = qs('.fsm-chips', panel);
    if(!chips) return;
    const isExpanded = chips.classList.contains('is-expanded');
    chips.classList.toggle('is-expanded', !isExpanded);
    btn.innerHTML = !isExpanded ? 'kevesebb <span aria-hidden="true">−</span>' : 'még több <span aria-hidden="true">+</span>';
  }

  // global click handler
  document.addEventListener('click', (e)=>{
    const openBtn = e.target.closest('[data-fsm-open]');
    if(openBtn){
      e.preventDefault();
      openDrawer();
      return;
    }
    if(e.target.closest('[data-fsm-close]')){
      e.preventDefault();
      closeDrawer();
      return;
    }
    const secBtn = e.target.closest('.fsm-section__toggle');
    if(secBtn){
      e.preventDefault();
      toggleSection(secBtn);
      return;
    }
    const moreBtn = e.target.closest('[data-fsm-more]');
    if(moreBtn){
      e.preventDefault();
      toggleMore(moreBtn);
      return;
    }
  });

  // escape closes
  document.addEventListener('keydown', (e)=>{
    if(e.key === 'Escape'){
      const drawer = qs('#fsm-drawer');
      if(drawer && !drawer.hidden) closeDrawer();
    }
  });

  // prevent background scroll (simple)
  const style = document.createElement('style');
  style.textContent = `
    html.fsm-lock, body.fsm-lock { overflow:hidden !important; }
  `;
  document.head.appendChild(style);
})();
