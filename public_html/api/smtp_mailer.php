<?php
declare(strict_types=1);
function smtp_read($fp): string {$out='';while(($line=fgets($fp,515))!==false){$out.=$line;if(strlen($line)>=4 && $line[3]===' ')break;}return $out;}
function smtp_cmd($fp,string $cmd,array $ok=[250]): string {fwrite($fp,$cmd."\r\n");$r=smtp_read($fp);$code=(int)substr($r,0,3);if(!in_array($code,$ok,true))throw new RuntimeException('SMTP error '.$code.': '.trim($r));return $r;}
function smtp_send(array $cfg,string $to,string $subject,string $text,?string $replyTo=null): bool {
  if(empty($cfg['smtp_host'])||empty($cfg['smtp_pass'])||empty($cfg['smtp_user'])||empty($cfg['from_email']))return false;
  $secure=$cfg['smtp_secure']??'ssl';$host=(string)$cfg['smtp_host'];$port=(int)($cfg['smtp_port']??465);
  $target=($secure==='ssl'?'ssl://':'').$host;$fp=@stream_socket_client($target.':'.$port,$errno,$errstr,15,STREAM_CLIENT_CONNECT);
  if(!$fp)throw new RuntimeException('SMTP connection failed: '.$errstr);stream_set_timeout($fp,15);smtp_read($fp);
  smtp_cmd($fp,'EHLO '.($_SERVER['HTTP_HOST']??'localhost'),[250]);
  if($secure==='tls'){smtp_cmd($fp,'STARTTLS',[220]);if(!stream_socket_enable_crypto($fp,true,STREAM_CRYPTO_METHOD_TLS_CLIENT))throw new RuntimeException('TLS negotiation failed');smtp_cmd($fp,'EHLO '.($_SERVER['HTTP_HOST']??'localhost'),[250]);}
  smtp_cmd($fp,'AUTH LOGIN',[334]);smtp_cmd($fp,base64_encode((string)$cfg['smtp_user']),[334]);smtp_cmd($fp,base64_encode((string)$cfg['smtp_pass']),[235]);
  smtp_cmd($fp,'MAIL FROM:<'.$cfg['from_email'].'>',[250]);smtp_cmd($fp,'RCPT TO:<'.$to.'>',[250,251]);smtp_cmd($fp,'DATA',[354]);
  $headers=['From: '.clean_header((string)($cfg['from_name']??'RCCG Open Heavens Fife')).' <'.$cfg['from_email'].'>','To: '.$to,'Subject: '.clean_header($subject),'MIME-Version: 1.0','Content-Type: text/plain; charset=UTF-8'];if($replyTo)$headers[]='Reply-To: '.clean_header($replyTo);
  $payload=implode("\r\n",$headers)."\r\n\r\n".$text;$payload=preg_replace('/(?m)^\./','..',$payload);fwrite($fp,$payload."\r\n.\r\n");$resp=smtp_read($fp);if((int)substr($resp,0,3)!==250)throw new RuntimeException('SMTP send failed: '.trim($resp));smtp_cmd($fp,'QUIT',[221]);fclose($fp);return true;
}
