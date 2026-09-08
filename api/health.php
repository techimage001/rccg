<?php
require __DIR__.'/bootstrap.php';
$root=$PRIVATE.'/discovery_class';$year=is_file($root.'/active-year.txt')?trim((string)file_get_contents($root.'/active-year.txt')):'';$manifest=$year?($root.'/'.$year.'/manifest.json'):'';$lessons=0;if($year&&is_dir($root.'/'.$year.'/teacher')){$lessons=count(glob($root.'/'.$year.'/teacher/lesson-*.json')?:[]);}api_json(['ok'=>true,'private_folder'=>is_dir($PRIVATE),'database'=>is_file($PRIVATE.'/rccg.sqlite'),'forms'=>is_file($PRIVATE.'/forms.json'),'manual_year'=>$year,'manifest'=>is_file($manifest),'teacher_lessons'=>$lessons]);
