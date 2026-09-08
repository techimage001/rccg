<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
$accept=str_contains($_SERVER['HTTP_ACCEPT']??'','application/json');
$data=$_POST;
if(($data['website']??'')!==''){if($accept)api_json(['ok'=>true,'message'=>'Thank you.']);header('Location: ../#/home');exit;}
$started=(int)($data['started']??0);
if($started>0 && (int)(microtime(true)*1000)-$started<3000){if($accept)api_json(['ok'=>false,'message'=>'Please wait a moment and try again.'],429);http_response_code(429);exit('Please wait a moment and try again.');}
if(!rate_limit($DB,$CFG,'first_time_worshipper',20)){if($accept)api_json(['ok'=>false,'message'=>'Too many submissions. Please wait a few minutes.'],429);http_response_code(429);exit('Too many submissions.');}
$name=trim((string)($data['name']??''));
$email=trim((string)($data['email']??''));
$phone=trim((string)($data['phone']??''));
$address=trim((string)($data['address']??''));
$prayer=trim((string)($data['prayer_point']??''));
$suggestion=trim((string)($data['suggestion']??''));
$rating=(int)($data['rating']??0);
$mayContact=strtolower(trim((string)($data['may_contact']??'')));
if($name===''||!filter_var($email,FILTER_VALIDATE_EMAIL)||$phone===''||$rating<1||$rating>5||!in_array($mayContact,['yes','no'],true)){
  if($accept)api_json(['ok'=>false,'message'=>'Please complete your name, email, phone number, experience rating and contact preference.'],422);
  http_response_code(422);exit('Please complete all required fields.');
}
$DB->exec("CREATE TABLE IF NOT EXISTS first_time_worshippers (
 id INTEGER PRIMARY KEY AUTOINCREMENT,
 name TEXT NOT NULL,
 email TEXT NOT NULL,
 phone TEXT NOT NULL,
 address TEXT NOT NULL DEFAULT '',
 prayer_point TEXT NOT NULL DEFAULT '',
 rating INTEGER NOT NULL,
 suggestion TEXT NOT NULL DEFAULT '',
 may_contact INTEGER NOT NULL DEFAULT 0,
 page TEXT NOT NULL DEFAULT '',
 created_at TEXT NOT NULL,
 ip_hash TEXT NOT NULL DEFAULT '',
 status TEXT NOT NULL DEFAULT 'new'
)");
$stmt=$DB->prepare('INSERT INTO first_time_worshippers(name,email,phone,address,prayer_point,rating,suggestion,may_contact,page,created_at,ip_hash,status) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)');
$stmt->execute([
 mb_substr($name,0,80),mb_substr($email,0,160),mb_substr($phone,0,40),mb_substr($address,0,240),mb_substr($prayer,0,2000),$rating,mb_substr($suggestion,0,2000),$mayContact==='yes'?1:0,$_SERVER['HTTP_REFERER']??'',now_utc(),ip_hash($CFG),'new'
]);
$msg='Thank you for worshipping with RCCG Open Heavens Fife. Your response has been received.';
if($accept)api_json(['ok'=>true,'message'=>$msg]);
?><!doctype html><meta name="viewport" content="width=device-width"><title>Thank you</title><style>body{font:18px system-ui;padding:40px;max-width:700px;margin:auto}a{color:#0a4dbf}</style><h1>Thank you</h1><p><?=htmlspecialchars($msg,ENT_QUOTES,'UTF-8')?></p><p><a href="../#/home">Back to RCCG Open Heavens Fife</a></p>
