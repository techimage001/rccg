<?php
require __DIR__.'/bootstrap.php';

$settings=[];foreach($DB->query('SELECT key,value FROM settings') as $r)$settings[$r['key']]=$r['value'];
$tzName=$settings['timezone']??'Europe/London';
$tz=new DateTimeZone($tzName);
$now=new DateTimeImmutable('now',$tz);
$today=$now->format('Y-m-d');

$services=$DB->query('SELECT * FROM service_times WHERE active=1 ORDER BY sort_order,id')->fetchAll();
$dbEvents=$DB->query('SELECT * FROM events WHERE active=1 ORDER BY start_at ASC')->fetchAll();
$rules=[];
try { $rules=$DB->query('SELECT * FROM recurring_events WHERE active=1 ORDER BY sort_order,id')->fetchAll(); } catch (Throwable $e) { $rules=[]; }
$mins=$DB->query('SELECT * FROM ministries WHERE active=1 ORDER BY name')->fetchAll();
$refs=$DB->query('SELECT * FROM donation_refs WHERE active=1 ORDER BY sort_order,id')->fetchAll();

function nthWeekdayOfMonth(DateTimeImmutable $d): int { return intdiv(((int)$d->format('j'))-1,7)+1; }
function lastWeekdayOfMonth(DateTimeImmutable $d): bool { return $d->modify('+7 days')->format('n')!==$d->format('n'); }
function isoLocal(DateTimeImmutable $d): string { return $d->format('Y-m-d\TH:i:sP'); }
function parseWeekdays(string $value): array {
  $out=[];
  foreach(explode(',',$value) as $part){
    $n=(int)trim($part);
    if($n>=1 && $n<=7) $out[$n]=true;
  }
  return array_keys($out);
}
function atLocalTime(DateTimeImmutable $day,string $time): DateTimeImmutable {
  $parts=explode(':',$time);
  $h=(int)($parts[0]??0);$m=(int)($parts[1]??0);$s=(int)($parts[2]??0);
  return $day->setTime($h,$m,$s);
}
function resolveSetting(array $settings,string $settingKey,string $literal=''): string {
  if($settingKey!=='' && array_key_exists($settingKey,$settings)) return (string)$settings[$settingKey];
  return $literal;
}

// All RCCG-specific recurring schedule values live in the private SQLite database.
// This public endpoint contains only a generic recurrence renderer.
$regular=[];
for($i=-365;$i<=730;$i++){
  $day=$now->setTime(0,0)->modify(($i>=0?'+':'').$i.' days');
  $weekday=(int)$day->format('N');
  foreach($rules as $rule){
    $weekdays=parseWeekdays((string)($rule['weekdays']??''));
    if($weekdays && !in_array($weekday,$weekdays,true)) continue;

    $type=(string)($rule['recurrence_type']??'weekly');
    if($type==='monthly_last_weekday' && !lastWeekdayOfMonth($day)) continue;
    if(!in_array($type,['weekly','weekdays','monthly_last_weekday'],true)) continue;

    $title=(string)$rule['title'];
    $description=(string)($rule['description']??'');
    $endTime=(string)($rule['end_time']??'');
    $exceptionNth=(int)($rule['exception_nth']??0);
    if($exceptionNth>0 && nthWeekdayOfMonth($day)===$exceptionNth){
      if((string)($rule['exception_title']??'')!=='') $title=(string)$rule['exception_title'];
      if((string)($rule['exception_description']??'')!=='') $description=(string)$rule['exception_description'];
      if((string)($rule['exception_end_time']??'')!=='') $endTime=(string)$rule['exception_end_time'];
    }

    $start=atLocalTime($day,(string)$rule['start_time']);
    $end=$endTime!=='' ? atLocalTime($day,$endTime) : null;
    $location=resolveSetting($settings,(string)($rule['location_setting']??''),(string)($rule['location_text']??''));
    $joinUrl=resolveSetting($settings,(string)($rule['join_setting']??''),(string)($rule['join_url']??''));

    $regular[]=[
      'id'=>'rec-'.($rule['slug']??$rule['id']).'-'.$day->format('Ymd'),
      'title'=>$title,
      'start_at'=>isoLocal($start),
      'end_at'=>$end ? isoLocal($end) : '',
      'description'=>$description,
      'location'=>$location,
      'join_url'=>$joinUrl,
      'featured'=>(int)($rule['featured']??0),
      'active'=>1,
      'source'=>'recurring',
      'show_live_links'=>(int)($rule['show_live_links']??0)
    ];
  }
}
$events=array_merge($dbEvents,$regular);
usort($events,fn($a,$b)=>strcmp((string)$a['start_at'],(string)$b['start_at']));

$quickAccessServices=[];$joinableServices=[];$contactServices=[];
foreach($rules as $rule){
  $joinUrl=resolveSetting($settings,(string)($rule['join_setting']??''),(string)($rule['join_url']??''));
  $item=[
    'slug'=>(string)($rule['slug']??''),
    'title'=>(string)($rule['title']??''),
    'icon'=>(string)($rule['quick_icon']??'🕒'),
    'schedule_label'=>(string)($rule['schedule_label']??''),
    'join_url'=>$joinUrl,
    'sort_order'=>(int)($rule['sort_order']??0)
  ];
  if((int)($rule['quick_access']??0)===1) $quickAccessServices[]=$item;
  if($joinUrl!=='') $joinableServices[]=$item;
  if($joinUrl!=='' && (int)($rule['contact_card']??0)===1) $contactServices[]=$item;
}

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
  'service_times'=>$services,
  'events'=>$events,
  'quick_access_services'=>$quickAccessServices,
  'joinable_services'=>$joinableServices,
  'contact_services'=>$contactServices,
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
