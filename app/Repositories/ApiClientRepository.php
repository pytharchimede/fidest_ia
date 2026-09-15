<?php
declare(strict_types=1);
namespace FidestIA\Repositories;
use PDO;
use FidestIA\Services\ApiKeyService;
final class ApiClientRepository
{
    public const SCOPES=['documents:analyze','documents:read','documents:list','types:read','types:write','rules:read','rules:write'];
    public function __construct(private readonly PDO $db){}
    public function all():array{return $this->db->query('SELECT * FROM api_clients ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC)?:[];}
    public function create(string $name,array $scopes,?string $expiresAt=null,array $options=[]):array
    {
        $env=in_array($options['environment']??'',['production','staging','development'],true)?$options['environment']:'production';
        $key=(new ApiKeyService())->generate($env);$prefix=$key['prefix'];$plain=$key['plain'];
        $code=$this->uniqueCode((string)($options['code']??$name));$safe=array_values(array_unique(array_intersect($scopes,self::SCOPES)));
        if(($options['internal_master']??false)===true)$safe=['*'];
        $stmt=$this->db->prepare('INSERT INTO api_clients (name,code,description,environment,origin,key_prefix,key_hash,last_four,scopes,rate_limit_per_minute,notes,expires_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');
        $stmt->execute([$name,$code,$options['description']??null,$env,$options['origin']??null,$prefix,$key['hash'],$key['last_four'],json_encode($safe,JSON_UNESCAPED_UNICODE),max(1,(int)($options['rate_limit_per_minute']??60)),$options['notes']??null,$expiresAt?:null]);
        return ['id'=>(int)$this->db->lastInsertId(),'name'=>$name,'code'=>$code,'key'=>$plain,'key_prefix'=>$prefix,'last_four'=>$key['last_four'],'scopes'=>$safe,'expires_at'=>$expiresAt?:null];
    }
    public function regenerate(int $id):?array
    {
        $stmt=$this->db->prepare('SELECT * FROM api_clients WHERE id=?');$stmt->execute([$id]);$client=$stmt->fetch(PDO::FETCH_ASSOC);if(!$client)return null;
        $key=(new ApiKeyService())->generate((string)($client['environment']??'production'));$prefix=$key['prefix'];$plain=$key['plain'];
        $stmt=$this->db->prepare('UPDATE api_clients SET key_prefix=?,key_hash=?,last_four=?,active=1,revoked_at=NULL WHERE id=?');$stmt->execute([$prefix,$key['hash'],$key['last_four'],$id]);
        return ['id'=>$id,'name'=>$client['name'],'key'=>$plain,'key_prefix'=>$prefix,'last_four'=>$key['last_four']];
    }
    public function findByPlainKey(string $plain):?array
    {
        $keys=new ApiKeyService();if(!$keys->isFormatValid($plain))return null;
        $parts=explode('_',$plain);$prefix=implode('_',array_slice($parts,0,3));$stmt=$this->db->prepare('SELECT * FROM api_clients WHERE key_prefix=?');$stmt->execute([$prefix]);$row=$stmt->fetch(PDO::FETCH_ASSOC);
        return $row&&$keys->verify($plain,(string)$row['key_hash'])?$row:null;
    }
    public function touchLastUsed(int $id):void{$stmt=$this->db->prepare('UPDATE api_clients SET last_used_at=NOW(),request_count=request_count+1 WHERE id=?');$stmt->execute([$id]);}
    public function consumeRateLimit(int $id,int $limit):array
    {
        $window=date('Y-m-d H:i:00');$stmt=$this->db->prepare('INSERT INTO api_rate_limits (api_client_id,window_start,request_count) VALUES (?,?,1) ON DUPLICATE KEY UPDATE request_count=request_count+1');$stmt->execute([$id,$window]);
        $stmt=$this->db->prepare('SELECT request_count FROM api_rate_limits WHERE api_client_id=? AND window_start=?');$stmt->execute([$id,$window]);$count=(int)$stmt->fetchColumn();
        return ['limit'=>$limit,'remaining'=>max(0,$limit-$count),'allowed'=>$count<=$limit,'retry_after'=>max(1,60-(int)date('s'))];
    }
    public function revoke(int $id):bool{$stmt=$this->db->prepare('UPDATE api_clients SET active=0,revoked_at=NOW() WHERE id=? AND active=1');$stmt->execute([$id]);return $stmt->rowCount()>0;}
    public function setActive(int $id,bool $active):bool{$stmt=$this->db->prepare('UPDATE api_clients SET active=?,revoked_at=IF(?=1,NULL,COALESCE(revoked_at,NOW())) WHERE id=?');$stmt->execute([$active?1:0,$active?1:0,$id]);return $stmt->rowCount()>0;}
    private function uniqueCode(string $value):string
    {
        $ascii=iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$value)?:$value;$base=trim(strtolower((string)preg_replace('/[^a-z0-9]+/i','-',$ascii)),'-')?:'application';$code=$base;$n=2;$stmt=$this->db->prepare('SELECT 1 FROM api_clients WHERE code=?');
        while(true){$stmt->execute([$code]);if(!$stmt->fetchColumn())return $code;$code=$base.'-'.$n++;}
    }
}
