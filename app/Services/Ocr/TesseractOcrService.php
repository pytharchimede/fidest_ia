<?php
declare(strict_types=1);
namespace FidestIA\Services\Ocr;
use FidestIA\Contracts\{OcrEngineInterface,PdfToImageConverterInterface};
use FidestIA\Core\ProcessRunner;
use RuntimeException;

final class TesseractOcrService implements OcrEngineInterface
{
    public function __construct(
        private readonly string $binary='tesseract',
        private readonly string $languages='fra+eng',
        private readonly ?PdfToImageConverterInterface $pdfConverter=null,
        private readonly ?ImagePreprocessor $preprocessor=null,
        private readonly ?ProcessRunner $runner=null,
        private readonly int $ompThreadLimit=1
    ) {}

    public function extract(string $absolutePath,string $mimeType):array
    {
        if(!is_file($absolutePath)) throw new RuntimeException('Document introuvable pour OCR.');
        if(!$this->available()) throw new RuntimeException('Aucun moteur OCR exécutable : configurez OCR_BINARY vers Tesseract ou son runtime embarqué.');

        $pages=[$absolutePath]; $temporaryDirectory=null; $converter=null;
        if($mimeType==='application/pdf'){
            if(!$this->pdfConverter) throw new RuntimeException('Aucun convertisseur PDF configuré.');
            $converted=$this->pdfConverter->convert($absolutePath);
            $pages=$converted['pages']; $temporaryDirectory=$converted['temporary_directory']; $converter=$converted['converter'];
        }

        $texts=[]; $prepared=[];
        try {
            foreach($pages as $index=>$page){
                $input=$this->preprocessor?->prepare($page)??$page;
                if($input!==$page) $prepared[]=$input;
                $text=$this->ocrImage($input);
                $texts[]=['number'=>$index+1,'text'=>$text];
                if($input!==$page){ @unlink($input); array_pop($prepared); }
                if($mimeType==='application/pdf' && is_file($page)) @unlink($page);
            }
            $full=count($texts)===1?$texts[0]['text']:implode("\n\n",array_map(fn($p)=>'--- PAGE '.$p['number']." ---\n".$p['text'],$texts));
            return ['text'=>trim($full),'confidence'=>$this->estimateConfidence($texts),'meta'=>[
                'engine'=>'tesseract','languages'=>$this->languages,'source'=>$mimeType==='application/pdf'?'pdf':'image',
                'pages'=>count($pages),'page_results'=>$texts,'pdf_converter'=>$converter,
                'omp_thread_limit'=>$this->ompThreadLimit,'confidence_method'=>'deterministic_text_quality_heuristic'
            ]];
        } finally {
            foreach($prepared as $file) @unlink($file);
            if($temporaryDirectory){ foreach(glob($temporaryDirectory.'/*')?:[] as $file) if(is_file($file)) @unlink($file); @rmdir($temporaryDirectory); }
        }
    }

    public function available():bool
    {
        if(str_contains($this->binary,'/')) return is_file($this->binary)&&is_executable($this->binary);
        try{$r=$this->process()->run(['sh','-c','command -v "$1"','fidest',$this->binary]);return $r['exit_code']===0&&trim($r['stdout'])!=='';}catch(\Throwable){return false;}
    }
    public function engineName():string{return 'tesseract';}

    private function ocrImage(string $path):string
    {
        $env=['OMP_THREAD_LIMIT'=>(string)max(1,$this->ompThreadLimit),'OMP_NUM_THREADS'=>(string)max(1,$this->ompThreadLimit)];
        $result=$this->process()->run([$this->binary,$path,'stdout','-l',$this->languages,'--psm','6'],$env);
        if($result['exit_code']!==0) {
            $detail=$result['stderr']!==''?$result['stderr']:'processus terminé avec le code '.$result['exit_code'];
            throw new RuntimeException('Échec OCR Tesseract : '.$detail);
        }
        return trim($result['stdout']);
    }
    private function process():ProcessRunner{return $this->runner??new ProcessRunner(120);}
    private function estimateConfidence(array $pages):?float{$text=implode('',array_column($pages,'text'));if(trim($text)==='')return 0.0;$length=mb_strlen($text);$valid=mb_strlen(preg_replace('/[^\pL\pN\pP\pZ\r\n]/u','',$text)??'');$quality=$valid/max(1,$length);$lengthFactor=min(1,$length/250);return round(min(.92,max(.25,($quality*.65)+($lengthFactor*.27))),2);}
}
