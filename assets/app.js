(() => {
  const $=(s,r=document)=>r.querySelector(s), $$=(s,r=document)=>[...r.querySelectorAll(s)];
  const API='api/';
  const state={content:null,route:'home',lessonMode:'teacher',currentLesson:null,currentBody:null,lessonFilter:'all',signupShown:false,eventMonth:null,audio:{chunks:[],index:0,utterance:null,playing:false,paused:false}};
  const store={get(k,d){try{return JSON.parse(localStorage.getItem('rccg_'+k))??d}catch{return d}},set(k,v){localStorage.setItem('rccg_'+k,JSON.stringify(v))}};
  const esc=s=>String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  const fmtDate=d=>new Intl.DateTimeFormat(undefined,{weekday:'long',day:'numeric',month:'long',year:'numeric'}).format(new Date(d+'T12:00:00'));
  const fmtDateTime=d=>new Intl.DateTimeFormat(undefined,{weekday:'long',day:'numeric',month:'long',hour:'2-digit',minute:'2-digit'}).format(new Date(d));
  const fmtTime=d=>new Intl.DateTimeFormat(undefined,{hour:'2-digit',minute:'2-digit'}).format(new Date(d));
  const fmtEventRange=e=>e?.end_at?`${fmtDateTime(e.start_at)} - ${fmtTime(e.end_at)}`:fmtDateTime(e.start_at);
  async function json(url,opts){const r=await fetch(url,opts);const j=await r.json().catch(()=>({ok:false,message:'Unexpected server response.'}));if(!r.ok&&!j.message)j.message='Request failed.';return j}

  async function init(){
    if(!location.hash || location.hash==='#') history.replaceState(null,'',location.pathname+location.search+'#/home');
    bindGlobal();
    try{state.content=await json(API+'content.php');if(!state.content?.ok)throw new Error(state.content?.message||'Private content unavailable');renderDynamic();}catch(e){renderConnectionError(e)}
    await updateSignupState();
    route(location.hash.replace('#/','')||'home',false);
    startSignupTimer();registerSW();
  }

  function bindGlobal(){
    window.addEventListener('hashchange',()=>route(location.hash.replace('#/','')||'home',false));
    $('#menuBtn')?.addEventListener('click',()=>$('#drawer')?.classList.add('open'));
    $('#mobileMore')?.addEventListener('click',()=>$('#drawer')?.classList.add('open'));
    $('#closeDrawer')?.addEventListener('click',()=>$('#drawer')?.classList.remove('open'));
    $('#drawer')?.addEventListener('click',e=>{if(e.target.id==='drawer')e.currentTarget.classList.remove('open')});
    $('#topDonate')?.addEventListener('click',openDonate);$('#desktopDonate')?.addEventListener('click',openDonate);
    $('#shareApp')?.addEventListener('click',()=>sharePage('RCCG Open Heavens Fife',location.origin+location.pathname+'#/home'));
    $('#installApp')?.addEventListener('click',installPwa);$('#drawerInstall')?.addEventListener('click',installPwa);
    $('#closeInstall')?.addEventListener('click',()=>$('#installModal')?.classList.remove('open'));$('#drawerSignup')?.addEventListener('click',()=>{ $('#drawer')?.classList.remove('open'); openSignup(); });
    document.addEventListener('click',e=>{
      const routeEl=e.target.closest('[data-route]');
      if(routeEl){e.preventDefault();route(routeEl.dataset.route);return}
      const c=e.target.closest('[data-copy]');if(c){copyText(c.dataset.copy,c);return}
      const ext=e.target.closest('a[data-external]');if(ext){ext.target='_blank';ext.rel='noopener noreferrer'}
    });
    $('#keepEditingForm')?.addEventListener('click',()=>$('#discardFormModal')?.classList.remove('open'));
    $('#discardFormNow')?.addEventListener('click',()=>{ $('#discardFormModal')?.classList.remove('open'); route('home'); });
    document.addEventListener('keydown',e=>{if(e.key==='Escape'){$$('.modal.open,.drawer.open').forEach(x=>x.classList.remove('open'))}});
  }

  function route(name,push=true){
    if(!name)name='home';
    if(name==='read')name='home';
    const alreadyHome=state.route==='home'&&name==='home';
    state.route=name;
    document.body.classList.toggle('form-route',name.startsWith('form/'));
    if(push)history.pushState(null,'',location.pathname+location.search+'#/'+name);
    const page=basePage(name);
    $$('.page-panel').forEach(p=>p.classList.toggle('active',p.dataset.page===page));
    $$('[data-route]').forEach(x=>x.classList.toggle('active',x.dataset.route===page||x.dataset.route===name));
    $('#drawer')?.classList.remove('open');
    if(name.startsWith('discovery/lesson/')) openLesson(Number(name.split('/').pop()));
    else if(name==='discovery') {$('#discoveryBrowse').style.display='block';$('#lessonView').style.display='none';renderDiscovery();}
    else if(name==='whats-on') renderEvents();
    else if(name==='service-times') renderServiceTimes();
    else if(name==='contact') renderChurchInfo();
    else if(name.startsWith('form/')) renderForm(name.split('/')[1]);
    window.scrollTo({top:0,behavior:alreadyHome?'smooth':'auto'});
  }
  function basePage(name){if(name.startsWith('discovery'))return'discovery';if(name.startsWith('form/'))return'forms';return name}

  function renderDynamic(){
    const c=state.content;if(!c?.ok)return;
    renderHomeTiles();renderNext();renderChurchInfo();setQuickLinks();renderConnectLinks();renderManualYear();
  }

  function setQuickLinks(){
    const s=state.content?.settings||{};
    const wa=(s.whatsapp||'').replace(/\D/g,'');
    if(wa){
      const a=$('#whatsappFab');
      if(a){
        const pastor=s.pastor_name||'Pastor';const msg=encodeURIComponent(`Hello ${pastor}, I'm contacting you from the RCCG Open Heavens Fife WebApp.`);
        a.href='https://wa.me/'+wa+'?text='+msg;
      }
    }
  }

  function renderHomeTiles(){
    const tiles=state.content?.quick_access||[];const g=$('#quickGrid');if(!g)return;
    g.innerHTML=tiles.map(t=>{
      const inner=`<span class="tile-icon">${esc(t.icon||'')}</span><strong>${esc(t.title)}</strong><small>${esc(t.subtitle||'')}</small><span class="arrow">${esc(t.button_label||'Open')} →</span>`;
      if(t.external_url)return `<a class="card tile" href="${esc(t.external_url)}" data-external target="_blank" rel="noopener noreferrer">${inner}</a>`;
      return `<a class="card tile" href="#/${esc(t.route)}" data-route="${esc(t.route)}">${inner}</a>`;
    }).join('');
  }

  function renderConnectLinks(){
    const forms=state.content?.forms||{};const entries=Object.entries(forms).filter(([,f])=>f.show_in_nav).sort((a,b)=>(a[1].nav_order||0)-(b[1].nav_order||0));
    const make=cls=>entries.map(([key,f])=>`<a class="${cls}" data-route="form/${esc(key)}" href="#/form/${esc(key)}">${esc(f.icon||'✉')} ${esc(f.title||key)}</a>`).join('');
    const side=$('#sideConnectLinks');if(side)side.innerHTML=make('side-link');const drawer=$('#drawerConnectLinks');if(drawer)drawer.innerHTML=make('drawer-link');
  }

  function renderNext(){
    const el=$('#nextAtChurch');if(!el||!state.content)return;
    const now=state.content.server_now?new Date(state.content.server_now):new Date();
    const future=(state.content.events||[]).filter(e=>new Date(e.start_at)>=now).sort((a,b)=>new Date(a.start_at)-new Date(b.start_at));
    const e=future[0];
    if(!e){el.innerHTML='<div class="empty">No upcoming activity has been published yet.</div>';return}
    const action=e.slug==='daily-prayer'
      ? `<a class="btn btn-primary" href="#/whats-on" data-route="whats-on">View in What's On →</a>`
      : (e.join_url?`<a class="btn btn-primary" data-external target="_blank" rel="noopener noreferrer" href="${esc(e.join_url)}">Join / Open ↗</a>`:'');
    el.innerHTML=`<div class="card"><time>${fmtEventRange(e)}</time><h3>${esc(e.title)}</h3><p>${esc(e.description||'')}</p>${action}</div>`;
  }

  function changeReaderFont(dir){const r=$('#devotionalBody')||$('#lessonReader');if(!r)return;let n=parseFloat(getComputedStyle(r).fontSize);r.style.fontSize=Math.max(16,Math.min(30,n+dir*2))+'px'}

  async function loadManualYear(year){
    if(!year||year===state.content?.active_manual_year)return;
    const c=await json(API+'content.php?year='+encodeURIComponent(year));
    if(c.ok){state.content=c;renderManualYear();renderDiscovery();}
  }
  function renderManualYear(){
    const sel=$('#manualYear');if(!sel||!state.content)return;
    const years=state.content.manual_years||[];
    sel.innerHTML=years.map(y=>`<option ${y===state.content.active_manual_year?'selected':''}>${esc(y)}</option>`).join('');
    sel.onchange=()=>loadManualYear(sel.value);
    const st=$('#manualSourceStatus');if(st)st.textContent=state.content.manual_meta?.source_status||'';
    const bind=(id,filter)=>{const el=$('#'+id);if(el)el.onclick=()=>{state.lessonFilter=filter;renderDiscovery();requestAnimationFrame(()=>$('#lessonToc')?.closest('.toc-section')?.scrollIntoView({behavior:'smooth',block:'start'}));};};
    bind('allLessons','all');bind('pastLessons','past');bind('thisWeekLesson','thisweek');bind('futureLessons','future');
  }
  function isoDay(d){return String(d||'').slice(0,10)}
  function weekBounds(today){
    const d=new Date(today+'T12:00:00Z'),day=d.getUTCDay();
    const start=new Date(d);start.setUTCDate(d.getUTCDate()-day);
    const end=new Date(start);end.setUTCDate(start.getUTCDate()+6);
    return [start.toISOString().slice(0,10),end.toISOString().slice(0,10)];
  }
  function renderDiscovery(){
    const all=(state.content?.discovery_lessons||[]).slice().sort((a,b)=>isoDay(a.lesson_date).localeCompare(isoDay(b.lesson_date)));
    const today=isoDay(state.content?.server_now||new Date().toISOString());
    const [weekStart,weekEnd]=weekBounds(today);
    const groups={
      all,
      past:all.filter(l=>isoDay(l.lesson_date)<weekStart),
      thisweek:all.filter(l=>isoDay(l.lesson_date)>=weekStart&&isoDay(l.lesson_date)<=weekEnd),
      future:all.filter(l=>isoDay(l.lesson_date)>weekEnd)
    };
    const lessons=groups[state.lessonFilter]||all;
    const counts={all:all.length,past:groups.past.length,thisweek:groups.thisweek.length,future:groups.future.length};
    const labels={all:'All Lessons',past:'Past Lessons',thisweek:'This Week',future:'Future Lessons'};
    for(const [id,f] of [['allLessons','all'],['pastLessons','past'],['thisWeekLesson','thisweek'],['futureLessons','future']]){
      const b=$('#'+id);if(!b)continue;b.textContent=`${labels[f]} (${counts[f]})`;b.classList.toggle('btn-primary',state.lessonFilter===f);b.classList.toggle('filter-active',state.lessonFilter===f);b.setAttribute('aria-pressed',String(state.lessonFilter===f));
    }
    const status=$('#lessonFilterStatus');if(status)status.textContent=`Showing ${lessons.length} of ${all.length} lessons for ${state.content?.active_manual_year||''}.`;
    const picker=$('#lessonDate');
    if(picker){if(all.length){picker.min=isoDay(all[0].lesson_date);picker.max=isoDay(all[all.length-1].lesson_date);}if(!picker.dataset.bound){picker.dataset.bound='1';picker.addEventListener('change',()=>{const chosen=isoDay(picker.value);const m=all.find(x=>isoDay(x.lesson_date)===chosen);const st=$('#lessonDateStatus');if(m){if(st)st.textContent=`Lesson ${m.lesson_number}: ${m.title}`;route('discovery/lesson/'+m.lesson_number);}else if(st)st.textContent='No Discovery Class Teacher Manual lesson is scheduled for that date.';});}}
    const toc=$('#lessonToc');if(!toc)return;
    const quarterLabel=n=>n<=13?'First Quarter':n<=26?'Second Quarter':n<=39?'Third Quarter':'Fourth Quarter';let lastQuarter='';
    toc.innerHTML=lessons.map(l=>{const q=quarterLabel(Number(l.lesson_number));const separator=q!==lastQuarter?`<tr class="quarter-row"><th colspan="3" scope="rowgroup">${q}</th></tr>`:'';lastQuarter=q;const d=isoDay(l.lesson_date);const date=new Intl.DateTimeFormat('en-GB',{day:'2-digit',month:'short',year:'numeric',timeZone:'UTC'}).format(new Date(d+'T12:00:00Z'));const routeName='discovery/lesson/'+l.lesson_number;const available=l.teacher_available;return `${separator}<tr class="toc-lesson-row ${available?'':'not-loaded'}" data-toc-lesson="${l.lesson_number}"><td><a href="#/${routeName}" data-route="${routeName}">Lesson ${esc(l.lesson_number)}</a></td><td><a href="#/${routeName}" data-route="${routeName}" tabindex="-1">${esc(date)}</a></td><td><a href="#/${routeName}" data-route="${routeName}" tabindex="-1"><strong>${esc(l.title)}</strong>${available?'':' <span class="status-pill schedule">Schedule only</span>'}</a></td></tr>`;}).join('')||'<tr><td colspan="3"><div class="empty">No lessons match this filter.</div></td></tr>';
    $$('[data-toc-lesson]',toc).forEach(row=>row.onclick=e=>{if(e.target.closest('a'))return;route('discovery/lesson/'+row.dataset.tocLesson);});
  }

  async function openLesson(number){
    const l=(state.content?.discovery_lessons||[]).find(x=>Number(x.lesson_number)===Number(number));if(!l)return;
    state.currentLesson=l;state.currentBody=null;
    $('#discoveryBrowse').style.display='none';$('#lessonView').style.display='block';
    await renderLessonBody();
  }
  async function renderLessonBody(){
    const l=state.currentLesson;if(!l)return;
    const host=$('#lessonView');state.lessonMode='teacher';
    host.innerHTML=`<div class="breadcrumb"><a href="#/home" data-route="home">Home</a> / <a href="#/discovery" data-route="discovery">Discovery Class Teacher Manual</a> / Lesson ${esc(l.lesson_number)}</div><div class="lesson-loading card">Loading Discovery Class Teacher Manual…</div>`;
    let body;try{body=await json(API+'discovery.php?year='+encodeURIComponent(state.content.active_manual_year)+'&lesson='+encodeURIComponent(l.lesson_number)+'&type=teacher')}catch{body={ok:false,message:'Unable to load this lesson right now.'}}
    state.currentBody=body;const content=body.content||{},sections=[];
    if(body.available){
      if(content.opening_prayer)sections.push(['Opening Prayer',content.opening_prayer]);
      if(content.previous_knowledge)sections.push(['Previous Knowledge',content.previous_knowledge]);
      if(content.memory_verse)sections.push(['Memory Verse',content.memory_verse,'verse']);
      if(content.bible_passage)sections.push(['Bible Passage',content.bible_passage]);
      if(content.introduction)sections.push(['Introduction',content.introduction]);
      if(content.teacher_diary){const td=content.teacher_diary,bits=[];if(td.lesson_aim)bits.push('Lesson Aim: '+td.lesson_aim);if((td.teaching_objectives||[]).length)bits.push('Teaching Objectives: '+td.teaching_objectives.join(' • '));if((td.teaching_plan||[]).length)bits.push('Teaching Plan: '+td.teaching_plan.join(' • '));if(bits.length)sections.push(["Teacher's Diary",bits.join('\n')]);}
      for(const sec of (content.sections||[])){if(sec?.heading&&sec?.body)sections.push([sec.heading,sec.body]);}
    }
    const bodyHtml=body.available?sections.map(([h,b,kind])=>`<section><h3>${esc(h)}</h3>${kind==='verse'?`<div class="verse">${esc(b)}</div>`:`<p>${esc(b).replace(/\n/g,'<br>')}</p>`}</section>`).join(''):`<section class="content-not-loaded"><h3>Discovery Class Teacher Manual content</h3><p>${esc(body.message||'The authorised Discovery Class Teacher Manual lesson body has not yet been loaded.')}</p><p><strong>Lesson ${esc(l.lesson_number)}:</strong> ${esc(l.title)} — ${fmtDate(l.lesson_date)}</p></section>`;
    host.innerHTML=`<div class="breadcrumb"><a href="#/home" data-route="home">Home</a> / <a href="#/discovery" data-route="discovery">Discovery Class Teacher Manual</a> / Lesson ${esc(l.lesson_number)}</div><div class="section-head"><div><div class="eyebrow" style="color:var(--blue)">${fmtDate(l.lesson_date)}</div><h1 class="page-title">Lesson ${esc(l.lesson_number)} — ${esc(l.title)}</h1></div></div><div class="reader-toolbar"><button class="btn btn-soft" id="lessonListen">🎧 Listen</button><button class="btn" id="lessonBookmark">🔖 Bookmark</button><button class="btn" id="lessonSave">⬇ Save Offline</button><button class="btn" id="lessonShare">↗ Share</button><button class="btn" id="lessonAPlus">A+</button><button class="btn" id="lessonAMinus">A−</button></div><article class="reader lesson" id="lessonReader">${bodyHtml}<section class="note-box"><h3>My Notes</h3><textarea id="lessonNote" placeholder="Write your personal notes here..."></textarea><p class="help">Saved only on this device.</p></section></article>`;
    $('#lessonListen').onclick=()=>{const speech=body.available?[l.title,...sections.map(x=>x[0]+'. '+x[1])].join('. '):`Lesson ${l.lesson_number}. ${l.title}. Full Discovery Class Teacher Manual content has not yet been loaded.`;speakText(speech,l.title)};
    $('#lessonBookmark').onclick=()=>toggleBookmark('lesson:'+state.content.active_manual_year+':'+l.lesson_number,{type:'lesson',year:state.content.active_manual_year,lesson_number:l.lesson_number,title:l.title});
    $('#lessonSave').onclick=()=>saveOfflineLesson({meta:l,manual_type:'teacher',body});
    $('#lessonShare').onclick=()=>sharePage(l.title,location.origin+location.pathname+'#/discovery/lesson/'+l.lesson_number);
    $('#lessonAPlus').onclick=()=>changeReaderFont(1);$('#lessonAMinus').onclick=()=>changeReaderFont(-1);
    const noteKey='note_lesson_'+state.content.active_manual_year+'_'+l.lesson_number;$('#lessonNote').value=store.get(noteKey,'');$('#lessonNote').addEventListener('input',e=>store.set(noteKey,e.target.value));
  }

  function renderEvents(){
    const el=$('#eventsList');if(!el||!state.content)return;
    const currentMonth=(state.content?.server_now||new Date().toISOString()).slice(0,7);if(!state.eventMonth)state.eventMonth=currentMonth;
    const monthInput=$('#eventMonth');if(monthInput){monthInput.value=state.eventMonth;monthInput.onchange=()=>{state.eventMonth=monthInput.value||currentMonth;renderEvents();};}
    const shiftMonth=delta=>{const [y,m]=state.eventMonth.split('-').map(Number);const d=new Date(Date.UTC(y,m-1+delta,1));state.eventMonth=`${d.getUTCFullYear()}-${String(d.getUTCMonth()+1).padStart(2,'0')}`;renderEvents();};
    if($('#eventPrevMonth'))$('#eventPrevMonth').onclick=()=>shiftMonth(-1);if($('#eventNextMonth'))$('#eventNextMonth').onclick=()=>shiftMonth(1);if($('#eventThisMonth'))$('#eventThisMonth').onclick=()=>{state.eventMonth=currentMonth;renderEvents();};
    const events=(state.content.events||[]).filter(e=>String(e.start_at||'').slice(0,7)===state.eventMonth).slice().sort((a,b)=>new Date(a.start_at)-new Date(b.start_at));const st=state.content.settings||{};
    el.innerHTML=events.map(e=>{
      const live=(e.facebook_live||e.youtube_live)
        ? `<div class="sunday-live"><strong>Join us live on Sunday</strong>${e.facebook_live&&st.facebook?`<a class="btn btn-facebook" data-external target="_blank" rel="noopener noreferrer" href="${esc(st.facebook)}">Facebook Live ↗</a>`:''}${e.youtube_live&&st.youtube?`<a class="btn btn-youtube" data-external target="_blank" rel="noopener noreferrer" href="${esc(st.youtube)}">YouTube Live ↗</a>`:''}</div>`
        : '';
      const join=e.join_url?`<a data-external target="_blank" rel="noopener noreferrer" class="btn btn-primary" href="${esc(e.join_url)}">Join / Open ↗</a>`:'';
      return `<article class="card"><div class="eyebrow" style="color:var(--blue)">${fmtEventRange(e)}</div><h3>${esc(e.title)}</h3><p>${esc(e.description||'')}</p>${e.location?`<p>📍 ${esc(e.location)}</p>`:''}${join}${live}</article>`;
    }).join('')||'<div class="empty">No church activities are scheduled for this month.</div>';
  }

  function renderServiceTimes(){
    const el=$('#serviceTimes');if(!el)return;const rows=state.content?.service_times||[],tz=state.content?.timezone||'Europe/London';
    el.innerHTML=`<div class="timezone-note">🕒 Service times use ${esc(tz)} and automatically follow local clock changes.</div>`+rows.map(r=>`<div class="card"><strong>${esc(r.day_label)} · ${esc(r.time_label)}</strong><p>${esc(r.service_name)}</p>${r.note?`<small>${esc(r.note)}</small>`:''}</div>`).join('');
  }
  function renderChurchInfo(){
    const el=$('#churchInfo');if(!el)return;const cards=state.content?.contact_cards||[];
    el.innerHTML=`<div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(220px,1fr))">${cards.map(c=>{
      let href='';
      if(c.action_type==='tel')href='tel:'+String(c.value||'');
      else if(c.action_type==='whatsapp')href='https://wa.me/'+String(c.value||'').replace(/[^0-9]/g,'');
      else if(c.action_type==='external')href=String(c.value||'');
      else if(c.action_type==='email')href='mailto:'+String(c.value||'');
      const newWindow=(c.action_type==='external'||c.action_type==='whatsapp')?' data-external target="_blank" rel="noopener noreferrer"':'';
      const button=href&&c.button_label?`<a class="btn ${esc(c.button_class||'')}"${newWindow} href="${esc(href)}">${esc(c.button_label)}</a>`:'';
      const visibleValue=c.display_value
        ? (c.action_type==='email'&&href
            ? `<p><a class="contact-value-link" href="${esc(href)}">${esc(c.display_value)}</a></p>`
            : `<p>${esc(c.display_value)}</p>`)
        : '';
      return `<div class="card contact-card"><h3>${esc(c.icon||'')} ${esc(c.title)}</h3><p>${esc(c.subtitle||'')}</p>${visibleValue}${button}</div>`;
    }).join('')}</div>`;
  }

  function formConfig(type){return state.content?.forms?.[type]||null;}
  function formOptions(field){let opts=(field.options||[]).slice();if(field.options_source==='ministries')opts=(state.content?.ministries||[]).map(x=>x.name);if(field.append_options)opts=opts.concat(field.append_options);return opts;}
  function fieldHtml(field,i){const id='f_'+i+'_'+field.name,req=field.required?' required':'',max=field.maxlength?` maxlength="${Number(field.maxlength)}"`:'',help=field.help?` <span class="help">(${esc(field.help)})</span>`:'';
    if(field.type==='textarea')return `<div class="field"><label for="${id}">${esc(field.label)}${field.required?' *':''}${help}</label><textarea id="${id}" name="${esc(field.name)}"${req}${max}></textarea></div>`;
    if(field.type==='select'){const opts=formOptions(field);return `<div class="field"><label for="${id}">${esc(field.label)}${field.required?' *':''}</label><select id="${id}" name="${esc(field.name)}"${req}><option value="">Choose one</option>${opts.map(x=>`<option value="${esc(x)}">${esc(x)}</option>`).join('')}</select></div>`;}
    if(field.type==='radio'){const opts=field.options||[],labs=field.option_labels||opts;return `<fieldset class="field"><legend>${esc(field.label)}${field.required?' *':''}</legend><div class="choice-row">${opts.map((x,n)=>`<label><input type="radio" name="${esc(field.name)}" value="${esc(x)}"${req}> ${esc(labs[n]??x)}</label>`).join('')}</div></fieldset>`;}
    if(field.type==='rating'){const min=Number(field.min||1),maxn=Number(field.max||5);return `<fieldset class="field rating-field"><legend>${esc(field.label)}${field.required?' *':''}</legend><div class="star-rating" role="radiogroup" aria-label="${esc(field.label)}">${Array.from({length:maxn-min+1},(_,k)=>k+min).map(n=>`<label><input type="radio" name="${esc(field.name)}" value="${n}"${req}><span aria-hidden="true">★</span><span class="sr-only">${n} star${n===1?'':'s'}</span></label>`).join('')}</div></fieldset>`;}
    const type=['email','tel','text'].includes(field.type)?field.type:'text';return `<div class="field"><label for="${id}">${esc(field.label)}${field.required?' *':''}${help}</label><input id="${id}" name="${esc(field.name)}" type="${type}"${req}${max}${field.autocomplete?` autocomplete="${esc(field.autocomplete)}"`:''}></div>`;
  }
  function formIsDirty(form){
    if(!form)return false;
    return [...form.querySelectorAll('input:not([type="hidden"]):not([name="website"]),textarea,select')].some(el=>{
      if((el.type==='radio'||el.type==='checkbox'))return el.checked;
      return String(el.value||'').trim()!=='';
    });
  }
  function requestCloseForm(){
    const f=$('#dynamicForm');
    if(f&&formIsDirty(f)){$('#discardFormModal')?.classList.add('open');return;}
    route('home');
  }
  function renderForm(type){
    const cfg=formConfig(type),el=$('#formHost');if(!el)return;if(!cfg){el.innerHTML='<div class="empty">This form is not available right now.</div>';return;}
    const hidden=cfg.endpoint==='api/contact.php'?`<input type="hidden" name="form_type" value="${esc(type)}">`:'';
    el.innerHTML=`<div class="form-top-row"><div class="breadcrumb"><a href="#/home" data-route="home">Home</a> / ${esc(cfg.title)}</div><button class="form-close-btn" id="formCloseBtn" type="button" aria-label="Close this form" title="Close form">×</button></div><h1 class="page-title">${esc(cfg.icon||'')} ${esc(cfg.title)}</h1><p class="lede">${esc(cfg.description||'')}</p><form id="dynamicForm" class="card form-grid" method="post" action="${esc(cfg.endpoint)}">${hidden}<input type="text" name="website" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px" aria-hidden="true"><input type="hidden" name="started" value="${Date.now()-5000}">${(cfg.fields||[]).map(fieldHtml).join('')}<button class="btn btn-primary" type="submit">Submit</button><p id="formStatus" class="status" aria-live="polite"></p></form>`;
    $('#formCloseBtn')?.addEventListener('click',requestCloseForm);
    $('#dynamicForm').addEventListener('submit',submitDynamicForm);$$('.star-rating input').forEach(r=>r.addEventListener('change',()=>{$$('.star-rating label').forEach((label,i)=>label.classList.toggle('chosen',i<Number(r.value)));}));
  }
  async function submitDynamicForm(e){e.preventDefault();const f=e.currentTarget,s=$('#formStatus');s.textContent='Sending…';const fd=new FormData(f);try{const r=await fetch(f.action,{method:'POST',body:fd,headers:{Accept:'application/json'}});const j=await r.json();s.textContent=j.message||'Submitted.';if(j.ok){f.reset();$$('.star-rating label').forEach(label=>label.classList.remove('chosen'));}}catch{s.textContent='We could not submit this right now. Please try again.'}}

  function openDonate(){
    const d=state.content?.donation||{},refs=state.content?.donation_refs||[],host=$('#donateContent');
    host.innerHTML=`<h2>Donate to RCCG Open Heavens Fife</h2><p>Use your own banking app or bank. This WebApp does not collect or process card details.</p><div class="copy-row"><span>Sort Code<br><code>${esc(d.sort_code||'')}</code></span><button class="btn" data-copy="${esc(d.sort_code||'')}">Copy</button></div><div class="copy-row"><span>Account Number<br><code>${esc(d.account_number||'')}</code></span><button class="btn" data-copy="${esc(d.account_number||'')}">Copy</button></div><div class="field"><label for="donRef">Choose a payment reference</label><select id="donRef">${refs.map(r=>`<option value="${esc(r.reference_code)}">${esc(r.label)}</option>`).join('')}</select></div><div class="copy-row"><span>Reference<br><code id="donRefCode">${esc(refs[0]?.reference_code||'')}</code></span><button class="btn" id="copyRef">Copy</button></div><div class="modal-actions"><button class="btn" id="closeDonate">Close</button></div>`;
    const sel=$('#donRef');if(sel)sel.onchange=()=>$('#donRefCode').textContent=sel.value;$('#copyRef').onclick=()=>copyText(sel?.value||'',$('#copyRef'));$('#closeDonate').onclick=()=>$('#donateModal').classList.remove('open');$('#donateModal').classList.add('open');
  }

  function splitSpeechText(text,max=220){
    const clean=String(text||'').replace(/\s+/g,' ').trim();if(!clean)return[];const parts=clean.match(/[^.!?]+[.!?]+|[^.!?]+$/g)||[clean],out=[];let cur='';
    for(const part of parts){const piece=part.trim();if(!piece)continue;if((cur+' '+piece).trim().length<=max){cur=(cur+' '+piece).trim();continue}if(cur)out.push(cur);if(piece.length<=max){cur=piece;continue}for(let i=0;i<piece.length;i+=max)out.push(piece.slice(i,i+max));cur='';}if(cur)out.push(cur);return out;
  }
  function stopSpeech(){if('speechSynthesis'in window)speechSynthesis.cancel();state.audio={chunks:[],index:0,utterance:null,playing:false,paused:false};const bar=$('#audioBar');if(bar)bar.classList.remove('show');if($('#audioToggle'))$('#audioToggle').textContent='▶';const prog=$('#audioBar .progress span');if(prog)prog.style.width='0%';}
  function speakNextChunk(){if(!state.audio.playing||state.audio.index>=state.audio.chunks.length){stopSpeech();return}const text=state.audio.chunks[state.audio.index];const u=new SpeechSynthesisUtterance(text);state.audio.utterance=u;u.rate=1;u.pitch=1;u.volume=1;const voices=speechSynthesis.getVoices();const preferred=voices.find(v=>/^en-GB/i.test(v.lang))||voices.find(v=>/^en/i.test(v.lang));if(preferred)u.voice=preferred;u.onend=()=>{if(!state.audio.playing)return;state.audio.index+=1;const prog=$('#audioBar .progress span');if(prog)prog.style.width=Math.min(100,(state.audio.index/state.audio.chunks.length)*100)+'%';setTimeout(speakNextChunk,30)};u.onerror=()=>{toast('Audio stopped. Tap Listen to try again.');stopSpeech();};speechSynthesis.speak(u);}
  function speakText(text,title='Read Aloud'){
    if(!('speechSynthesis'in window)||typeof SpeechSynthesisUtterance==='undefined'){toast('Read-aloud is not supported by this browser.');return}const chunks=splitSpeechText(text);if(!chunks.length){toast('There is no text available to read aloud yet.');return}
    speechSynthesis.cancel();state.audio={chunks,index:0,utterance:null,playing:true,paused:false};const bar=$('#audioBar');if(bar)bar.classList.add('show');if($('#audioTitle'))$('#audioTitle').textContent=title;if($('#audioToggle'))$('#audioToggle').textContent='⏸';const prog=$('#audioBar .progress span');if(prog)prog.style.width='0%';
    $('#audioToggle').onclick=()=>{if(!state.audio.playing)return;if(state.audio.paused){speechSynthesis.resume();state.audio.paused=false;$('#audioToggle').textContent='⏸';}else{speechSynthesis.pause();state.audio.paused=true;$('#audioToggle').textContent='▶';}};$('#audioStop').onclick=stopSpeech;
    // Start immediately from the user's tap. If voices are still loading, the browser default voice is used.
    speakNextChunk();
  }

  function toggleBookmark(key,item){const b=store.get('bookmarks',{});if(b[key])delete b[key];else b[key]=item;store.set('bookmarks',b);toast(b[key]?'Bookmarked':'Bookmark removed')}
  function saveOfflineLesson(item){const o=store.get('offline_lessons',{}),key=(item.meta?.lesson_number||Date.now())+':'+item.manual_type;o[key]=item;store.set('offline_lessons',o);toast('Saved for offline reading on this device')}
  async function sharePage(title,url){try{if(navigator.share)await navigator.share({title,text:title,url});else{await navigator.clipboard.writeText(url);toast('Link copied')}}catch(e){if(e.name!=='AbortError')toast('Unable to share right now')}}
  async function copyText(text,btn){try{await navigator.clipboard.writeText(text);const old=btn.textContent;btn.textContent='Copied';setTimeout(()=>btn.textContent=old,1500)}catch{toast('Copy failed')}}
  function toast(msg){const t=$('#toast');t.textContent=msg;t.hidden=false;clearTimeout(t._timer);t._timer=setTimeout(()=>t.hidden=true,2200)}

  let deferredPrompt=null;
  window.addEventListener('beforeinstallprompt',e=>{e.preventDefault();deferredPrompt=e});
  async function installPwa(){
    if(deferredPrompt){deferredPrompt.prompt();await deferredPrompt.userChoice;deferredPrompt=null;return}
    const ios=/iphone|ipad|ipod/i.test(navigator.userAgent);
    const mac=/macintosh/i.test(navigator.userAgent);
    const help=$('#installHelp');
    help.innerHTML=ios
      ? '<p><strong>iPhone/iPad:</strong> tap the browser Share button, then choose <strong>Add to Home Screen</strong>.</p><p>You can also use Share to save or send this WebApp.</p>'
      : `<p><strong>Install:</strong> use your browser menu and choose <strong>Install app</strong> or <strong>Add to Home screen</strong> when shown.</p><p><strong>Bookmark:</strong> press ${mac?'⌘D':'Ctrl+D'} or use your browser's bookmark/star button.</p><p><strong>Share:</strong> use the ↗ button in the WebApp header.</p>`;
    $('#installModal').classList.add('open');
  }
  function registerSW(){if('serviceWorker'in navigator)navigator.serviceWorker.register('sw.js').catch(()=>{})}

  async function updateSignupState(){try{const s=await json(API+'status.php');if(s.verified){localStorage.setItem('rccg_verified','1');$('#signupBtn').textContent=s.email?.[0]?.toUpperCase()||'✓';$('#signupBtn').title=s.email||'Verified'}else{$('#signupBtn').onclick=()=>openSignup()}}catch{}}
  function startSignupTimer(){if(localStorage.getItem('rccg_verified')==='1')return;let active=0,last=Date.now();const timer=setInterval(()=>{const now=Date.now();if(document.visibilityState==='visible'){active+=now-last;if(active>=5000&&!state.signupShown){clearInterval(timer);openSignup()}}last=now},500)}
  function openSignup(){if(localStorage.getItem('rccg_verified')==='1')return;state.signupShown=true;$('#signupModal').classList.add('open');$('#signupStarted').value=Date.now()-5000}
  $('#signupClose')?.addEventListener('click',()=>$('#signupModal').classList.remove('open'));
  $('#signupForm')?.addEventListener('submit',async e=>{e.preventDefault();const s=$('#signupStatus');s.textContent='Sending verification link…';const body={email:$('#signupEmail').value,ts:Number($('#signupStarted').value),website:$('#signupWebsite').value,page:location.href};try{const j=await json(API+'subscribe.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(body)});s.textContent=j.message||''}catch{s.textContent='Could not start verification. Please try again.'}});

  function renderConnectionError(err){
    state.content={ok:false,discovery_lessons:[],events:[],service_times:[],settings:{},quick_access:[],contact_cards:[],forms:{},manual_years:[]};
    const msg='The private church content could not be loaded. Please confirm the rccg_fife_private folder is installed beside public_html.';
    const q=$('#quickGrid');if(q)q.innerHTML=`<div class="card" style="grid-column:1/-1"><strong>Content connection error</strong><p>${esc(msg)}</p></div>`;
    const n=$('#nextAtChurch');if(n)n.innerHTML=`<div class="empty">${esc(msg)}</div>`;
    const toc=$('#lessonToc');if(toc)toc.innerHTML=`<tr><td colspan="3"><div class="empty">${esc(msg)}</div></td></tr>`;
    console.error(err);
  }
  document.addEventListener('DOMContentLoaded',init);
})();
