<?php

use PHPUnit\Framework\TestCase;
use FidestIA\DecisionEngine;

final class DecisionEngineTest extends TestCase
{
    public function testAcceptWhenLabelAndPriceMatch()
    {
        $engine = new DecisionEngine();
        $ocr = "Facture\nProduit: camera de surveillance\nReference: XYZ";
        $res = $engine->decide('achat de camera de surveillance', 0, $ocr);
        $this->assertEquals('ACCEPTE', $res['decision']);
        $this->assertStringContainsString('LABEL_TOKENS', json_encode($res['reasons']));
    }

    public function testRefuseWhenPriceMismatch()
    {
        $engine = new DecisionEngine();
        $ocr = "Bon de commande\nProduit: chaise de bureau\nRef: 12";
        $res = $engine->decide('achat de camera', 0, $ocr);
        $this->assertEquals('REFUSE', $res['decision']);
        $this->assertStringContainsString('TEXT_MISMATCH', json_encode($res['reasons']));
    }

    public function testHumanReviewWhenPartialMatch()
    {
        $engine = new DecisionEngine();
        $ocr = "Facture\nProduit: materiel de surveillance\nReference: 55";
        $res = $engine->decide('achat de camera de surveillance', 0, $ocr, ['accept_ratio' => 0.7, 'review_ratio' => 0.2]);
        $this->assertEquals('HUMAN_REVIEW', $res['decision']);
        $this->assertStringContainsString('TEXT_AMBIGUOUS', json_encode($res['reasons']));
    }
}
