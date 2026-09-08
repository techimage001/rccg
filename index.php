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
  <meta name="description" content="RCCG Open Heavens Fife church WebApp: Discovery Class Teacher Manual, events, service times, prayer requests, first-time worshipper feedback and ways to connect.">
  <?php if ($testing): ?><meta name="robots" content="noindex,nofollow,noarchive"><?php else: ?><meta name="robots" content="index,follow,max-image-preview:large,max-snippet:-1"><?php endif; ?>
  <meta name="theme-color" content="#062451">
  <link rel="manifest" href="site.webmanifest">
  <link rel="icon" href="icons/icon.svg" type="image/svg+xml">
  <link rel="apple-touch-icon" href="icons/icon-192.png">
  <link rel="stylesheet" href="assets/app.css?v=9">
</head>
<body>
<a class="skip" href="#main">Skip to content</a>
<div class="app">
  <header class="topbar">
    <a class="brand brand-home" href="#/home" data-route="home" aria-label="RCCG Open Heavens Fife — Home"><div class="brand-mark">OH</div><div class="brand-copy"><strong>RCCG Open Heavens Fife</strong><span>Church WebApp</span></div></a>
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
      <a class="brand brand-home" href="#/home" data-route="home" aria-label="RCCG Open Heavens Fife — Home"><div class="brand-mark">OH</div><div class="brand-copy"><strong>RCCG Open Heavens Fife</strong><span>Church WebApp</span></div></a>
      <div class="side-group"><h3>Main</h3>
        <a class="side-link active" href="#/home" data-route="home">⌂ Home</a>
        <a class="side-link" href="#/discovery" data-route="discovery">🎓 Discovery Class Teacher Manual</a>
        <a class="side-link" href="#/whats-on" data-route="whats-on">📅 What's On</a>
        <a class="side-link" href="#/service-times" data-route="service-times">🕒 Service Times</a>
        <a class="side-link" href="#/contact" data-route="contact">📍 Contact & Join</a>
      </div>
      <div class="side-group"><h3>Connect</h3><div id="sideConnectLinks"></div></div>
      <button class="btn btn-gold side-donate" id="desktopDonate" type="button">❤ Donate</button>
    </aside>

    <main class="content" id="main">
      <section class="page-panel active" data-page="home">
        <div class="hero" id="todayHero"><div class="eyebrow">RCCG Open Heavens Fife</div><h1>Welcome to our church WebApp</h1><p>Discovery Class Teacher Manual, church activities, service times, prayer support and simple ways to stay connected.</p><div class="hero-actions"><button class="btn" data-route="discovery">🎓 Discovery Class Teacher Manual</button><button class="btn" data-route="form/worshipper">👋 First-Time Worshipper</button><button class="btn" data-route="form/prayer">🙏 Prayer Request</button></div></div>
        <section class="section"><div class="section-head"><div><h2>Quick Access</h2><p class="lede">Everything currently available, one tap away.</p></div></div><div class="grid" id="quickGrid"></div></section>
        <section class="section first-visit-callout"><div class="card"><div><div class="eyebrow">New here?</div><h2>First time with us?</h2><p>Tell us about your visit, share a prayer point and let us know whether we may contact you.</p></div><button class="btn btn-primary" data-route="form/worshipper">Tell us about your visit</button></div></section>
        <section class="section"><div class="section-head"><div><h2>Next at RCCG</h2><p class="lede">The next published church activity.</p></div><button class="btn" data-route="whats-on">View all</button></div><div class="next-card" id="nextAtChurch"></div></section>
      </section>

      <section class="page-panel" data-page="discovery">
        <div id="discoveryBrowse">
          <div class="breadcrumb"><a href="#/home" data-route="home">Home</a> / Discovery Class Teacher Manual</div>
          <div class="section-head"><div><h1 class="page-title">Discovery Class Teacher Manual</h1><p class="lede">Browse the 2026–2027 Discovery Class Teacher Manual by week, including past, current and future lessons.</p></div></div>
          <div class="subnav lesson-filters" aria-label="Discovery Class Teacher Manual lesson filters"><button class="btn" id="allLessons" type="button">All Lessons</button><button class="btn" id="pastLessons" type="button">Past Lessons</button><button class="btn btn-primary" id="thisWeekLesson" type="button">This Week</button><button class="btn" id="futureLessons" type="button">Future Lessons</button></div>
          <div class="manual-year-row"><label for="manualYear"><strong>Manual year</strong></label><select id="manualYear" aria-label="Discovery Class Teacher Manual year"></select><span id="manualSourceStatus" class="help"></span></div>
          <section class="section calendar-wrap">
            <div class="card calendar-card"><h2>Choose a lesson date</h2><p class="lede">Pick a scheduled Sunday.</p><input id="lessonDate" type="date"><p id="lessonDateStatus" class="help"></p><p class="help">Only authorised Discovery Class Teacher Manual content supplied for this WebApp is displayed.</p></div>
            <div class="toc-section">
              <div class="section-head"><div><h2>Table of Contents</h2><p class="help" id="lessonFilterStatus" aria-live="polite"></p></div></div>
              <div class="toc-wrap"><table class="toc-table"><thead><tr><th scope="col">Lesson</th><th scope="col">Date</th><th scope="col">Title</th></tr></thead><tbody id="lessonToc"></tbody></table></div>
            </div>
          </section>
        </div>
        <div id="lessonView" style="display:none"></div>
      </section>

      <section class="page-panel" data-page="whats-on"><div class="breadcrumb"><a href="#/home" data-route="home">Home</a> / What's On</div><div class="section-head"><div><h1 class="page-title">What's On</h1><p class="lede">Browse church activities by month. Times use Scotland/UK local time and follow BST/GMT automatically.</p></div></div><div class="event-month-controls"><button class="btn" id="eventPrevMonth" type="button" aria-label="Previous month">←</button><label for="eventMonth"><strong>Month</strong></label><input id="eventMonth" type="month"><button class="btn" id="eventNextMonth" type="button" aria-label="Next month">→</button><button class="btn" id="eventThisMonth" type="button">This month</button></div><div class="section lesson-list" id="eventsList"></div></section>

      <section class="page-panel" data-page="service-times"><div class="breadcrumb"><a href="#/home" data-route="home">Home</a> / Service Times</div><div class="section-head"><div><h1 class="page-title">Service Times</h1><p class="lede">Sunday, Wednesday and monthly programme.</p></div></div><div class="section lesson-list" id="serviceTimes"></div></section>

      <section class="page-panel" data-page="contact"><div class="breadcrumb"><a href="#/home" data-route="home">Home</a> / Contact & Join</div><div class="section-head"><div><h1 class="page-title">Contact & Join</h1><p class="lede">Open external links separately so the WebApp stays open underneath.</p></div></div><div class="section" id="churchInfo"></div></section>

      <section class="page-panel" data-page="forms"><div id="formHost"></div></section>
    </main>
  </div>

  <div class="floating-actions" aria-label="Quick contact">
    <a class="whatsapp-fab" id="whatsappFab" href="#" data-external aria-label="Chat with Pastor Joseph on WhatsApp" title="Chat with Pastor Joseph on WhatsApp"><span aria-hidden="true">💬</span><span>Chat with Pastor Joseph</span></a>
    <button class="prayer-fab" data-route="form/prayer" type="button">🙏 Prayer Request</button>
  </div>
  <nav class="bottom-nav" aria-label="Mobile navigation">
    <button class="active" data-route="home">⌂<span>Home</span></button>
    <button data-route="discovery">🎓<span>Manual</span></button>
    <button data-route="whats-on">📅<span>What's On</span></button>
    <button data-route="form/worshipper">👋<span>New Here?</span></button>
    <button id="mobileMore">☰<span>More</span></button>
  </nav>

  <div class="drawer" id="drawer" aria-hidden="true"><div class="drawer-panel"><div class="drawer-head"><strong>RCCG Open Heavens Fife</strong><button class="icon-btn" id="closeDrawer">✕</button></div>
    <div class="drawer-group"><h3>Main</h3><a class="drawer-link" data-route="home" href="#/home">⌂ Home</a><a class="drawer-link" data-route="discovery" href="#/discovery">🎓 Discovery Class Teacher Manual</a><a class="drawer-link" data-route="whats-on" href="#/whats-on">📅 What's On</a><a class="drawer-link" data-route="service-times" href="#/service-times">🕒 Service Times</a><a class="drawer-link" data-route="contact" href="#/contact">📍 Contact & Join</a></div>
    <div class="drawer-group"><h3>Connect</h3><div id="drawerConnectLinks"></div></div>
    <div class="drawer-group"><h3>WebApp</h3><button class="drawer-link drawer-button" id="drawerInstall" type="button">⇩ Install / Save App</button><button class="drawer-link drawer-button" id="drawerSignup" type="button">✉ Stay Connected</button></div>
  </div></div>

  <div class="audio-bar" id="audioBar"><button class="icon-btn" id="audioToggle">⏸</button><strong id="audioTitle">Read Aloud</strong><div class="progress"><span></span></div><button class="icon-btn" id="audioStop">■</button></div>
  <div id="toast" hidden style="position:fixed;left:50%;bottom:120px;transform:translateX(-50%);z-index:160;background:#062451;color:#fff;padding:10px 14px;border-radius:999px;box-shadow:var(--shadow)"></div>

  <div class="modal" id="donateModal"><div class="modal-card" id="donateContent"></div></div>
  <div class="modal" id="discardModal"><div class="modal-card"><h2>Leave this form?</h2><p>You have already started filling this form in. If you leave now, what you have typed will not be saved or sent to the church.</p><div class="modal-actions"><button class="btn btn-primary" id="keepEditing" type="button">Keep Editing</button><button class="btn btn-danger" id="discardForm" type="button">Discard Form</button></div></div></div>
  <div class="modal" id="installModal"><div class="modal-card"><h2>Install or save RCCG Open Heavens Fife</h2><div id="installHelp"></div><div class="modal-actions"><button class="btn" id="closeInstall">Close</button></div></div></div>
  <div class="modal" id="signupModal"><div class="modal-card"><h2>Stay connected with RCCG Open Heavens Fife</h2><p>Receive important church updates. Verify your email to keep this browser recognised.</p><form id="signupForm" class="form-grid"><input id="signupWebsite" name="company" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px"><input id="signupStarted" type="hidden"><div class="field"><label for="signupEmail">Email address</label><input id="signupEmail" type="email" required></div><button class="btn btn-primary" type="submit">Continue</button><p id="signupStatus" class="status" aria-live="polite"></p></form><div class="modal-actions"><button class="btn" id="signupClose">Not now</button></div></div></div>
</div>
<script src="assets/app.js?v=9" defer></script>
</body>
</html>
