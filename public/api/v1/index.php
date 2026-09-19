<?php
declare(strict_types=1);
use FidestIA\Core\{ApiAuth,ApiException,Database};
use FidestIA\Repositories\{ApiLogRepository,DocumentRepository,ValidationRuleRepository};
use FidestIA\Services\{DocumentAnalysisService,DocumentClassifierService,DocumentExtractionService,DocumentStorageService,OcrDocumentService,ValidationEngine};
use FidestIA\Services\Ocr\OcrEngineFactory;
$root=dirname(__DIR__,3);
ob_start();
ini_set('display_errors','0');
header('Content-Type: application/json; charset=utf-8');
register_shutdown_function(static function():void{
    $error=error_get_last();
    if(!$error||!in_array($error['type'],[E_ERROR,E_PARSE,E_CORE_ERROR,E_COMPILE_ERROR,E_USER_ERROR],true))return;
    while(ob_get_level()>0)ob_end_clean();
    if(!headers_sent()){http_response_code(500);header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');}
    try{$requestId=uuid();}catch(Throwable){$requestId=bin2hex(random_bytes(8));}
    echo json_encode(['success'=>false,'error'=>['code'=>'INTERNAL_ERROR','message'=>'Une erreur interne a interrompu le traitement.'],'meta'=>['request_id'=>$requestId]],JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
});
try{$config=require $root.'/bootstrap.php';}catch(Throwable $e){http_response_code(500);echo json_encode(['success'=>false,'error'=>['code'=>'BOOTSTRAP_ERROR','message'=>'Service documentaire temporairement indisponible.'],'meta'=>['request_id'=>uuid()]],JSON_UNESCAPED_UNICODE);exit;}
ApiAuth::applyCors($config);
if(($_SERVER['REQUEST_METHOD']??'GET')==='OPTIONS'){http_response_code(204);exit;}
$started=microtime(true);$requestId=uuid();$route='/'.trim((string)($_GET['_route']??''),'/');$method=strtoupper((string)($_SERVER['REQUEST_METHOD']??'GET'));$client=null;$documentUuid=null;$errorCode=null;$status=500;
try{
    $db=Database::connection($config);$documents=new DocumentRepository($db);$rules=new ValidationRuleRepository($db);
    if($method==='GET'&&$route==='/health')respond(200,['success'=>true,'data'=>['service'=>'FIDEST IA','api_version'=>'v1','status'=>'ok'],'meta'=>['request_id'=>$requestId]]);
    $scope=match(true){
        $route==='/ocr'&&$method==='POST'=>'documents:analyze',
        preg_match('#^/documents/[0-9a-f-]{36}/ocr$#i',$route)===1=>'documents:read',
        ($route==='/documents/analyze'||($route==='/documents'&&$method==='POST'))=>'documents:analyze',
        $route==='/documents'=>'documents:list',
        preg_match('#^/documents/[0-9a-f-]{36}(?:/analysis)?$#i',$route)===1=>'documents:read',
        $route==='/document-types'&&$method==='GET'=>'types:read',
        $route==='/document-types'&&$method==='POST'=>'types:write',
        $route==='/validation-rules'&&$method==='GET'=>'rules:read',
        $route==='/validation-rules'&&$method==='POST'=>'rules:write',
        default=>throw new ApiException(404,'NOT_FOUND','Route API introuvable.')
    };
    $client=ApiAuth::authenticate($config,$db,$scope);

    if($route==='/documents'&&$method==='GET')respond(200,['success'=>true,'data'=>$documents->search(['q'=>trim((string)($_GET['q']??'')),'status'=>trim((string)($_GET['status']??''))],min(100,max(1,(int)($_GET['limit']??50)))),'meta'=>['request_id'=>$requestId]]);

    if($method==='GET'&&preg_match('#^/documents/([0-9a-f-]{36})/ocr$#i',$route,$m)){
        $doc=$documents->findByUuid($m[1]);
        if(!$doc)throw new ApiException(404,'DOCUMENT_NOT_FOUND','Document introuvable.');
        assertDocumentAccess($doc,$client);
        $documentUuid=$m[1];
        respond(200,['success'=>true,'data'=>['document_id'=>$m[1],'filename'=>$doc['original_name'],'mime_type'=>$doc['mime_type'],'ocr'=>['text'=>(string)($doc['extracted_text']??''),'language'=>(string)($config['ocr']['languages']??'fra+eng'),'engine'=>(string)($config['ocr']['driver']??'tesseract'),'confidence'=>$doc['confidence']!==null?(float)$doc['confidence']:null]],'meta'=>['request_id'=>$requestId]]);
    }

    if($method==='GET'&&preg_match('#^/documents/([0-9a-f-]{36})(?:/analysis)?$#i',$route,$m)){$doc=$documents->findByUuid($m[1]);if(!$doc)throw new ApiException(404,'DOCUMENT_NOT_FOUND','Document introuvable.');assertDocumentAccess($doc,$client);$doc['extracted_data']=json_decode((string)($doc['extracted_data']??'{}'),true)?:[];$doc['ocr']=['text'=>(string)($doc['extracted_text']??''),'confidence'=>$doc['confidence']!==null?(float)$doc['confidence']:null];respond(200,['success'=>true,'data'=>$doc,'meta'=>['request_id'=>$requestId]]);}
    if($route==='/document-types'&&$method==='GET')respond(200,['success'=>true,'data'=>array_map('publicType',$documents->allTypes(true)),'meta'=>['request_id'=>$requestId]]);
    if($route==='/document-types'&&$method==='POST'){$p=payload();$name=trim((string)($p['name']??''));if($name==='')throw new ApiException(422,'INVALID_DOCUMENT_TYPE','Le nom est requis.');$code=typeCode((string)($p['code']??$name));$id=$documents->createType(['code'=>$code,'name'=>$name,'description'=>$p['description']??null,'extraction_schema'=>$p['extraction_schema']??['fields'=>$p['fields']??[],'classification'=>['keywords'=>$p['keywords']??[]]]]);respond(201,['success'=>true,'data'=>['id'=>$id,'code'=>$code],'meta'=>['request_id'=>$requestId]]);}
    if($route==='/validation-rules'&&$method==='GET')respond(200,['success'=>true,'data'=>$rules->all(),'meta'=>['request_id'=>$requestId]]);
    if($route==='/validation-rules'&&$method==='POST'){$p=payload();foreach(['document_type_id','name','rule_type','error_message'] as $f)if(empty($p[$f]))throw new ApiException(422,'INVALID_RULE',"Champ requis : $f");$id=$rules->create($p);respond(201,['success'=>true,'data'=>['id'=>$id],'meta'=>['request_id'=>$requestId]]);}

    if($route==='/ocr'&&$method==='POST'){
        validateUploadedDocument($config);
        $language=trim((string)($_POST['language']??($config['ocr']['languages']??'fra+eng')));
        if(!preg_match('/^[a-z]{3}(?:\+[a-z]{3})*$/i',$language))throw new ApiException(422,'INVALID_OCR_LANGUAGE','Langue OCR invalide. Exemple : fra ou fra+eng.');
        $ocrConfig=$config;$ocrConfig['ocr']['languages']=$language;
        $service=new OcrDocumentService(new DocumentStorageService($config['storage']['documents_path']),(new OcrEngineFactory($ocrConfig,$root))->create(),$documents,(string)($config['ocr']['languages']??'fra+eng'));
        $result=$service->extract($_FILES['document'],$client['id']??null,isset($_POST['client_reference'])?trim((string)$_POST['client_reference']):null,$language);
        $documentUuid=$result['document_id'];unset($result['internal_id']);
        respond(200,['success'=>true,'data'=>$result,'meta'=>['request_id'=>$requestId]]);
    }

    if(($route==='/documents/analyze'||$route==='/documents')&&$method==='POST'){
        validateUploadedDocument($config);
        $service=new DocumentAnalysisService(new DocumentStorageService($config['storage']['documents_path']),(new OcrEngineFactory($config,$root))->create(),new DocumentExtractionService(),new DocumentClassifierService(),new ValidationEngine($documents),$documents,$rules);
        $result=$service->analyze($_FILES['document'],trim((string)($_POST['document_type']??'AUTO'))?:'AUTO',isset($_POST['client_reference'])?trim((string)$_POST['client_reference']):null,$client['id']??null);$documentUuid=$result['uuid'];respond(200,['success'=>true,'data'=>$result,'meta'=>['request_id'=>$requestId]]);
    }
}catch(ApiException $e){$status=$e->status;$errorCode=$e->errorCode;respond($status,['success'=>false,'error'=>['code'=>$errorCode,'message'=>$e->getMessage()],'meta'=>['request_id'=>$requestId]]);}catch(RuntimeException $e){$status=422;$errorCode='OCR_FAILED';respond(422,['success'=>false,'error'=>['code'=>$errorCode,'message'=>$e->getMessage()],'meta'=>['request_id'=>$requestId]]);}catch(Throwable $e){$status=500;$errorCode='INTERNAL_ERROR';$message=($config['app']['debug']??false)?$e->getMessage():'Une erreur interne est survenue.';respond(500,['success'=>false,'error'=>['code'=>$errorCode,'message'=>$message],'meta'=>['request_id'=>$requestId]]);}
function validateUploadedDocument(array $config):void{if(!isset($_FILES['document']))throw new ApiException(422,'FILE_REQUIRED','Le champ document est requis.');$max=(int)($config['storage']['max_upload_mb']??15);if((int)($_FILES['document']['size']??0)>$max*1048576)throw new ApiException(422,'FILE_TOO_LARGE',"Taille maximale : $max Mo.");if((int)($_FILES['document']['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK)throw new ApiException(422,'INVALID_DOCUMENT','Téléversement du document invalide.');}
function assertDocumentAccess(array $doc,array $client):void{if(($client['type']??'')==='master')return;$owner=$doc['api_client_id']??null;if($owner===null||(int)$owner!==(int)($client['id']??0))throw new ApiException(403,'DOCUMENT_ACCESS_DENIED','Cette application ne peut pas accéder à ce document.');}
function respond(int $code,array $body):never{global $db,$client,$requestId,$method,$route,$started,$documentUuid,$errorCode;$status=$code;try{if(isset($db))(new ApiLogRepository($db))->record(['api_client_id'=>$client['id']??null,'request_id'=>$requestId,'method'=>$method,'route'=>$route,'status_code'=>$code,'duration_ms'=>(int)((microtime(true)-$started)*1000),'ip_address'=>$_SERVER['REMOTE_ADDR']??null,'user_agent'=>mb_substr((string)($_SERVER['HTTP_USER_AGENT']??''),0,500),'document_uuid'=>$documentUuid,'error_code'=>$errorCode]);}catch(Throwable){}while(ob_get_level()>0)ob_end_clean();http_response_code($code);header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');echo json_encode($body,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);exit;}
function payload():array{$data=json_decode(file_get_contents('php://input')?:'{}',true);return is_array($data)?$data:[];}
function typeCode(string $v):string{$a=iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$v)?:$v;$c=trim(strtoupper((string)preg_replace('/[^A-Z0-9]+/i','_',$a)),'_');if($c===''||in_array($c,['AUTO','GENERAL'],true))throw new ApiException(422,'INVALID_DOCUMENT_TYPE','Code invalide ou réservé.');return $c;}
function publicType(array $t):array{$s=json_decode((string)($t['extraction_schema']??'{}'),true)?:[];return ['id'=>(int)$t['id'],'code'=>$t['code'],'name'=>$t['name'],'description'=>$t['description'],'extraction_schema'=>$s];}
function uuid():string{$d=random_bytes(16);$d[6]=chr((ord($d[6])&15)|64);$d[8]=chr((ord($d[8])&63)|128);return vsprintf('%s%s-%s-%s-%s-%s%s%s',str_split(bin2hex($d),4));}
