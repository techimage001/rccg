(() => {
  const $=(s,r=document)=>r.querySelector(s), $$=(s,r=document)=>[...r.querySelectorAll(s)];
  const API='api/';
  const state={content:null, route:'home', lessonMode:'student', currentLesson:null, speech:null, signupShown:false};
  const store={
    get(k,d){try{return JSON.parse(localStorage.getItem('rccg_'+k))??d}catch{return d}},
    set(k,v){localStorage.setItem('rccg_'+k,JSON.stringify(v))}
  };
  const esc=s=>String(s??'').replace(/[&<>"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
  const fmtDate=d=>new Intl.DateTimeFormat(undefined,{weekday:'short',day:'numeric',month:'short',year:'numeric'}).format(new Date(d+'T12:00:00'));
  async function json(url,opts){const r=await fetch(url,opts);const j=await r.json().catch(()=>({ok:false,message:'Unexpected server response.'}));if(!r.ok&&!j.message)j.message='Request failed.';return j}

  async function init(){
    bindGlobal();
    try{state.content=await json(API+'content.php'); renderDynamic();}catch(e){renderOfflineFallback()}
    await updateSignupState();
    route(location.hash.replace('#/','')||'home',false);
    startSignupTimer();
    registerSW();
  }

  function bindGlobal(){
    window.addEventListener('hashchange',()=>route(location.hash.replace('#/','')||'home',false));
    $('#menuBtn')?.addEventListener('click',()=>$('#drawer').classList.add('open'));
    $('#closeDrawer')?.addEventListener('click',()=>$('#drawer').classList.remove('open'));
    $('#drawer')?.addEventListener('click',e=>{if(e.target.id==='drawer')e.currentTarget.classList.remove('open')});
    $$('[data-route]').forEach(a=>a.addEventListener('click',e=>{e.preventDefault();route(a.dataset.route)}));
    $$('[data-donate]').forEach(b=>b.addEventListener('click',openDonate));
    $('#shareApp')?.addEventListener('click',()=>sharePage(document.title,location.origin+location.pathname));
    $('#installApp')?.addEventListener('click',installPwa);
    document.addEventListener('click',e=>{
      const c=e.target.closest('[data-copy]'); if(c) copyText(c.dataset.copy,c);
      const ext=e.target.closest('a[data-external]'); if(ext){ext.target='_blank';ext.rel='noopener noreferrer'}
    });
    document.addEventListener('keydown',e=>{if(e.key==='Escape'){$$('.modal.open,.drawer.open').forEach(x=>x.classList.remove('open'))}});
  }

  function route(name,push=true){
    if(!name)name='home'; state.route=name;
    if(push)history.pushState(null,'','#/'+name);
    $$('.page-panel').forEach(p=>p.classList.toggle('active',p.dataset.page===basePage(name)));
    $$('[data-route]').forEach(x=>x.classList.toggle('active',x.dataset.route===basePage(name)));
    $('#drawer')?.classList.remove('open');
    if(name.startsWith('discovery/lesson/')) openLesson(name.split('/').pop());
    if(name==='discovery') renderDiscovery();
    if(name==='whats-on') renderEvents();
    if(name==='service-times') renderServiceTimes();
    if(name==='contact') renderChurchInfo();
    if(name==='donate') openDonate();
    if(name.startsWith('form/')) renderForm(name.split('/')[1]);
    window.scrollTo({top:0,behavior:'smooth'});
  }
  function basePage(name){if(name.startsWith('discovery'))return'discovery';if(name.startsWith('form/'))return'forms';return name}

  function renderDynamic(){
    const c=state.content;
    if(!c?.ok)return;
    const hero=$('#todayHero');
    if(c.today_devotional){hero.innerHTML=`<div class="eyebrow">Today's Devotional</div><h1>${esc(c.today_devotional.title)}</h1><p>${esc(c.today_devotional.summary||'Grow daily in God\'s Word with a calm, focused reading experience.')}</p><div class="hero-actions"><button class="btn" id="heroListen">🎧 Listen</button><button class="btn" data-route="read">📖 Read Devotional</button><button class="btn btn-gold" data-donate>❤ Donate</button></div>`}
    hero?.querySelector('#heroListen')?.addEventListener('click',()=>speakText(c.today_devotional.body||c.today_devotional.summary||c.today_devotional.title));
    hero?.querySelector('[data-route="read"]')?.addEventListener('click',()=>route('read'));
    hero?.querySelector('[data-donate]')?.addEventListener('click',openDonate);
    renderHomeTiles(); renderNext(); renderRead(); renderChurchInfo();
  }

  function renderHomeTiles(){
    const tiles=[
      ['📖','Open Heavens','Daily devotional reading','read'],['🎓','Discovery Class','Student & Teacher Manuals','discovery'],['👥','Teens Open Heavens','Resources for teens','read'],['🏠','House Fellowship','Study and fellowship resources','read'],['📘','Bible','Read and search scripture','read'],['🎵','Hymns','Hymns & songs','read'],['📅',"What's On",'Upcoming church activities','whats-on'],['🙏','Prayer Request','Send a prayer request','form/prayer'],['👤','Speak With Pastor','Private pastoral contact','form/pastor'],['🤝','Join a Ministry','Serve in the church','form/ministry'],['❤️','Sponsor a Child','Send sponsorship interest','form/sponsorship'],['💡','Suggestion','Share an idea with us','form/suggestion']
    ];
    $('#quickGrid').innerHTML=tiles.map(([i,t,s,r])=>`<a class="card tile" href="#/${r}" data-route="${r}"><span class="tile-icon">${i}</span><strong>${t}</strong><small>${s}</small><span class="arrow">Open →</span></a>`).join('');
    $$('#quickGrid [data-route]').forEach(a=>a.addEventListener('click',e=>{e.preventDefault();route(a.dataset.route)}));
  }

  function renderNext(){
    const el=$('#nextAtChurch'); if(!el||!state.content)return;
    const events=state.content.events||[]; const now=new Date();
    const future=events.filter(e=>new Date(e.start_at)>now).sort((a,b)=>new Date(a.start_at)-new Date(b.start_at));
    const e=future[0];
    if(!e){el.innerHTML='<div class="empty">No upcoming activity has been published yet.</div>';return}
    el.innerHTML=`<div class="card"><time>${new Intl.DateTimeFormat(undefined,{weekday:'long',day:'numeric',month:'long',hour:'2-digit',minute:'2-digit'}).format(new Date(e.start_at))}</time><h3>${esc(e.title)}</h3><p>${esc(e.description||'')}</p>${e.join_url?`<a class="btn btn-primary" data-external href="${esc(e.join_url)}">Join / Open ↗</a>`:''}</div>`;
  }

  function renderRead(){
    const c=state.content?.today_devotional; const el=$('#readerContent'); if(!el)return;
    if(!c){el.innerHTML='<div class="empty">No devotional published for today yet.</div>';return}
    el.innerHTML=`<div class="breadcrumb"><a href="#/home" data-route="home">Home</a> / Today's Devotional</div><h1 class="page-title">${esc(c.title)}</h1><div class="reader-toolbar"><button class="btn btn-soft" id="readListen">🎧 Listen</button><button class="btn" id="readBookmark">🔖 Bookmark</button><button class="btn" id="readShare">↗ Share</button><button class="btn" id="fontUp">A+</button><button class="btn" id="fontDown">A−</button></div><article class="reader" id="devotionalBody"><section><h3>Memory Verse</h3><div class="verse">${esc(c.memory_verse||'')}</div></section><section><h3>Bible Reading</h3><p>${esc(c.bible_reading||'')}</p></section><section><h3>Devotional</h3><p>${esc(c.body||'')}</p></section></article>`;
    $('#readListen').onclick=()=>speakText([c.title,c.memory_verse,c.body].join('. '));
    $('#readBookmark').onclick=()=>toggleBookmark('devotional:'+c.id,{type:'devotional',id:c.id,title:c.title});
    $('#readShare').onclick=()=>sharePage(c.title,location.origin+location.pathname+'#/read');
    $('#fontUp').onclick=()=>changeReaderFont(1); $('#fontDown').onclick=()=>changeReaderFont(-1);
    $$('[data-route]',el).forEach(a=>a.onclick=e=>{e.preventDefault();route(a.dataset.route)});
  }
  function changeReaderFont(dir){const r=$('#devotionalBody')||$('#lessonReader');if(!r)return;let n=parseFloat(getComputedStyle(r).fontSize);r.style.fontSize=Math.max(16,Math.min(28,n+dir*2))+'px'}

  function renderDiscovery(){
    const lessons=(state.content?.discovery_lessons||[]).slice().sort((a,b)=>a.lesson_date.localeCompare(b.lesson_date));
    const list=$('#lessonList'); if(!list)return;
    const picker=$('#lessonDate'); if(picker&&!picker.dataset.bound){picker.dataset.bound=1;picker.addEventListener('change',()=>{const m=lessons.find(x=>x.lesson_date===picker.value);if(m)route('discovery/lesson/'+m.id); else $('#lessonDateStatus').textContent='No published lesson is available for that date.'})}
    const today=new Date().toISOString().slice(0,10);
    list.innerHTML=lessons.map(l=>{const kind=l.lesson_date<today?'Past':l.lesson_date===today?'Current':'Future';return `<button class="lesson-item ${kind.toLowerCase()}" data-lesson="${l.id}"><span>📘</span><span><strong>${esc(l.title)}</strong><small>${fmtDate(l.lesson_date)} · Lesson ${esc(l.lesson_number)}</small><span class="status-pill">${kind}</span></span></button>`}).join('')||'<div class="empty">No Discovery Class lessons published yet.</div>';
    $$('[data-lesson]',list).forEach(b=>b.onclick=()=>route('discovery/lesson/'+b.dataset.lesson));
  }

  function openLesson(id){
    const l=(state.content?.discovery_lessons||[]).find(x=>String(x.id)===String(id)); if(!l)return;
    state.currentLesson=l; $('#discoveryBrowse').style.display='none'; $('#lessonView').style.display='block';
    renderLessonBody();
  }
  function renderLessonBody(){
    const l=state.currentLesson;if(!l)return;
    const teacher=state.lessonMode==='teacher';
    $('#lessonView').innerHTML=`<div class="breadcrumb"><a href="#/home" data-route="home">Home</a> / <a href="#/discovery" id="backDiscovery">Discovery Class</a> / Lesson ${esc(l.lesson_number)}</div><div class="section-head"><div><div class="eyebrow" style="color:var(--blue)">${fmtDate(l.lesson_date)}</div><h1 class="page-title">${esc(l.title)}</h1></div><button class="btn btn-gold" data-donate>❤ Donate</button></div><div class="segmented" aria-label="Discovery Class manual type"><button id="studentMode" class="${teacher?'':'active'}">Student Manual</button><button id="teacherMode" class="${teacher?'active':''}">Teacher Manual</button></div><div class="reader-toolbar"><button class="btn btn-soft" id="lessonListen">🎧 Listen</button><button class="btn" id="lessonBookmark">🔖 Bookmark</button><button class="btn" id="lessonSave">⬇ Save Offline</button><button class="btn" id="lessonShare">↗ Share</button><button class="btn" id="lessonAPlus">A+</button><button class="btn" id="lessonAMinus">A−</button></div><article class="reader lesson" id="lessonReader"><section><h3>Memory Verse</h3><div class="verse">${esc(l.memory_verse)}</div></section><section><h3>Bible Passage</h3><p>${esc(l.bible_passage)}</p></section><section><h3>Introduction</h3><p>${esc(l.introduction)}</p></section><section><h3>Lesson Outline</h3><p>${esc(l.student_content)}</p></section>${teacher?`<section><h3>Teacher Guidance</h3><p>${esc(l.teacher_content)}</p></section>`:''}<section><h3>Class Activity</h3><p>${esc(l.class_activity)}</p></section><section><h3>Discussion Questions</h3><p>${esc(l.discussion_questions)}</p></section><section><h3>Conclusion</h3><p>${esc(l.conclusion)}</p></section><section class="note-box"><h3>My Notes</h3><textarea id="lessonNote" placeholder="Write your personal notes here..."></textarea><p class="help">Saved only on this device.</p></section></article>`;
    $('#backDiscovery').onclick=e=>{e.preventDefault();$('#discoveryBrowse').style.display='block';$('#lessonView').style.display='none';route('discovery')};
    $('#studentMode').onclick=()=>{state.lessonMode='student';renderLessonBody()}; $('#teacherMode').onclick=()=>{state.lessonMode='teacher';renderLessonBody()};
    $('#lessonListen').onclick=()=>speakText([l.title,l.memory_verse,l.bible_passage,l.student_content,teacher?l.teacher_content:''].join('. '));
    $('#lessonBookmark').onclick=()=>toggleBookmark('lesson:'+l.id,{type:'lesson',id:l.id,title:l.title});
    $('#lessonSave').onclick=()=>saveOfflineLesson(l);
    $('#lessonShare').onclick=()=>sharePage(l.title,location.origin+location.pathname+'#/discovery/lesson/'+l.id);
    $('#lessonAPlus').onclick=()=>changeReaderFont(1); $('#lessonAMinus').onclick=()=>changeReaderFont(-1);
    const noteKey='note_lesson_'+l.id; $('#lessonNote').value=store.get(noteKey,''); $('#lessonNote').addEventListener('input',e=>store.set(noteKey,e.target.value));
    $('[data-donate]','#lessonView').onclick=openDonate;
  }

  function renderEvents(){
    const el=$('#eventsList');if(!el)return; const events=(state.content?.events||[]).slice().sort((a,b)=>a.start_at.localeCompare(b.start_at));
    el.innerHTML=events.map(e=>`<article class="card"><div class="eyebrow" style="color:var(--blue)">${new Intl.DateTimeFormat(undefined,{weekday:'long',day:'numeric',month:'long',hour:'2-digit',minute:'2-digit'}).format(new Date(e.start_at))}</div><h3>${esc(e.title)}</h3><p>${esc(e.description||'')}</p>${e.location?`<p>📍 ${esc(e.location)}</p>`:''}${e.join_url?`<a data-external class="btn btn-primary" href="${esc(e.join_url)}">Join / Open ↗</a>`:''}</article>`).join('')||'<div class="empty">No activities published yet.</div>';
  }
  function renderServiceTimes(){
    const el=$('#serviceTimes');if(!el)return; const rows=state.content?.service_times||[];
    el.innerHTML=rows.map(r=>`<div class="card"><strong>${esc(r.day_label)} · ${esc(r.time_label)}</strong><p>${esc(r.service_name)}</p>${r.note?`<small>${esc(r.note)}</small>`:''}</div>`).join('')||'<div class="empty">Service times have not been published yet.</div>';
  }
  function renderChurchInfo(){
    const s=state.content?.settings||{}; const el=$('#churchInfo'); if(!el)return;
    el.innerHTML=`<div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(220px,1fr))"><div class="card"><h3>📞 Call Us</h3><p>${esc(s.phone||'')}</p>${s.phone?`<a class="btn btn-primary" href="tel:${esc(s.phone.replace(/\s+/g,''))}">Call now</a>`:''}</div><div class="card"><h3>📍 Visit Us</h3><p>${esc(s.address||'')}</p></div><div class="card"><h3>🌐 Website</h3><p>${esc(s.website||'')}</p>${s.website?`<a class="btn" data-external href="${esc(s.website)}">Open website ↗</a>`:''}</div><div class="card"><h3>Facebook</h3><p>RCCG Open Heavens Fife</p>${s.facebook?`<a class="btn" data-external href="${esc(s.facebook)}">Open Facebook ↗</a>`:''}</div><div class="card"><h3>YouTube</h3><p>Church channel</p>${s.youtube?`<a class="btn" data-external href="${esc(s.youtube)}">Open YouTube ↗</a>`:''}</div><div class="card"><h3>🔵 Wednesday Bible Study</h3><p>Join the meeting in a separate browser view so the WebApp stays open.</p>${s.zoom_url?`<a class="btn btn-primary" data-external href="${esc(s.zoom_url)}">Join Zoom ↗</a>`:''}</div></div>`;
  }

  function formConfig(type){
    const configs={
      suggestion:{title:'Send a Suggestion',category:'Suggestion category',options:['Church service','Website / WebApp','Youth','Children','Discovery Class','Bible Study','Evangelism','Other']},
      pastor:{title:'Speak With the Pastor Directly',category:'Reason',options:['Prayer','Spiritual guidance','Family','Marriage','Salvation','Baptism','Bereavement','Membership','Personal matter','Other']},
      prayer:{title:'Prayer Request',category:'Privacy',options:['Private prayer request','May be shared with prayer team']},
      ministry:{title:'Join a Ministry in the Church',category:'Ministry',options:(state.content?.ministries||[]).map(x=>x.name).concat(['Other'])},
      sponsorship:{title:'Sponsor a Child',category:'Type of support',options:['Regular sponsorship','Education support','School supplies','Clothing / essentials','One-off support','More information']},
      general:{title:'Contact RCCG Open Heavens Fife',category:'Reason',options:['General enquiry','Feedback','Content correction','Other']}
    };
    return configs[type]||configs.general;
  }
  function renderForm(type){
    const cfg=formConfig(type); const el=$('#formHost'); if(!el)return;
    el.innerHTML=`<div class="breadcrumb"><a href="#/home" data-route="home">Home</a> / ${esc(cfg.title)}</div><h1 class="page-title">${esc(cfg.title)}</h1><p class="lede">Complete the form and submit it securely. Contacting the church does not add you to the subscriber list.</p><form id="contactForm" class="card form-grid" method="post" action="api/contact.php"><input type="hidden" name="form_type" value="${esc(type)}"><input type="text" name="website" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px" aria-hidden="true"><input type="hidden" name="started" value="${Date.now()-5000}"><div class="field"><label for="cfName">Full Name *</label><input id="cfName" name="name" required maxlength="80"></div><div class="form-grid two"><div class="field"><label for="cfEmail">Email Address *</label><input id="cfEmail" name="email" type="email" required maxlength="160"></div><div class="field"><label for="cfPhone">Phone Number *</label><input id="cfPhone" name="phone" type="tel" required maxlength="40"></div></div><div class="field"><label for="cfCategory">${esc(cfg.category)} *</label><select id="cfCategory" name="category" required><option value="">Choose one</option>${cfg.options.map(x=>`<option>${esc(x)}</option>`).join('')}</select></div><div class="field"><label for="cfMessage">Message *</label><textarea id="cfMessage" name="message" required maxlength="3000"></textarea></div><button class="btn btn-primary" type="submit">Submit</button><p id="contactStatus" class="status" aria-live="polite"></p></form>`;
    $$('[data-route]',el).forEach(a=>a.onclick=e=>{e.preventDefault();route(a.dataset.route)});
    $('#contactForm').addEventListener('submit',submitContact);
  }
  async function submitContact(e){e.preventDefault();const f=e.currentTarget,s=$('#contactStatus');s.textContent='Sending…';const fd=new FormData(f);try{const j=await fetch(f.action,{method:'POST',body:fd,headers:{'Accept':'application/json'}}).then(r=>r.json());s.textContent=j.message||'Submitted.';if(j.ok)f.reset()}catch{s.textContent='We could not submit this right now. Please try again.'}}

  function openDonate(){
    const d=state.content?.donation||{}; const refs=state.content?.donation_refs||[];
    $('#donateContent').innerHTML=`<h2>Donate to RCCG Open Heavens Fife</h2><p>Use your own banking app or bank. This WebApp does not collect or process card details.</p><div class="copy-row"><span>Sort Code<br><code>${esc(d.sort_code||'')}</code></span><button class="btn" data-copy="${esc(d.sort_code||'')}">Copy</button></div><div class="copy-row"><span>Account Number<br><code>${esc(d.account_number||'')}</code></span><button class="btn" data-copy="${esc(d.account_number||'')}">Copy</button></div><div class="field"><label for="donRef">Choose a payment reference</label><select id="donRef">${refs.map(r=>`<option value="${esc(r.reference_code)}">${esc(r.label)}</option>`).join('')}</select></div><div class="copy-row"><span>Reference<br><code id="donRefCode">${esc(refs[0]?.reference_code||'')}</code></span><button class="btn" id="copyRef">Copy</button></div><div class="modal-actions"><button class="btn" id="closeDonate">Close</button></div>`;
    const sel=$('#donRef'); sel.onchange=()=>$('#donRefCode').textContent=sel.value; $('#copyRef').onclick=()=>copyText(sel.value,$('#copyRef')); $('#closeDonate').onclick=()=>$('#donateModal').classList.remove('open'); $('#donateModal').classList.add('open');
  }

  function speakText(text){
    if(!('speechSynthesis'in window)){alert('Read-aloud is not supported by this browser.');return}
    speechSynthesis.cancel();const u=new SpeechSynthesisUtterance(text);u.rate=1;state.speech=u;const bar=$('#audioBar');bar.classList.add('show');$('#audioTitle').textContent='Read Aloud';$('#audioToggle').textContent='⏸';u.onend=()=>{bar.classList.remove('show');$('#audioToggle').textContent='▶'};speechSynthesis.speak(u);$('#audioToggle').onclick=()=>{if(speechSynthesis.paused){speechSynthesis.resume();$('#audioToggle').textContent='⏸'}else{speechSynthesis.pause();$('#audioToggle').textContent='▶'}};$('#audioStop').onclick=()=>{speechSynthesis.cancel();bar.classList.remove('show')}
  }
  function toggleBookmark(key,item){const b=store.get('bookmarks',{});if(b[key]){delete b[key]}else b[key]=item;store.set('bookmarks',b);toast(b[key]?'Bookmarked':'Bookmark removed')}
  function saveOfflineLesson(l){const o=store.get('offline_lessons',{});o[l.id]=l;store.set('offline_lessons',o);toast('Saved for offline reading on this device')}
  async function sharePage(title,url){try{if(navigator.share){await navigator.share({title,text:title,url})}else{await navigator.clipboard.writeText(url);toast('Link copied')}}catch(e){if(e.name!=='AbortError')toast('Unable to share right now')}}
  async function copyText(text,btn){try{await navigator.clipboard.writeText(text);const old=btn.textContent;btn.textContent='Copied';setTimeout(()=>btn.textContent=old,1500)}catch{toast('Copy failed')}}
  function toast(msg){let t=$('#toast');t.textContent=msg;t.hidden=false;clearTimeout(t._timer);t._timer=setTimeout(()=>t.hidden=true,2200)}

  let deferredPrompt; window.addEventListener('beforeinstallprompt',e=>{e.preventDefault();deferredPrompt=e;$('#installApp')?.removeAttribute('hidden')});
  async function installPwa(){if(!deferredPrompt)return;deferredPrompt.prompt();await deferredPrompt.userChoice;deferredPrompt=null;$('#installApp')?.setAttribute('hidden','')}
  function registerSW(){if('serviceWorker'in navigator)navigator.serviceWorker.register('sw.js').catch(()=>{})}

  async function updateSignupState(){try{const s=await json(API+'status.php');if(s.verified){localStorage.setItem('rccg_verified','1');$('#signupBtn').textContent=s.email?.[0]?.toUpperCase()||'✓';$('#signupBtn').title=s.email||'Verified'}else{$('#signupBtn').onclick=()=>openSignup()}}catch{}}
  function startSignupTimer(){if(localStorage.getItem('rccg_verified')==='1')return;let active=0,last=Date.now(),timer=setInterval(()=>{if(document.visibilityState==='visible'){const now=Date.now();active+=now-last;if(active>=5000&&!state.signupShown){clearInterval(timer);openSignup()};last=now}else last=Date.now()},500)}
  function openSignup(){if(localStorage.getItem('rccg_verified')==='1')return;state.signupShown=true;$('#signupModal').classList.add('open');$('#signupStarted').value=Date.now()-5000}
  $('#signupClose')?.addEventListener('click',()=>$('#signupModal').classList.remove('open'));
  $('#signupForm')?.addEventListener('submit',async e=>{e.preventDefault();const s=$('#signupStatus');s.textContent='Sending verification link…';const body={email:$('#signupEmail').value,ts:Number($('#signupStarted').value),website:$('#signupWebsite').value,page:location.href};try{const j=await json(API+'subscribe.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(body)});s.textContent=j.message||''}catch{s.textContent='Could not start verification. Please try again.'}});

  function renderOfflineFallback(){const cached=store.get('offline_lessons',{});if(Object.keys(cached).length){state.content={ok:true,discovery_lessons:Object.values(cached),events:[],service_times:[],settings:{},donation:{},donation_refs:[]};renderDiscovery()} }
  document.addEventListener('DOMContentLoaded',init);
})();
