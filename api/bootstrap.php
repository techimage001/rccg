<?php
declare(strict_types=1);

function api_json(array $data,int $status=200): never {
  http_response_code($status);
  header('Content-Type: application/json; charset=utf-8');
  header('Cache-Control: no-store');
  echo json_encode($data,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
  exit;
}

/**
 * Locate the private RCCG data directory while keeping it outside the public
 * WebApp folder. This supports both normal Hostinger public_html deployment
 * and the nested public_html/public_html layout currently used by the site.
 */
function locate_private_dir(): ?string {
  $candidates=[];
  $env=getenv('RCCG_FIFE_PRIVATE_DIR');
  if(is_string($env) && trim($env)!=='') $candidates[]=rtrim(trim($env),'/\\');

  // api/ lives one level below the WebApp root. Walk upward far enough to
  // support public_html/api and public_html/public_html/api deployments.
  for($levels=2;$levels<=5;$levels++){
    $base=dirname(__DIR__,$levels);
    if($base && $base!=='.') $candidates[]=$base.'/rccg_fife_private';
  }

  if(!empty($_SERVER['DOCUMENT_ROOT'])){
    $doc=rtrim((string)$_SERVER['DOCUMENT_ROOT'],'/\\');
    $candidates[]=dirname($doc).'/rccg_fife_private';
    $candidates[]=dirname($doc,2).'/rccg_fife_private';
  }

  $seen=[];
  foreach($candidates as $candidate){
    $candidate=rtrim($candidate,'/\\');
    if($candidate==='' || isset($seen[$candidate])) continue;
    $seen[$candidate]=true;
    if(is_dir($candidate) && is_file($candidate.'/rccg.sqlite')) return $candidate;
  }

  error_log('RCCG WebApp: private data directory not found. Candidate count='.count($seen));
  return null;
}

$PRIVATE=locate_private_dir();
if($PRIVATE===null){
  api_json([
    'ok'=>false,
    'code'=>'PRIVATE_DATA_NOT_FOUND',
    'message'=>'Church data is temporarily unavailable because the private data folder could not be located.'
  ],500);
}

$CFG=is_file($PRIVATE.'/config.php') ? require $PRIVATE.'/config.php' : [];
$dbFile=$PRIVATE.'/rccg.sqlite';
if(!is_file($dbFile)){
  error_log('RCCG WebApp: rccg.sqlite is missing from the private data directory.');
  api_json(['ok'=>false,'code'=>'DATABASE_NOT_FOUND','message'=>'Church data is temporarily unavailable because the private database is missing.'],500);
}
if(!extension_loaded('pdo_sqlite')){
  error_log('RCCG WebApp: PDO SQLite PHP extension is not enabled.');
  api_json(['ok'=>false,'code'=>'SQLITE_NOT_ENABLED','message'=>'Church data is temporarily unavailable because the server database extension is not enabled.'],500);
}
try{
  $DB=new PDO('sqlite:'.$dbFile);
  $DB->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
  $DB->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_ASSOC);
  $DB->exec('PRAGMA foreign_keys=ON');
}catch(Throwable $e){
  error_log('RCCG WebApp database connection failed: '.$e->getMessage());
  api_json(['ok'=>false,'code'=>'DATABASE_CONNECTION_FAILED','message'=>'Church data is temporarily unavailable because the private database could not be opened.'],500);
}

function now_utc(): string {return gmdate('c');}
function site_url(array $cfg): string {
  if (!empty($cfg['site_url'])) return rtrim((string)$cfg['site_url'],'/');
  $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off') ? 'https' : 'http';
  return $scheme.'://'.($_SERVER['HTTP_HOST']??'localhost');
}
function ip_hash(array $cfg): string {return hash('sha256',($_SERVER['REMOTE_ADDR']??'0.0.0.0').'|'.($cfg['site_salt']??'fallback')) ;}
function clean_header(string $v): string {return trim(str_replace(["\r","\n"],' ',$v));}
function rate_limit(PDO $db,array $cfg,string $action,int $max=20): bool {
  if($max===0)return true;$ip=ip_hash($cfg);$cut=gmdate('c',time()-3600);
  $db->prepare('DELETE FROM rate_limits WHERE created_at < ?')->execute([$cut]);
  $s=$db->prepare('SELECT COUNT(*) FROM rate_limits WHERE ip_hash=? AND action=? AND created_at>=?');$s->execute([$ip,$action,$cut]);
  if((int)$s->fetchColumn()>=$max)return false;
  $db->prepare('INSERT INTO rate_limits(ip_hash,action,created_at) VALUES(?,?,?)')->execute([$ip,$action,now_utc()]);return true;
}
function token(int $bytes=32): string {return rtrim(strtr(base64_encode(random_bytes($bytes)),'+/','-_'),'=');}
function token_hash(string $t): string {return hash('sha256',$t);}
function verify_cookie_name(): string {return 'rccg_fife_access';}
