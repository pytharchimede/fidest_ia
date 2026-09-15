<?php
declare(strict_types=1);
namespace FidestIA\Services;
final class ApiKeyService
{
    public function generate(string $environment='production'):array
    {
        $prefix='fia_'.($environment==='production'?'live':'test').'_'.bin2hex(random_bytes(4));$secret=bin2hex(random_bytes(32));$plain=$prefix.'_'.$secret;
        return ['plain'=>$plain,'prefix'=>$prefix,'hash'=>hash('sha256',$plain),'last_four'=>substr($secret,-4)];
    }
    public function verify(string $plain,string $expectedHash):bool{return hash_equals($expectedHash,hash('sha256',$plain));}
    public function isFormatValid(string $plain):bool{return preg_match('/^fia_(?:live|test)_[a-f0-9]{8}_[a-f0-9]{64}$/D',$plain)===1;}
}
