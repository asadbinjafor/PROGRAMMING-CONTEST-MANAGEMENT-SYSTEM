<?php
declare(strict_types=1);

$root=dirname(__DIR__);$iterator=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS));$failed=[];
foreach($iterator as $file){
    if($file->getExtension()!=='php')continue;
    $path=$file->getPathname();
    if(str_contains($path,DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR))continue;
    exec(escapeshellarg(PHP_BINARY).' -l '.escapeshellarg($path).' 2>&1',$output,$code);
    if($code!==0)$failed[]=implode(PHP_EOL,$output);
    $output=[];
}
if($failed){fwrite(STDERR,implode(PHP_EOL,$failed).PHP_EOL);exit(1);}
echo "PHP lint passed.".PHP_EOL;

