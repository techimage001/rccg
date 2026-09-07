<?php
$private = dirname(__DIR__) . '/rccg_fife_private';
$cfg = file_exists($private.'/config.php') ? require $private.'/config.php' : ['environment'=>'testing','site_name'=>'RCCG Open Heavens Fife'];
$testing = ($cfg['environment'] ?? 'testing') !== 'production';
?><!doctype html>
<html lang="en-GB">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
  <title>RCCG Open Heavens Fife</title>
  <meta name="description" content="RCCG Open Heavens Fife devotional, Discovery Class, church events, service times, audio, notes and community links.">
  <?php if ($testing): ?><meta name="robots" content="noindex,nofollow,noarchive"><?php else: ?><meta name="robots" content="index,follow,max-image-preview:large,max-snippet:-1"><?php endif; ?>
  <meta name="theme-color" content="#062451">
  <link rel="manifest" href="site.webmanifest">
  <link rel="icon" href="icons/icon.svg" type="image/svg+xml">
  <link rel="apple-touch-icon" href="icons/icon-192.png">
  <link rel="stylesheet" href="assets/app.css?v=3">
</head>
<body>
<a class="skip" href="#main">Skip to content</a>
<div class="app">
  <header class="topbar">
    <div class="brand"><div class="brand-mark">OH</div><div class="brand-copy"><strong>RCCG Open Heavens Fife</strong><span>Grow daily in God's Word</span></div></div>
    <div class="top-actions">
      <button class="btn btn-gold top-donate" id="topDonate" type="button">❤ Donate</button>
      <button class="icon-btn" id="shareApp" aria-label="Share this WebApp" title="Share">↗</button>
      <button class="icon-btn" id="installApp" aria-label="Install or save WebApp" title="Install / save">⇩</button>
      <button class="icon-btn" id="signupBtn" aria-label="Sign up">✉</button>
      <button class="icon-btn" id="menuBtn" aria-label="Open navigation">☰</button>
    </div>
  </header>

  <div class="desktop-shell">
    <aside class="sidebar" aria-label="Main navigation">
      <div class="brand"><div class="brand-mark">OH</div><div class="brand-copy"><strong>RCCG Open Heavens Fife</strong><span>Devotional WebApp</span></div></div>
      <div class="side-search"><input aria-label="Search" placeholder="Search devotionals, lessons…"></div>
      <div class="side-group"><h3>Today</h3>
        <a class="side-link active" href="#/home" data-route="home">⌂ Home</a>
        <a class="side-link" href="#/read" data-route="read">📖 Today's Devotional</a>
        <a class="side-link" href="#/discovery" data-route="discovery">🎓 Discovery Class</a>
      </div>
      <div class="side-group"><h3>Church</h3>
        <a class="side-link" href="#/whats-on" data-route="whats-on">📅 What's On</a>
        <a class="side-link" href="#/service-times" data-route="service-times">🕒 Service Times</a>
        <a class="side-link" href="#/contact" data-route="contact">📍 Contact & Join</a>
      </div>
      <div class="side-group"><h3>Get involved</h3>
        <a class="side-link" href="#/form/prayer" data-route="form/prayer">🙏 Prayer Request</a>
        <a class="side-link" href="#/form/pastor" data-route="form/pastor">👤 Speak With Pastor Joseph</a>
        <a class="side-link" href="#/form/ministry" data-route="form/ministry">🤝 Join a Ministry</a>
        <a class="side-link" href="#/form/sponsorship" data-route="form/sponsorship">❤️ Sponsor a Child</a>
        <a class="side-link" href="#/form/suggestion" data-route="form/suggestion">💡 Suggestion</a>
      </div>
    </aside>

    <main class="content" id="main">
      <section class="page-panel active" data-page="home">
        <div class="hero" id="todayHero"><div class="eyebrow">RCCG Open Heavens Fife</div><h1>Grow daily in God's Word</h1><p>Devotionals, Discovery Class, audio, church activities and ways to connect — designed first for mobile.</p><div class="hero-actions"><button class="btn" data-route="discovery">🎓 Discovery Class</button><button class="btn" data-route="form/prayer">🙏 Prayer Request</button></div></div>
        <section class="section"><div class="section-head"><div><h2>Quick Access</h2><p class="lede">Everything important, one tap away.</p></div></div><div class="grid" id="quickGrid"></div></section>
        <section class="section"><div class="section-head"><div><h2>Next at RCCG</h2><p class="lede">The next published church activity.</p></div><button class="btn" data-route="whats-on">View all</button></div><div class="next-card" id="nextAtChurch"></div></section>
      </section>

      <section class="page-panel" data-page="read"><div id="readerContent"></div></section>

      <section class="page-panel" data-page="discovery">
        <div id="discoveryBrowse">
          <div class="breadcrumb"><a href="#/home" data-route="home">Home</a> / Discovery Class</div>
          <div class="section-head"><div><h1 class="page-title">Discovery Class</h1><p class="lede">Student and Teacher manuals, including past and future lessons from the private yearly manual folder.</p></div></div>
          <div class="subnav"><button class="btn btn-primary" id="browseStudent">Student Manual</button><button class="btn" id="browseTeacher">Teacher Manual</button><button class="btn" id="pastLessons">Past Lessons</button><button class="btn" id="futureLessons">Future Lessons</button></div>
          <div class="manual-year-row"><label for="manualYear"><strong>Manual year</strong></label><select id="manualYear" aria-label="Discovery Class manual year"></select><span id="manualSourceStatus" class="help"></span></div>
          <section class="section calendar-wrap">
            <div class="card calendar-card"><h2>Choose a lesson date</h2><p class="lede">Pick any Sunday — past, current or future.</p><input id="lessonDate" type="date"><p id="lessonDateStatus" class="help"></p><p class="help">The annual schedule shows all verified lesson dates and titles. Full Student or Teacher content opens when it has been loaded into the private manual folder.</p></div>
            <div class="toc-section">
              <div class="section-head"><div><h2>Table of Contents</h2><p class="lede">Tap any lesson to open it directly.</p></div></div>
              <div class="toc-wrap"><table class="toc-table"><thead><tr><th scope="col">Lesson</th><th scope="col">Date</th><th scope="col">Title</th></tr></thead><tbody id="lessonToc"></tbody></table></div>
            </div>
            <div><h2>Browse Lessons</h2><div class="lesson-list" id="lessonList"></div></div>
          </section>
        </div>
        <div id="lessonView" style="display:none"></div>
      </section>

      <section class="page-panel" data-page="whats-on"><div class="breadcrumb"><a href="#/home" data-route="home">Home</a> / What's On</div><div class="section-head"><div><h1 class="page-title">What's On</h1><p class="lede">Upcoming church activities, automatically surfaced from the private content database.</p></div></div><div class="section lesson-list" id="eventsList"></div></section>

      <section class="page-panel" data-page="service-times"><div class="breadcrumb"><a href="#/home" data-route="home">Home</a> / Service Times</div><div class="section-head"><div><h1 class="page-title">Service Times</h1><p class="lede">Sunday, Wednesday and monthly programme.</p></div></div><div class="section lesson-list" id="serviceTimes"></div></section>

      <section class="page-panel" data-page="contact"><div class="breadcrumb"><a href="#/home" data-route="home">Home</a> / Contact & Join</div><div class="section-head"><div><h1 class="page-title">Contact & Join</h1><p class="lede">Open external links separately so the WebApp stays open underneath.</p></div></div><div class="section" id="churchInfo"></div></section>

      <section class="page-panel" data-page="forms"><div id="formHost"></div></section>
    </main>
  </div>

  <div class="floating-actions" aria-label="Quick contact">
    <a class="whatsapp-fab" id="whatsappFab" href="#" data-external aria-label="WhatsApp RCCG Open Heavens Fife" title="WhatsApp us">💬</a>
    <button class="prayer-fab" data-route="form/prayer" type="button">🙏 Prayer Request</button>
  </div>
  <nav class="bottom-nav" aria-label="Mobile navigation">
    <button class="active" data-route="home">⌂<span>Home</span></button>
    <button data-route="read">📖<span>Read</span></button>
    <button data-route="whats-on">📅<span>What's On</span></button>
    <button data-route="discovery">🎓<span>Discovery</span></button>
    <button id="mobileMore">☰<span>More</span></button>
  </nav>

  <div class="drawer" id="drawer" aria-hidden="true"><div class="drawer-panel"><div class="drawer-head"><strong>RCCG Open Heavens Fife</strong><button class="icon-btn" id="closeDrawer">✕</button></div>
    <div class="drawer-group"><h3>Read & Grow</h3><a class="drawer-link" data-route="home" href="#/home">⌂ Home</a><a class="drawer-link" data-route="read" href="#/read">📖 Today's Devotional</a><a class="drawer-link" data-route="discovery" href="#/discovery">🎓 Discovery Class</a></div>
    <div class="drawer-group"><h3>Church</h3><a class="drawer-link" data-route="whats-on" href="#/whats-on">📅 What's On</a><a class="drawer-link" data-route="service-times" href="#/service-times">🕒 Service Times</a><a class="drawer-link" data-route="contact" href="#/contact">📍 Contact & Join</a></div>
    <div class="drawer-group"><h3>Connect</h3><a class="drawer-link" data-route="form/prayer" href="#/form/prayer">🙏 Prayer Request</a><a class="drawer-link" data-route="form/pastor" href="#/form/pastor">👤 Speak With Pastor Joseph</a><a class="drawer-link" data-route="form/ministry" href="#/form/ministry">🤝 Join a Ministry</a><a class="drawer-link" data-route="form/sponsorship" href="#/form/sponsorship">❤️ Sponsor a Child</a><a class="drawer-link" data-route="form/suggestion" href="#/form/suggestion">💡 Send a Suggestion</a></div>
    <div class="drawer-group"><h3>WebApp</h3><button class="drawer-link drawer-button" id="drawerInstall" type="button">⇩ Install / Save App</button><button class="drawer-link drawer-button" id="drawerSignup" type="button">✉ Stay Connected</button></div>
  </div></div>

  <div class="audio-bar" id="audioBar"><button class="icon-btn" id="audioToggle">⏸</button><strong id="audioTitle">Read Aloud</strong><div class="progress"><span></span></div><button class="icon-btn" id="audioStop">■</button></div>
  <div id="toast" hidden style="position:fixed;left:50%;bottom:120px;transform:translateX(-50%);z-index:160;background:#062451;color:#fff;padding:10px 14px;border-radius:999px;box-shadow:var(--shadow)"></div>

  <div class="modal" id="donateModal"><div class="modal-card" id="donateContent"></div></div>
  <div class="modal" id="installModal"><div class="modal-card"><h2>Install or save RCCG Open Heavens Fife</h2><div id="installHelp"></div><div class="modal-actions"><button class="btn" id="closeInstall">Close</button></div></div></div>
  <div class="modal" id="signupModal"><div class="modal-card"><h2>Stay connected with RCCG Open Heavens Fife</h2><p>Receive devotional and important church updates. Verify your email to keep this browser recognised.</p><form id="signupForm" class="form-grid"><input id="signupWebsite" name="company" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px"><input id="signupStarted" type="hidden"><div class="field"><label for="signupEmail">Email address</label><input id="signupEmail" type="email" required></div><button class="btn btn-primary" type="submit">Continue</button><p id="signupStatus" class="status" aria-live="polite"></p></form><div class="modal-actions"><button class="btn" id="signupClose">Not now</button></div></div></div>
</div>
<script src="assets/app.js?v=3" defer></script>
<script>document.addEventListener('DOMContentLoaded',()=>{document.getElementById('mobileMore')?.addEventListener('click',()=>document.getElementById('drawer').classList.add('open'));document.querySelectorAll('[data-route]').forEach(()=>{});});</script>
</body>
</html>
