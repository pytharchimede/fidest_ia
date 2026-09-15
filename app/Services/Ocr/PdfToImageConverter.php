<?php
declare(strict_types=1);
namespace FidestIA\Services\Ocr;
use FidestIA\Contracts\PdfToImageConverterInterface;
use FidestIA\Core\ProcessRunner;
use RuntimeException;
final class PdfToImageConverter implements PdfToImageConverterInterface
{
    public function __construct(private readonly ProcessRunner $runner,private readonly string $driver='auto',private readonly string $poppler='pdftoppm',private readonly string $ghostscript='/bin/gs',private readonly string $imageMagick='/bin/convert',private readonly int $dpi=250,private readonly int $maxPages=20){}
    public function activeConverter():?string{foreach($this->order() as [$name,$binary])if($this->executable($binary))return $name;return null;}
    public function convert(string $pdfPath):array
    {
        if(!is_file($pdfPath))throw new RuntimeException('PDF introuvable.');$active=$this->activeConverter();if($active===null)throw new RuntimeException('Conversion PDF indisponible : installez Poppler, Ghostscript ou ImageMagick.');
        $dir=rtrim(sys_get_temp_dir(),DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'fidest_pdf_'.bin2hex(random_bytes(12));if(!mkdir($dir,0700,true))throw new RuntimeException('Impossible de créer le dossier PDF temporaire.');
        try{$args=$this->arguments($active,$pdfPath,$dir);$result=$this->runner->run($args);if($result['exit_code']!==0)throw new RuntimeException('Échec conversion PDF avec '.$active.' : '.$result['stderr']);$pages=glob($dir.DIRECTORY_SEPARATOR.'page-*.png')?:[];natsort($pages);$pages=array_values($pages);if($pages===[])throw new RuntimeException('Le convertisseur PDF n’a produit aucune page.');if(count($pages)>$this->maxPages)throw new RuntimeException('Le PDF dépasse la limite de '.$this->maxPages.' pages.');return ['pages'=>$pages,'temporary_directory'=>$dir,'converter'=>$active];}catch(\Throwable $e){$this->cleanup($dir);throw $e;}
    }
    /** @return list<array{0:string,1:string}> */
    private function order():array{$all=[['poppler',$this->poppler],['ghostscript',$this->ghostscript],['imagemagick',$this->imageMagick]];if($this->driver==='auto')return $all;return array_values(array_filter($all,fn($item)=>$item[0]===$this->driver));}
    /** @return list<string> */
    private function arguments(string $driver,string $pdf,string $dir):array{return match($driver){'poppler'=>[$this->poppler,'-f','1','-l',(string)$this->maxPages,'-r',(string)$this->dpi,'-png',$pdf,$dir.'/page'],'ghostscript'=>[$this->ghostscript,'-dSAFER','-dBATCH','-dNOPAUSE','-dQUIET','-dFirstPage=1','-dLastPage='.$this->maxPages,'-r'.$this->dpi,'-sDEVICE=pnggray','-dTextAlphaBits=4','-sOutputFile='.$dir.'/page-%03d.png',$pdf],'imagemagick'=>[$this->imageMagick,'-limit','memory','256MiB','-limit','map','512MiB','-limit','disk','1GiB','-density',(string)$this->dpi,$pdf.'[0-'.($this->maxPages-1).']','-colorspace','Gray',$dir.'/page-%03d.png'],default=>throw new RuntimeException('Convertisseur PDF inconnu.')};}
    private function executable(string $binary):bool{if(str_contains($binary,'/'))return is_file($binary)&&is_executable($binary);try{$r=$this->runner->run(['sh','-c','command -v "$1"','fidest',$binary]);return $r['exit_code']===0&&trim($r['stdout'])!=='';}catch(\Throwable){return false;}}
    private function cleanup(string $dir):void{foreach(glob($dir.'/*')?:[] as $file)if(is_file($file))@unlink($file);@rmdir($dir);}
}
