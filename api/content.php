<?php
require __DIR__.'/bootstrap.php';

$settings=[];foreach($DB->query('SELECT key,value FROM settings') as $r)$settings[$r['key']]=$r['value'];
$tzName=$settings['timezone']??'Europe/London';
$tz=new DateTimeZone($tzName);
$now=new DateTimeImmutable('now',$tz);
$today=$now->format('Y-m-d');

$s=$DB->prepare('SELECT * FROM devotionals WHERE published=1 AND devotional_date<=? ORDER BY devotional_date DESC LIMIT 1');
$s->execute([$today]);$dev=$s->fetch()?:null;
$services=$DB->query('SELECT * FROM service_times WHERE active=1 ORDER BY sort_order,id')->fetchAll();
$dbEvents=$DB->query('SELECT * FROM events WHERE active=1 ORDER BY start_at ASC')->fetchAll();
$mins=$DB->query('SELECT * FROM ministries WHERE active=1 ORDER BY name')->fetchAll();
$refs=$DB->query('SELECT * FROM donation_refs WHERE active=1 ORDER BY sort_order,id')->fetchAll();

function nthWeekdayOfMonth(DateTimeImmutable $d): int { return intdiv(((int)$d->format('j'))-1,7)+1; }
function lastSundayOfMonth(DateTimeImmutable $d): bool { return $d->format('N')==='7' && $d->modify('+7 days')->format('n')!==$d->format('n'); }
function isoLocal(DateTimeImmutable $d): string { return $d->format('Y-m-d\TH:i:sP'); }

// Generate a rolling multi-year church calendar in Europe/London so BST/GMT changes are automatic.
// One year of history plus two years ahead lets the month picker browse well beyond the current month.
$regular=[];
for($i=-365;$i<=730;$i++){
  $day=$now->setTime(0,0)->modify(($i>=0?'+':'').$i.' days');
  if($day->format('N')==='3'){
    $third=nthWeekdayOfMonth($day)===3;
    $start=$day->setTime(19,0);
    $regular[]=[
      'id'=>'reg-wed-'.$day->format('Ymd'),
      'title'=>$third?'Prayer Meeting':'Prayer Meeting & Digging Deep',
      'start_at'=>isoLocal($start),
      'end_at'=>isoLocal($start->modify('+1 hour')),
      'description'=>$third?'Prayer Meeting at 19:00. There is no Bible Study on the 3rd Wednesday of the month.':'Prayer Meeting at 19:00 and Digging Deep Bible Study from 19:00–20:00.',
      'location'=>'Church / Online',
      'join_url'=>$settings['zoom_url']??'',
      'featured'=>1,
      'active'=>1,
      'source'=>'regular'
    ];
  }
  if($day->format('N')==='7'){
    $start=$day->setTime(10,30);
    $regular[]=[
      'id'=>'reg-sun-'.$day->format('Ymd'),
      'title'=>'Sunday Services',
      'start_at'=>isoLocal($start),
      'end_at'=>isoLocal($day->setTime(13,0)),
      'description'=>'Morning Prayers 10:30–11:00, Discovery Class 11:00–11:30, Celebration Service / Kids Church 11:30–13:00.',
      'location'=>$settings['address']??'',
      'join_url'=>'',
      'featured'=>1,
      'active'=>1,
      'source'=>'regular'
    ];
    if(lastSundayOfMonth($day)){
      $hc=$day->setTime(19,0);
      $regular[]=[
        'id'=>'reg-hc-'.$day->format('Ymd'),
        'title'=>'Holy Communion',
        'start_at'=>isoLocal($hc),
        'end_at'=>isoLocal($hc->modify('+1 hour')),
        'description'=>'Holy Communion on the last Sunday of the month, 19:00–20:00.',
        'location'=>$settings['address']??'',
        'join_url'=>'',
        'featured'=>1,
        'active'=>1,
        'source'=>'regular'
      ];
    }
  }
}
$events=array_merge($dbEvents,$regular);
usort($events,fn($a,$b)=>strcmp((string)$a['start_at'],(string)$b['start_at']));

// Discovery Class schedule lives outside public_html in yearly folders.
$discoveryRoot=$PRIVATE.'/discovery_class';
$activeYear='';
if(file_exists($discoveryRoot.'/active-year.txt')) $activeYear=trim((string)file_get_contents($discoveryRoot.'/active-year.txt'));
$years=[];
if(is_dir($discoveryRoot)){
  foreach(scandir($discoveryRoot)?:[] as $name){
    if(preg_match('/^\d{4}-\d{4}$/',$name) && is_dir($discoveryRoot.'/'.$name)) $years[]=$name;
  }
}
sort($years);
if(!$activeYear && $years) $activeYear=end($years);
$requestedYear=preg_replace('/[^0-9-]/','',(string)($_GET['year']??''));
if($requestedYear && in_array($requestedYear,$years,true)) $activeYear=$requestedYear;
$lessons=[];$manualMeta=null;
if($activeYear){
  $mf=$discoveryRoot.'/'.$activeYear.'/manifest.json';
  if(file_exists($mf)){
    $manualMeta=json_decode((string)file_get_contents($mf),true);
    if(is_array($manualMeta)){
      $lessons=$manualMeta['lessons']??[];
      foreach($lessons as &$l){
        $n=str_pad((string)($l['lesson_number']??0),2,'0',STR_PAD_LEFT);
        $l['student_available']=file_exists($discoveryRoot.'/'.$activeYear.'/student/lesson-'.$n.'.json');
        $l['teacher_available']=file_exists($discoveryRoot.'/'.$activeYear.'/teacher/lesson-'.$n.'.json');
      }
      unset($l);
    }
  }
}

api_json([
  'ok'=>true,
  'server_now'=>isoLocal($now),
  'timezone'=>$tzName,
  'settings'=>$settings,
  'today_devotional'=>$dev,
  'service_times'=>$services,
  'events'=>$events,
  'discovery_lessons'=>$lessons,
  'manual_years'=>$years,
  'active_manual_year'=>$activeYear,
  'manual_meta'=>$manualMeta ? [
    'manual_year'=>$manualMeta['manual_year']??$activeYear,
    'manual_type'=>$manualMeta['manual_type']??'Adult',
    'source_status'=>$manualMeta['source_status']??''
  ] : null,
  'ministries'=>$mins,
  'donation'=>[
    'sort_code'=>$settings['donation_sort_code']??'',
    'account_number'=>$settings['donation_account_number']??''
  ],
  'donation_refs'=>$refs
]);
