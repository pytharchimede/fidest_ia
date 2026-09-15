<?php
declare(strict_types=1);
namespace FidestIA\Core;
use RuntimeException;
final class ProcessRunner
{
    public function __construct(private readonly int $timeoutSeconds=90){}
    /** @param list<string> $arguments @return array{stdout:string,stderr:string,exit_code:int} */
    public function run(array $arguments,?array $environment=null):array
    {
        if(!$this->available())throw new RuntimeException('Exécution de commandes système indisponible.');
        $command=implode(' ',array_map('escapeshellarg',$arguments));
        $pipes=[];$process=proc_open($command,[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,null,$environment);
        if(!is_resource($process))throw new RuntimeException('Impossible de démarrer le processus OCR.');
        fclose($pipes[0]);stream_set_blocking($pipes[1],false);stream_set_blocking($pipes[2],false);$stdout='';$stderr='';$started=microtime(true);$code=-1;
        try{while(true){$stdout.=stream_get_contents($pipes[1])?:'';$stderr.=stream_get_contents($pipes[2])?:'';$status=proc_get_status($process);if(!$status['running']){$code=(int)$status['exitcode'];break;}if(microtime(true)-$started>$this->timeoutSeconds){proc_terminate($process,9);throw new RuntimeException('Délai maximal du traitement externe dépassé.');}usleep(50000);}}
        finally{foreach($pipes as $pipe)if(is_resource($pipe))fclose($pipe);$closed=proc_close($process);if($code<0)$code=$closed;}
        return ['stdout'=>$stdout,'stderr'=>mb_substr(trim($stderr),0,2000),'exit_code'=>$code];
    }
    public function available():bool{$disabled=array_map('trim',explode(',',(string)ini_get('disable_functions')));return function_exists('proc_open')&&!in_array('proc_open',$disabled,true);}
}
