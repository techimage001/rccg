<?php
require __DIR__.'/bootstrap.php';
$settings=[];foreach($DB->query('SELECT key,value FROM settings') as $r)$settings[$r['key']]=$r['value'];
$tzName=$settings['timezone']??'Europe/London';$tz=new DateTimeZone($tzName);$now=new DateTimeImmutable('now',$tz);
$services=$DB->query('SELECT * FROM service_times WHERE active=1 ORDER BY sort_order,id')->fetchAll();
$dbEvents=$DB->query('SELECT * FROM events WHERE active=1 ORDER BY start_at ASC')->fetchAll();
$mins=$DB->query('SELECT * FROM ministries WHERE active=1 ORDER BY name')->fetchAll();
$refs=$DB->query('SELECT * FROM donation_refs WHERE active=1 ORDER BY sort_order,id')->fetchAll();
function table_exists(PDO $db,string $name): bool {$s=$db->prepare("SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name=?");$s->execute([$name]);return (int)$s->fetchColumn()>0;}
function nthWeekdayOfMonth(DateTimeImmutable $d): int {return intdiv(((int)$d->format('j'))-1,7)+1;}
function lastWeekdayOfMonth(DateTimeImmutable $d): bool {return $d->modify('+7 days')->format('n')!==$d->format('n');}
function isoLocal(DateTimeImmutable $d): string {return $d->format('Y-m-d\TH:i:sP');}
function parseClock(DateTimeImmutable $day,string $clock): DateTimeImmutable {$p=array_map('intval',explode(':',$clock));return $day->setTime($p[0]??0,$p[1]??0);}
$regular=[];
if(table_exists($DB,'recurring_events')){
  $rules=$DB->query('SELECT * FROM recurring_events WHERE active=1 ORDER BY sort_order,id')->fetchAll();
  for($i=-365;$i<=730;$i++){
    $day=$now->setTime(0,0)->modify(($i>=0?'+':'').$i.' days');$weekday=(int)$day->format('N');$nth=nthWeekdayOfMonth($day);
    foreach($rules as $r){
      $weekdays=array_values(array_filter(array_map('intval',explode(',',(string)$r['weekdays']))));if($weekdays && !in_array($weekday,$weekdays,true))continue;
      if((int)$r['nth_week_of_month']>0 && $nth!==(int)$r['nth_week_of_month'])continue;
      if((int)$r['exclude_nth_week_of_month']>0 && $nth===(int)$r['exclude_nth_week_of_month'])continue;
      if((int)$r['last_weekday']===1 && !lastWeekdayOfMonth($day))continue;
      $start=parseClock($day,(string)$r['start_time']);$end=(string)$r['end_time']!==''?parseClock($day,(string)$r['end_time']):$start;if($end<$start)$end=$end->modify('+1 day');
      $loc=(string)$r['location_text'];if($loc==='' && !empty($r['location_setting_key']))$loc=$settings[(string)$r['location_setting_key']]??'';
      $join=(string)$r['join_url'];if($join==='' && !empty($r['join_setting_key']))$join=$settings[(string)$r['join_setting_key']]??'';
      $regular[]=['id'=>'reg-'.$r['slug'].'-'.$day->format('Ymd'),'slug'=>$r['slug'],'title'=>$r['title'],'start_at'=>isoLocal($start),'end_at'=>isoLocal($end),'description'=>$r['description'],'location'=>$loc,'join_url'=>$join,'facebook_live'=>(int)$r['facebook_live'],'youtube_live'=>(int)$r['youtube_live'],'featured'=>(int)$r['featured'],'active'=>1,'source'=>'recurring'];
    }
  }
}
$events=array_merge($dbEvents,$regular);usort($events,fn($a,$b)=>strcmp((string)$a['start_at'],(string)$b['start_at']));

// Fellowship invitation text lives in the private settings table, never in public code.
$fellowNote=(string)($settings['sunday_fellowship_note']??'');
$csvList=function(string $raw):array{return array_values(array_filter(array_map('trim',explode(',',$raw)),fn($v)=>$v!==''));};
$fellowSlugs=$csvList((string)($settings['sunday_fellowship_slugs']??''));
$fellowServiceIds=$csvList((string)($settings['sunday_fellowship_service_ids']??''));
foreach($events as &$ev){$ev['fellowship_note']=($fellowNote!==''&&in_array((string)($ev['slug']??''),$fellowSlugs,true))?$fellowNote:'';}unset($ev);
foreach($services as &$sv){$sv['fellowship_note']=($fellowNote!==''&&in_array((string)($sv['id']??''),$fellowServiceIds,true))?$fellowNote:'';}unset($sv);

$quick=[];if(table_exists($DB,'quick_access')){foreach($DB->query('SELECT * FROM quick_access WHERE active=1 ORDER BY sort_order,id') as $r){$r['external_url']='';if(!empty($r['external_setting_key']))$r['external_url']=$settings[$r['external_setting_key']]??'';$quick[]=$r;}}
$cards=[];if(table_exists($DB,'contact_cards')){foreach($DB->query('SELECT * FROM contact_cards WHERE active=1 ORDER BY sort_order,id') as $r){$r['value']=$settings[$r['setting_key']]??'';$r['display_value']=$settings[$r['display_setting_key']]??$r['value'];$cards[]=$r;}}
$forms=[];$formsFile=$PRIVATE.'/forms.json';if(is_file($formsFile)){$forms=json_decode((string)file_get_contents($formsFile),true);if(!is_array($forms))$forms=[];}

$discoveryRoot=$PRIVATE.'/discovery_class';$activeYear='';if(file_exists($discoveryRoot.'/active-year.txt'))$activeYear=trim((string)file_get_contents($discoveryRoot.'/active-year.txt'));
$years=[];if(is_dir($discoveryRoot)){foreach(scandir($discoveryRoot)?:[] as $name){if(preg_match('/^\d{4}-\d{4}$/',$name)&&is_dir($discoveryRoot.'/'.$name))$years[]=$name;}}sort($years);if(!$activeYear&&$years)$activeYear=end($years);
$requestedYear=preg_replace('/[^0-9-]/','',(string)($_GET['year']??''));if($requestedYear&&in_array($requestedYear,$years,true))$activeYear=$requestedYear;
$lessons=[];$manualMeta=null;if($activeYear){$mf=$discoveryRoot.'/'.$activeYear.'/manifest.json';if(file_exists($mf)){$manualMeta=json_decode((string)file_get_contents($mf),true);if(is_array($manualMeta)){$lessons=$manualMeta['lessons']??[];foreach($lessons as &$l){$n=str_pad((string)($l['lesson_number']??0),2,'0',STR_PAD_LEFT);$l['teacher_available']=file_exists($discoveryRoot.'/'.$activeYear.'/teacher/lesson-'.$n.'.json');}unset($l);}}}
api_json(['ok'=>true,'server_now'=>isoLocal($now),'timezone'=>$tzName,'settings'=>$settings,'service_times'=>$services,'events'=>$events,'quick_access'=>$quick,'contact_cards'=>$cards,'forms'=>$forms,'discovery_lessons'=>$lessons,'manual_years'=>$years,'active_manual_year'=>$activeYear,'manual_meta'=>$manualMeta?['manual_year'=>$manualMeta['manual_year']??$activeYear,'manual_type'=>$manualMeta['manual_type']??'Adult','source_status'=>$manualMeta['source_status']??'']:null,'ministries'=>$mins,'donation'=>['sort_code'=>$settings['donation_sort_code']??'','account_number'=>$settings['donation_account_number']??''],'donation_refs'=>$refs]);
