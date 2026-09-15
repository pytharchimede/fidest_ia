<?php
declare(strict_types=1);
namespace FidestIA\Contracts;
interface AIProviderInterface
{
    public function enabled():bool;
    public function enrich(array $analysis):array;
}
