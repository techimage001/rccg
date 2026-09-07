<?php
require __DIR__.'/bootstrap.php';require __DIR__.'/smtp_mailer.php';
$accept=str_contains($_SERVER['HTTP_ACCEPT']??'','application/json');
$data=$_POST;
if(($data['website']??'')!==''){if($accept)api_json(['ok'=>true,'message'=>'Thank you.']);header('Location: ../#/home');exit;}
$started=(int)($data['started']??0);if($started>0 && (int)(microtime(true)*1000)-$started<3000){if($accept)api_json(['ok'=>false,'message'=>'Please wait a moment and try again.'],429);http_response_code(429);exit('Please wait a moment and try again.');}
if(!rate_limit($DB,$CFG,'contact',30)){if($accept)api_json(['ok'=>false,'message'=>'Too many submissions. Please wait a few minutes.'],429);http_response_code(429);exit('Too many submissions.');}
$name=trim((string)($data['name']??''));$email=trim((string)($data['email']??''));$phone=trim((string)($data['phone']??''));$category=trim((string)($data['category']??''));$message=trim((string)($data['message']??''));$type=preg_replace('/[^a-z_-]/i','',(string)($data['form_type']??'general'));
if($name===''||!filter_var($email,FILTER_VALIDATE_EMAIL)||$phone===''||$message===''){if($accept)api_json(['ok'=>false,'message'=>'Please complete name, email, phone number and message.'],422);http_response_code(422);exit('Please complete all required fields.');}
$stmt=$DB->prepare('INSERT INTO contacts(form_type,name,email,phone,category,message,page,created_at,ip_hash,sent) VALUES(?,?,?,?,?,?,?,?,?,0)');$stmt->execute([$type,mb_substr($name,0,80),mb_substr($email,0,160),mb_substr($phone,0,40),mb_substr($category,0,120),mb_substr($message,0,3000),$_SERVER['HTTP_REFERER']??'',now_utc(),ip_hash($CFG)]);$id=(int)$DB->lastInsertId();$sent=false;
if(!empty($CFG['contact_to'])){try{$body="Form: {$type}\nName: {$name}\nEmail: {$email}\nPhone: {$phone}\nCategory: {$category}\n\nMessage:\n{$message}\n";$sent=smtp_send($CFG,(string)$CFG['contact_to'],'RCCG Open Heavens Fife - '.ucfirst($type),$body,$email);if($sent)$DB->prepare('UPDATE contacts SET sent=1 WHERE id=?')->execute([$id]);}catch(Throwable $e){error_log($e->getMessage());}}
$msg='Thank you. Your message has been received by RCCG Open Heavens Fife.';if($accept)api_json(['ok'=>true,'message'=>$msg,'sent'=>$sent]);
?><!doctype html><meta name="viewport" content="width=device-width"><title>Message received</title><style>body{font:18px system-ui;padding:40px;max-width:700px;margin:auto}a{color:#0a4dbf}</style><h1>Message received</h1><p><?=htmlspecialchars($msg,ENT_QUOTES,'UTF-8')?></p><p><a href="../">Back to RCCG Open Heavens Fife</a></p>
