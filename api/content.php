<?php
require __DIR__.'/bootstrap.php';
$settings=[];foreach($DB->query('SELECT key,value FROM settings') as $r)$settings[$r['key']]=$r['value'];
$today=(new DateTimeImmutable('now',new DateTimeZone('Europe/London')))->format('Y-m-d');
$s=$DB->prepare('SELECT * FROM devotionals WHERE published=1 AND devotional_date<=? ORDER BY devotional_date DESC LIMIT 1');$s->execute([$today]);$dev=$s->fetch()?:null;
$services=$DB->query('SELECT * FROM service_times WHERE active=1 ORDER BY sort_order,id')->fetchAll();
$events=$DB->query('SELECT * FROM events WHERE active=1 ORDER BY start_at ASC')->fetchAll();
$lessons=$DB->query('SELECT * FROM discovery_lessons WHERE published=1 ORDER BY lesson_date ASC')->fetchAll();
$mins=$DB->query('SELECT * FROM ministries WHERE active=1 ORDER BY name')->fetchAll();
$refs=$DB->query('SELECT * FROM donation_refs WHERE active=1 ORDER BY sort_order,id')->fetchAll();
api_json(['ok'=>true,'settings'=>$settings,'today_devotional'=>$dev,'service_times'=>$services,'events'=>$events,'discovery_lessons'=>$lessons,'ministries'=>$mins,'donation'=>['sort_code'=>$settings['donation_sort_code']??'','account_number'=>$settings['donation_account_number']??''],'donation_refs'=>$refs]);
