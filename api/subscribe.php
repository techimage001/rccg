<?php
require __DIR__.'/bootstrap.php';require __DIR__.'/smtp_mailer.php';
$raw=json_decode(file_get_contents('php://input'),true)?:[];$email=trim((string)($raw['email']??''));$ts=(int)($raw['ts']??0);$hp=(string)($raw['website']??'');$page=mb_substr((string)($raw['page']??''),0,500);
if($hp!=='')api_json(['ok'=>true,'message'=>'Check your inbox and open the verification link.']);
if($ts>0 && (int)(microtime(true)*1000)-$ts<3000)api_json(['ok'=>false,'message'=>'Please wait a moment and try again.','reason'=>'time_trap'],429);
if(!filter_var($email,FILTER_VALIDATE_EMAIL))api_json(['ok'=>false,'message'=>'Please enter a valid email address.'],422);
if(!rate_limit($DB,$CFG,'subscribe',15))api_json(['ok'=>false,'message'=>'Too many requests. Please wait a few minutes.'],429);
if(empty($CFG['smtp_host'])||empty($CFG['smtp_pass'])||empty($CFG['from_email']))api_json(['ok'=>false,'message'=>'Email verification is not configured yet.'],503);
$t=token();$u=token();$th=token_hash($t);$uh=token_hash($u);$now=now_utc();$exp=gmdate('c',time()+48*3600);
$s=$DB->prepare('INSERT INTO subscribers(email,token_hash,created_at,updated_at,token_expires_at,unsubscribe_hash,page,ip_hash) VALUES(?,?,?,?,?,?,?,?) ON CONFLICT(email) DO UPDATE SET token_hash=excluded.token_hash,updated_at=excluded.updated_at,token_expires_at=excluded.token_expires_at,unsubscribe_hash=excluded.unsubscribe_hash,page=excluded.page,ip_hash=excluded.ip_hash');$s->execute([$email,$th,$now,$now,$exp,$uh,$page,ip_hash($CFG)]);
$base=site_url($CFG);$verify=$base.'/api/verify.php?token='.rawurlencode($t).'&email='.rawurlencode($email);$unsub=$base.'/api/unsubscribe.php?token='.rawurlencode($u).'&email='.rawurlencode($email);
$body="Hello,\n\nOpen the private link below to confirm your email and unlock RCCG Open Heavens Fife on this browser:\n\n{$verify}\n\nThe link works for 48 hours. Nothing is charged and no payment-card details are requested.\n\nIf you did not request this email, ignore it.\n\nDelete this address from our records:\n{$unsub}\n";
try{smtp_send($CFG,$email,'Confirm your email address',$body);}catch(Throwable $e){error_log($e->getMessage());api_json(['ok'=>false,'message'=>'We saved your request but could not send the verification email. Please check SMTP settings.'],503);}api_json(['ok'=>true,'message'=>'Check your inbox and open the verification link.']);
