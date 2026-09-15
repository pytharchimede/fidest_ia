<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use FidestIA\Services\{ApiKeyService,DocumentClassifierService};
final class DocumentPlatformTest extends TestCase
{
    public function testApiKeyGenerationAndHashVerification():void{$s=new ApiKeyService();$k=$s->generate('production');self::assertStringStartsWith('fia_live_',$k['plain']);self::assertTrue($s->isFormatValid($k['plain']));self::assertTrue($s->verify($k['plain'],$k['hash']));self::assertFalse($s->verify($k['plain'].'x',$k['hash']));self::assertSame(64,strlen($k['hash']));}
    public function testStagingUsesTestPrefix():void{$k=(new ApiKeyService())->generate('staging');self::assertStringStartsWith('fia_test_',$k['plain']);}
    public function testInvoiceFooterIbanDoesNotBecomeRib():void
    {
        $types=[['code'=>'RIB','extraction_schema'=>json_encode(['classification'=>['strong_keywords'=>['IBAN','BIC'],'minimum_score'=>2]])],['code'=>'INVOICE','extraction_schema'=>json_encode(['classification'=>['strong_keywords'=>['FACTURE','TOTAL TTC'],'minimum_score'=>2]])]];
        $result=(new DocumentClassifierService())->classify("FACTURE N 123\nTOTAL TTC 100000\n\nCoordonnees: IBAN CI00 BIC ABCD",$types);self::assertSame('INVOICE',$result['code']);
    }
}
