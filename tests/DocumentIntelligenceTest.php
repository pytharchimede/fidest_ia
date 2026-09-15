<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use FidestIA\Services\DocumentIntelligence\{AnomalyDetector,ConfidenceCalculator,ExtractorRegistry,TextNormalizer};
final class DocumentIntelligenceTest extends TestCase
{
    public function testNormalizesWhitespaceWithoutChangingRawText():void{$raw="FACTURE  N°  12\r\n\r\nTOTAL TTC";$normalized=(new TextNormalizer())->normalize($raw);self::assertSame("FACTURE N° 12\n\nTOTAL TTC",$normalized);self::assertStringContainsString("  ",$raw);}
    /** @dataProvider amounts */ public function testIvorianAmounts(string $raw,float $expected):void{self::assertSame($expected,(new TextNormalizer())->amount($raw));}
    public static function amounts():array{return [['1 250 000 FCFA',1250000.0],['1.250.000 CFA',1250000.0],['1,250,000 XOF',1250000.0]];}
    public function testFrenchAndNumericDates():void{$n=new TextNormalizer();self::assertSame('2026-09-15',$n->date('15/09/2026'));self::assertSame('2026-09-15',$n->date('15 septembre 2026'));self::assertNull($n->date('31/02/2026'));}
    public function testInvoiceExtractionKeepsEvidenceAndConfidence():void{$f=(new ExtractorRegistry())->for('FNE_INVOICE')->extract("FACTURE N° FAC-42\nTOTAL HT 1 000 000 FCFA\nTVA 180 000 FCFA\nTOTAL TTC 1 180 000 FCFA");self::assertSame('FAC-42',$f['invoice_number']['value']);self::assertSame(1180000.0,$f['amount_ttc']['value']);self::assertNotEmpty($f['amount_ttc']['source']);}
    public function testQuoteExtractor():void{$f=(new ExtractorRegistry())->for('QUOTE')->extract("DEVIS N° DEV-9\nTOTAL TTC 50 000");self::assertSame('DEV-9',$f['quote_number']['value']);}
    public function testMathematicalAnomaly():void{$fields=['amount_ht'=>['value'=>1000],'amount_tax'=>['value'=>180],'amount_ttc'=>['value'=>1300]];$a=(new AnomalyDetector())->detect($fields,'INVOICE',.9,.9);self::assertSame('TOTAL_MISMATCH',$a[0]['code']);}
    public function testDeterministicScores():void{$s=(new ConfidenceCalculator())->calculate(.8,.9,['x'=>['confidence'=>.7]],['results'=>[]],[]);self::assertSame(.84,$s['overall']);}
    public function testUnknownDocumentUsesGenericExtractor():void{$f=(new ExtractorRegistry())->for('UNKNOWN')->extract('Référence : DOC-2026');self::assertSame('DOC-2026',$f['document_number']['value']);}
}
