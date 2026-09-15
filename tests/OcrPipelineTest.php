<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use FidestIA\Core\ProcessRunner;
use FidestIA\Services\Ocr\{OcrEngineFactory,PdfToImageConverter};
final class OcrPipelineTest extends TestCase
{
    public function testNoEngineProducesControlledException():void{$this->expectException(RuntimeException::class);(new OcrEngineFactory(['ocr'=>['driver'=>'embedded','embedded_binary'=>'/missing'],'pdf'=>[]],dirname(__DIR__)))->create();}
    public function testTesseractSelectionWhenAvailable():void{if(!is_executable('/usr/bin/tesseract'))self::markTestSkipped('Tesseract absent');$engine=(new OcrEngineFactory(['ocr'=>['driver'=>'tesseract','binary'=>'/usr/bin/tesseract','languages'=>'eng'],'pdf'=>[]],dirname(__DIR__)))->create();self::assertSame('tesseract',$engine->engineName());}
    public function testGhostscriptFallbackAndMultipageLimit():void{if(!is_executable('/usr/bin/gs'))self::markTestSkipped('Ghostscript absent');$ps=tempnam(sys_get_temp_dir(),'fidest_ps_');$pdf=tempnam(sys_get_temp_dir(),'fidest_pdf_');file_put_contents($ps,"%!PS\n/Helvetica findfont 20 scalefont setfont\n50 700 moveto (PAGE ONE) show showpage\n50 700 moveto (PAGE TWO) show showpage\n");(new ProcessRunner())->run(['/usr/bin/gs','-dSAFER','-dBATCH','-dNOPAUSE','-dQUIET','-sDEVICE=pdfwrite','-sOutputFile='.$pdf,$ps]);try{$result=(new PdfToImageConverter(new ProcessRunner(),'ghostscript','/missing','/usr/bin/gs','/missing',150,2))->convert($pdf);self::assertSame('ghostscript',$result['converter']);self::assertCount(2,$result['pages']);foreach($result['pages'] as $page)@unlink($page);@rmdir($result['temporary_directory']);}finally{@unlink($ps);@unlink($pdf);}}
}
