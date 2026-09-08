<?php
declare(strict_types=1);
$PRIVATE = dirname(__DIR__,2) . '/rccg_fife_private';
if (!is_dir($PRIVATE)) {
  http_response_code(500); header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['ok'=>false,'message'=>'Private RCCG data folder is not available. Confirm rccg_fife_private is beside public_html.']); exit;
}
$cfgFile=$PRIVATE.'/config.php';
$dbFile=$PRIVATE.'/rccg.sqlite';
if (!is_file($cfgFile) || !is_file($dbFile)) {
  http_response_code(500); header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['ok'=>false,'message'=>'Private RCCG configuration or database is missing.']); exit;
}
$CFG = require $cfgFile;
if (!extension_loaded('pdo_sqlite')) {
  http_response_code(500); header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['ok'=>false,'message'=>'PDO SQLite is not enabled on this server.']); exit;
}
$DB = new PDO('sqlite:'.$dbFile);
$DB->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$DB->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
$DB->exec('PRAGMA foreign_keys=ON');
function api_json(array $data,int $status=200): never {http_response_code($status);header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');echo json_encode($data,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);exit;}
function now_utc(): string {return gmdate('c');}
function site_url(array $cfg): string {if (!empty($cfg['site_url'])) return rtrim((string)$cfg['site_url'],'/');$scheme=(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')?'https':'http';return $scheme.'://'.($_SERVER['HTTP_HOST']??'localhost');}
function ip_hash(array $cfg): string {return hash('sha256',($_SERVER['REMOTE_ADDR']??'0.0.0.0').'|'.($cfg['site_salt']??'fallback'));}
function clean_header(string $v): string {return trim(str_replace(["\r","\n"],' ',$v));}
function rate_limit(PDO $db,array $cfg,string $action,int $max=20): bool {if($max===0)return true;$ip=ip_hash($cfg);$cut=gmdate('c',time()-3600);$db->prepare('DELETE FROM rate_limits WHERE created_at < ?')->execute([$cut]);$s=$db->prepare('SELECT COUNT(*) FROM rate_limits WHERE ip_hash=? AND action=? AND created_at>=?');$s->execute([$ip,$action,$cut]);if((int)$s->fetchColumn()>=$max)return false;$db->prepare('INSERT INTO rate_limits(ip_hash,action,created_at) VALUES(?,?,?)')->execute([$ip,$action,now_utc()]);return true;}
function token(int $bytes=32): string {return rtrim(strtr(base64_encode(random_bytes($bytes)),'+/','-_'),'=');}
function token_hash(string $t): string {return hash('sha256',$t);}
function verify_cookie_name(): string {return 'rccg_fife_access';}
