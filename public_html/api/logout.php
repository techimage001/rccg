<?php
require __DIR__.'/bootstrap.php';$c=$_COOKIE[verify_cookie_name()]??'';if($c!=='')$DB->prepare('DELETE FROM access_sessions WHERE session_hash=?')->execute([token_hash($c)]);setcookie(verify_cookie_name(),'',time()-3600,'/');header('Location: ../');
