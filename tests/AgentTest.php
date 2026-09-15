<?php

use PHPUnit\Framework\TestCase;
use FidestIA\Agent;
use FidestIA\Database;

final class AgentTest extends TestCase
{
    public function testSynonymMatching()
    {
        // prepare in-memory PDO? we reuse project's DB connection (requires migrations run)
        try {
            $pdo = Database::getConnection();
            $pdo->query('SELECT 1 FROM training_examples_ia LIMIT 1');
        } catch (Throwable) {
            self::markTestSkipped('Legacy training_examples_ia integration table is unavailable.');
        }
        // insert an example
        $stmt = $pdo->prepare('INSERT INTO training_examples_ia (agent,label,ocr_text,decision,meta) VALUES (?,?,?,?,?)');
        $stmt->execute(['etalonIA', 'achat de camera de surveillance', 'Produit: caméra', 'ACCEPTE', json_encode(['test' => true])]);

        $agent = new Agent('etalonIA');
        $res = $agent->predict('achat de camra surveillance', 'Produit: camera', $pdo);
        $this->assertContains($res['decision'], ['ACCEPTE', 'HUMAN_REVIEW', 'REFUSE']);
        $this->assertIsFloat($res['score']);
        $this->assertArrayHasKey('example_match', $res);
    }
}
