<?php
$dbFile = __DIR__ . '/rccg.sqlite';
$db = new PDO('sqlite:' . $dbFile);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->exec('PRAGMA journal_mode=WAL; PRAGMA foreign_keys=ON;');
$db->exec(<<<SQL
CREATE TABLE IF NOT EXISTS settings (key TEXT PRIMARY KEY, value TEXT NOT NULL DEFAULT '');
CREATE TABLE IF NOT EXISTS service_times (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  sort_order INTEGER NOT NULL DEFAULT 0,
  day_label TEXT NOT NULL,
  time_label TEXT NOT NULL,
  service_name TEXT NOT NULL,
  note TEXT NOT NULL DEFAULT '',
  active INTEGER NOT NULL DEFAULT 1
);
CREATE TABLE IF NOT EXISTS events (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  title TEXT NOT NULL,
  start_at TEXT NOT NULL,
  end_at TEXT NOT NULL DEFAULT '',
  description TEXT NOT NULL DEFAULT '',
  location TEXT NOT NULL DEFAULT '',
  join_url TEXT NOT NULL DEFAULT '',
  featured INTEGER NOT NULL DEFAULT 0,
  active INTEGER NOT NULL DEFAULT 1
);
CREATE TABLE IF NOT EXISTS discovery_lessons (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  lesson_number INTEGER NOT NULL,
  lesson_date TEXT NOT NULL UNIQUE,
  title TEXT NOT NULL,
  memory_verse TEXT NOT NULL DEFAULT '',
  bible_passage TEXT NOT NULL DEFAULT '',
  introduction TEXT NOT NULL DEFAULT '',
  student_content TEXT NOT NULL DEFAULT '',
  teacher_content TEXT NOT NULL DEFAULT '',
  class_activity TEXT NOT NULL DEFAULT '',
  discussion_questions TEXT NOT NULL DEFAULT '',
  conclusion TEXT NOT NULL DEFAULT '',
  published INTEGER NOT NULL DEFAULT 1
);
CREATE TABLE IF NOT EXISTS devotionals (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  devotional_date TEXT NOT NULL UNIQUE,
  title TEXT NOT NULL,
  summary TEXT NOT NULL DEFAULT '',
  memory_verse TEXT NOT NULL DEFAULT '',
  bible_reading TEXT NOT NULL DEFAULT '',
  body TEXT NOT NULL DEFAULT '',
  published INTEGER NOT NULL DEFAULT 1
);
CREATE TABLE IF NOT EXISTS ministries (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL UNIQUE, active INTEGER NOT NULL DEFAULT 1);
CREATE TABLE IF NOT EXISTS donation_refs (id INTEGER PRIMARY KEY AUTOINCREMENT, label TEXT NOT NULL, reference_code TEXT NOT NULL, active INTEGER NOT NULL DEFAULT 1, sort_order INTEGER NOT NULL DEFAULT 0);
CREATE TABLE IF NOT EXISTS contacts (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  form_type TEXT NOT NULL,
  name TEXT NOT NULL,
  email TEXT NOT NULL,
  phone TEXT NOT NULL,
  category TEXT NOT NULL DEFAULT '',
  message TEXT NOT NULL,
  page TEXT NOT NULL DEFAULT '',
  created_at TEXT NOT NULL,
  ip_hash TEXT NOT NULL DEFAULT '',
  sent INTEGER NOT NULL DEFAULT 0,
  status TEXT NOT NULL DEFAULT 'new'
);
CREATE TABLE IF NOT EXISTS subscribers (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  email TEXT NOT NULL UNIQUE,
  token_hash TEXT NOT NULL,
  created_at TEXT NOT NULL,
  updated_at TEXT NOT NULL,
  verified_at TEXT,
  token_expires_at TEXT NOT NULL,
  unsubscribe_hash TEXT NOT NULL,
  page TEXT NOT NULL DEFAULT '',
  ip_hash TEXT NOT NULL DEFAULT ''
);
CREATE TABLE IF NOT EXISTS access_sessions (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  session_hash TEXT NOT NULL UNIQUE,
  email TEXT NOT NULL,
  created_at TEXT NOT NULL,
  expires_at TEXT NOT NULL
);
CREATE TABLE IF NOT EXISTS rate_limits (id INTEGER PRIMARY KEY AUTOINCREMENT, ip_hash TEXT NOT NULL, action TEXT NOT NULL, created_at TEXT NOT NULL);
SQL);

function upsertSetting(PDO $db, string $k, string $v): void {
    $s=$db->prepare('INSERT INTO settings(key,value) VALUES(?,?) ON CONFLICT(key) DO UPDATE SET value=excluded.value');
    $s->execute([$k,$v]);
}
$settings = [
 'phone'=>'07955 527798',
 'address'=>'2 Victoria Gardens, Kirkcaldy KY1 1DJ',
 'website'=>'https://www.kirkcaldyrccg.co.uk/',
 'facebook'=>'https://www.facebook.com/p/Redeemed-Christian-Church-of-God-Open-Heavens-FIFE-100064830753181/',
 'youtube'=>'https://www.youtube.com/channel/UC0j0DD8Rryt6h6OiLhJeWEQ',
 'zoom_url'=>'https://us02web.zoom.us/j/8741131420?pwd=UXpqdHhvNUdDWDNObmdQTFAyK25FUT09',
 'donation_sort_code'=>'80-12-71',
 'donation_account_number'=>'06000981'
];
foreach($settings as $k=>$v) upsertSetting($db,$k,$v);

if ((int)$db->query('SELECT COUNT(*) FROM service_times')->fetchColumn()===0) {
  $rows=[
   [10,'Sunday','10:30–11:00','Morning Prayers',''],
   [20,'Sunday','11:00–11:30','Discovery Class','Student and Teacher manuals available in the WebApp.'],
   [30,'Sunday','11:30–13:00','Celebration Service / Kids Church',''],
   [40,'Wednesday','19:00','Prayer Meeting',''],
   [50,'Wednesday','19:00–20:00','Digging Deep – Bible Study','No Bible study on the 3rd Wednesday of the month.'],
   [60,'Last Sunday','19:00–20:00','Holy Communion','Every last Sunday of the month.']
  ];
  $s=$db->prepare('INSERT INTO service_times(sort_order,day_label,time_label,service_name,note) VALUES(?,?,?,?,?)'); foreach($rows as $r)$s->execute($r);
}
if ((int)$db->query('SELECT COUNT(*) FROM events')->fetchColumn()===0) {
  $now=new DateTimeImmutable('now',new DateTimeZone('Europe/London'));
  $nextWed=$now->modify('next wednesday')->setTime(19,0);
  $nextSun=$now->modify('next sunday')->setTime(10,30);
  $s=$db->prepare('INSERT INTO events(title,start_at,end_at,description,location,join_url,featured) VALUES(?,?,?,?,?,?,?)');
  $s->execute(['Prayer Meeting & Digging Deep',$nextWed->format(DATE_ATOM),$nextWed->modify('+1 hour')->format(DATE_ATOM),'Wednesday prayer and Bible study.','Online / Church',$settings['zoom_url'],1]);
  $s->execute(['Sunday Celebration Service',$nextSun->format(DATE_ATOM),$nextSun->setTime(13,0)->format(DATE_ATOM),'Morning prayers, Discovery Class and Celebration Service.','2 Victoria Gardens, Kirkcaldy KY1 1DJ','',1]);
}
if ((int)$db->query('SELECT COUNT(*) FROM discovery_lessons')->fetchColumn()===0) {
  $base=new DateTimeImmutable('last sunday',new DateTimeZone('Europe/London'));
  $s=$db->prepare('INSERT INTO discovery_lessons(lesson_number,lesson_date,title,memory_verse,bible_passage,introduction,student_content,teacher_content,class_activity,discussion_questions,conclusion,published) VALUES(?,?,?,?,?,?,?,?,?,?,?,1)');
  for($i=-2;$i<=5;$i++){
    $d=$base->modify(($i>=0?'+':'').$i.' week');
    $n=$i+3;
    $title=['Growing in Grace','Walking in Wisdom','A Life Led by the Holy Spirit','Faith That Works','Living a Thankful Life','The Power of Prayer','Serving with Joy','Standing Firm in Faith'][$n-1]??('Discovery Class Lesson '.$n);
    $s->execute([$n,$d->format('Y-m-d'),$title,'Sample memory verse placeholder for authorised lesson content.','Sample Bible passage reference.','This is placeholder lesson material for testing. Replace it in Admin with authorised RCCG Discovery Class content.','Student outline placeholder: clear, readable sections for study and discussion.','Teacher guidance placeholder: objectives, prompts, answers and facilitation notes can appear here.','Discuss one practical way to apply the lesson this week.','1. What did you learn? 2. How will you apply it?','Summarise the lesson and close with prayer.']);
  }
}
if ((int)$db->query('SELECT COUNT(*) FROM devotionals')->fetchColumn()===0) {
  $today=(new DateTimeImmutable('now',new DateTimeZone('Europe/London')))->format('Y-m-d');
  $s=$db->prepare('INSERT INTO devotionals(devotional_date,title,summary,memory_verse,bible_reading,body,published) VALUES(?,?,?,?,?,?,1)');
  $s->execute([$today,'A Life Led by the Holy Spirit','A calm daily reading experience with audio, bookmarks and offline support.','Romans 8:14 — For as many as are led by the Spirit of God, they are the children of God.','Romans 8:12–17','This is original placeholder devotional copy for testing the WebApp. Replace it with content the church is authorised to publish.']);
}
if ((int)$db->query('SELECT COUNT(*) FROM ministries')->fetchColumn()===0) {
  foreach(['Choir','Ushering','Children’s Ministry','Youth Ministry','Media / Technical','Evangelism','Hospitality','Prayer Team','Welfare','Discovery Class','House Fellowship'] as $m){$s=$db->prepare('INSERT INTO ministries(name) VALUES(?)');$s->execute([$m]);}
}
if ((int)$db->query('SELECT COUNT(*) FROM donation_refs')->fetchColumn()===0) {
  $refs=[['Tithe','TITHE'],['Offering','OFFERING'],['Free Will Donation','FREEWILL'],['Thanksgiving Offering','THANKSGIVING'],['Church Project','PROJECT'],['Missions / Evangelism','MISSIONS'],['Children / Youth Ministry','YOUTH'],['Welfare / Community Support','WELFARE'],['Special Programme','SPECIAL'],['Other','OTHER']];
  $s=$db->prepare('INSERT INTO donation_refs(label,reference_code,sort_order) VALUES(?,?,?)');$i=0;foreach($refs as $r)$s->execute([$r[0],$r[1],++$i]);
}
echo "Database ready: {$dbFile}\n";
