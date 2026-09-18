<?php
declare(strict_types=1);

require dirname(__DIR__).'/app/bootstrap.php';

use PCMS\Support\Csrf;
use PCMS\Support\Validator;

$failures=[];$assert=function(bool $condition,string $message)use(&$failures):void{if(!$condition)$failures[]=$message;};

$token=Csrf::token();
$assert(strlen($token)===64,'CSRF token must contain 32 random bytes.');
$assert(Csrf::verify($token),'Generated CSRF token must verify.');
$assert(!Csrf::verify('invalid'),'Invalid CSRF token must fail.');

$v=(new Validator())->required('name','','Name')->email('email','bad')->length('password','short',10,200,'Password')->in('role','ROOT',['ADMIN'],'role');
$assert($v->fails(),'Invalid values must fail validation.');
$assert(count($v->errors())===4,'Validator must report every invalid field.');

$hash=password_hash('Password123!',PASSWORD_DEFAULT);
$assert(password_verify('Password123!',$hash),'Password hash round-trip must pass.');
$assert(!password_verify('wrong',$hash),'Wrong password must fail.');

$routes=require dirname(__DIR__).'/routes/web.php';
$assert(count($routes)>=40,'Expected complete route inventory.');
$patterns=[];
foreach($routes as $route){
    [$method,$pattern,$handler,$middleware]=$route+[3=>[]];
    $key=$method.' '.$pattern;
    $assert(!isset($patterns[$key]),"Duplicate route: {$key}");
    $patterns[$key]=true;
    [$class,$action]=$handler;
    $assert(class_exists($class),"Controller not found: {$class}");
    $assert(method_exists($class,$action),"Controller action not found: {$class}::{$action}");
    if(in_array($method,['POST','PUT','PATCH','DELETE'],true))$assert(in_array('csrf',$middleware,true),"State-changing route lacks CSRF: {$key}");
}
$assert(!preg_match('/CodeElite|href=["\\x27]#["\\x27]/',implode('',array_map(fn($f)=>file_get_contents($f),glob(dirname(__DIR__).'/app/Views/*/*.php')?:[]))),'Views must not contain stale branding or dead hash links.');

$sql=implode(PHP_EOL,array_map(fn($f)=>file_get_contents($f),glob(dirname(__DIR__).'/database/*.sql')?:[]));
$assert(str_contains($sql,'CONTEST_REGISTRATION'),'Registration table must exist.');
$assert(str_contains($sql,'SEQ_ANNOUNCEMENT'),'Announcement sequence must exist.');
$assert(!str_contains($sql,'NOT SET’'),'SQL must not contain typographic quote defect.');

if($failures){foreach($failures as $failure)fwrite(STDERR,"FAIL: {$failure}".PHP_EOL);exit(1);}
echo "All ".(count($routes)+10)." smoke assertions passed.".PHP_EOL;

