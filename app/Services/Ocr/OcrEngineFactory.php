<?php
declare(strict_types=1);
namespace FidestIA\Services\Ocr;
use FidestIA\Contracts\OcrEngineInterface;
use FidestIA\Core\ProcessRunner;
use RuntimeException;
final class OcrEngineFactory
{
    public function __construct(private readonly array $config,private readonly string $rootPath){}
    public function create():OcrEngineInterface
    {
        $driver=strtolower((string)($this->config['ocr']['driver']??'auto'));if(!in_array($driver,['auto','tesseract','embedded'],true))throw new RuntimeException('OCR_DRIVER invalide. Les moteurs php fictifs ne sont pas acceptés.');
        $binary=(string)($this->config['ocr']['binary']??'tesseract');$embedded=(string)($this->config['ocr']['embedded_binary']??$this->rootPath.'/tools/tesseract/tesseract.AppImage');
        $candidates=$driver==='embedded'?[$embedded]:($driver==='tesseract'?[$binary]:[$embedded,$binary]);
        foreach(array_unique($candidates) as $candidate){$engine=$this->build($candidate);if($engine->available())return $engine;}
        throw new RuntimeException('Aucun vrai moteur OCR disponible. Installez Tesseract ou configurez OCR_EMBEDDED_BINARY vers un AppImage exécutable.');
    }
    public function diagnostics():array
    {
        $embedded=(string)($this->config['ocr']['embedded_binary']??$this->rootPath.'/tools/tesseract/tesseract.AppImage');$system=(string)($this->config['ocr']['binary']??'tesseract');$e=$this->build($embedded);$s=$this->build($system);$active=null;try{$active=$this->create() instanceof TesseractOcrService?'tesseract':null;}catch(\Throwable){}return ['embedded'=>$e->available(),'tesseract'=>$s->available(),'active'=>$active,'pdf_converter'=>$this->converter()->activeConverter()];
    }
    private function build(string $binary):TesseractOcrService{return new TesseractOcrService($binary,(string)($this->config['ocr']['languages']??'fra+eng'),$this->converter(),new ImagePreprocessor(),new ProcessRunner((int)($this->config['ocr']['timeout']??120)));}
    private function converter():PdfToImageConverter{return new PdfToImageConverter(new ProcessRunner((int)($this->config['ocr']['timeout']??120)),(string)($this->config['pdf']['converter']??'auto'),(string)($this->config['pdf']['poppler_binary']??'pdftoppm'),(string)($this->config['pdf']['gs_binary']??'/bin/gs'),(string)($this->config['pdf']['imagemagick_binary']??'/bin/convert'),(int)($this->config['pdf']['dpi']??250),(int)($this->config['pdf']['max_pages']??20));}
}
