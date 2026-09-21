<?php
declare(strict_types=1);
namespace FidestIA\Services\Ocr;
use FidestIA\Contracts\OcrEngineInterface;
use FidestIA\Core\{ProcessRunner,ServerResourceGuard};
use RuntimeException;

final class OcrEngineFactory
{
    public function __construct(private readonly array $config,private readonly string $rootPath){}
    public function create():OcrEngineInterface
    {
        $driver=strtolower((string)($this->config['ocr']['driver']??'auto'));
        if(!in_array($driver,['auto','tesseract','embedded'],true)) throw new RuntimeException('OCR_DRIVER invalide.');
        $binary=(string)($this->config['ocr']['binary']??'tesseract');
        $embedded=(string)($this->config['ocr']['embedded_binary']??$this->rootPath.'/tools/tesseract/squashfs-root/AppRun');
        $candidates=$driver==='embedded'?[$embedded]:($driver==='tesseract'?[$binary]:[$embedded,$binary]);
        foreach(array_unique($candidates) as $candidate){$engine=$this->build($candidate);if($engine->available())return $engine;}
        throw new RuntimeException('Aucun vrai moteur OCR disponible. Installez Tesseract ou configurez OCR_EMBEDDED_BINARY vers un exécutable valide.');
    }

    public function diagnostics():array
    {
        $embedded=(string)($this->config['ocr']['embedded_binary']??$this->rootPath.'/tools/tesseract/squashfs-root/AppRun');
        $system=(string)($this->config['ocr']['binary']??'tesseract');
        $e=$this->build($embedded);$s=$this->build($system);$active=null;
        try{$active=$this->create() instanceof TesseractOcrService?'tesseract':null;}catch(\Throwable){}
        return ['embedded'=>$e->available(),'tesseract'=>$s->available(),'active'=>$active,'pdf_converter'=>$this->converter()->activeConverter(),'resources'=>$this->resourceGuard()->status()];
    }

    private function build(string $binary):TesseractOcrService
    {
        $shared=(bool)($this->config['ocr']['shared_hosting_mode']??false);
        $preprocessor=new ImagePreprocessor(
            (bool)($this->config['ocr']['optimize_documents']??true),
            (int)($this->config['ocr']['max_image_width']??1400),
            $shared
        );
        return new TesseractOcrService(
            $binary,(string)($this->config['ocr']['languages']??'fra+eng'),
            $this->converter(),$preprocessor,
            new ProcessRunner((int)($this->config['ocr']['timeout']??120)),
            (int)($this->config['ocr']['omp_thread_limit']??1),
            $shared ? (string)($this->config['ocr']['lock_file']??$this->rootPath.'/storage/locks/ocr.lock') : null,
            (int)($this->config['ocr']['lock_wait_seconds']??2),
            $this->resourceGuard()
        );
    }

    private function resourceGuard():ServerResourceGuard
    {
        $cfg=$this->config['resources']??[];
        return new ServerResourceGuard(
            (bool)($cfg['guard_enabled']??true),
            (float)($cfg['max_load_per_cpu']??1.20),
            (int)($cfg['max_memory_percent']??85),
            (int)($cfg['retry_after_seconds']??15)
        );
    }

    private function converter():PdfToImageConverter
    {
        $shared=(bool)($this->config['ocr']['shared_hosting_mode']??false);
        $dpi=$shared?(int)($this->config['pdf']['shared_hosting_dpi']??100):(int)($this->config['pdf']['dpi']??150);
        return new PdfToImageConverter(
            new ProcessRunner((int)($this->config['ocr']['timeout']??120)),
            (string)($this->config['pdf']['converter']??'auto'),
            (string)($this->config['pdf']['poppler_binary']??'pdftoppm'),
            (string)($this->config['pdf']['gs_binary']??'/bin/gs'),
            (string)($this->config['pdf']['imagemagick_binary']??'/bin/convert'),
            max(72,$dpi),(int)($this->config['pdf']['max_pages']??20)
        );
    }
}
