<?php
declare(strict_types=1);
namespace FidestIA\Services\DocumentIntelligence;
use FidestIA\Contracts\AIProviderInterface;
final class NullAIProvider implements AIProviderInterface
{
    public function enabled():bool{return false;}
    public function enrich(array $analysis):array{return $analysis;}
}
