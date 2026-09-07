<?php
require __DIR__.'/bootstrap.php';$cookie=$_COOKIE[verify_cookie_name()]??'';if($cookie==='')api_json(['verified'=>false]);$s=$DB->prepare('SELECT email FROM access_sessions WHERE session_hash=? AND expires_at>?');$s->execute([token_hash($cookie),now_utc()]);$r=$s->fetch();api_json($r?['verified'=>true,'email'=>$r['email']]:['verified'=>false]);
