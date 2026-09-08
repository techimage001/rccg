<?php
require __DIR__.'/bootstrap.php';
$root=$PRIVATE.'/discovery_class';
$year=preg_replace('/[^0-9-]/','',(string)($_GET['year']??''));
if(!$year && file_exists($root.'/active-year.txt')) $year=trim((string)file_get_contents($root.'/active-year.txt'));
$type='teacher';
$num=(int)($_GET['lesson']??0);
if(!$year || $num<1 || $num>60) api_json(['ok'=>false,'message'=>'Invalid Discovery Class request.'],422);
$manifestFile=$root.'/'.$year.'/manifest.json';
if(!file_exists($manifestFile)) api_json(['ok'=>false,'message'=>'That manual year is not available.'],404);
$manifest=json_decode((string)file_get_contents($manifestFile),true);
$meta=null;
foreach(($manifest['lessons']??[]) as $l){if((int)($l['lesson_number']??0)===$num){$meta=$l;break;}}
if(!$meta) api_json(['ok'=>false,'message'=>'Lesson not found in this manual year.'],404);
$file=$root.'/'.$year.'/'.$type.'/lesson-'.str_pad((string)$num,2,'0',STR_PAD_LEFT).'.json';
if(!file_exists($file)){
  api_json(['ok'=>true,'available'=>false,'manual_type'=>$type,'lesson'=>$meta,'message'=>'Discovery Class Teacher Manual content has not yet been loaded into the private '.$year.' folder. The lesson date and title are verified.']);
}
$data=json_decode((string)file_get_contents($file),true);
if(!is_array($data)) api_json(['ok'=>false,'message'=>'The lesson content file is invalid JSON.'],500);
api_json(['ok'=>true,'available'=>true,'manual_type'=>$type,'lesson'=>$meta,'content'=>$data]);
