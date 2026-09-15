<?php
declare(strict_types=1);
namespace FidestIA\Services\DocumentIntelligence;
use FidestIA\Contracts\DocumentExtractorInterface;
use FidestIA\Services\DocumentIntelligence\Extractors\{GenericExtractor,InvoiceExtractor,QuoteExtractor};
final class ExtractorRegistry
{
    /** @var list<DocumentExtractorInterface> */ private array $extractors;
    public function __construct(?array $extractors=null){$this->extractors=$extractors??[new InvoiceExtractor(),new QuoteExtractor(),new GenericExtractor()];}
    public function for(string $type):DocumentExtractorInterface{foreach($this->extractors as $extractor)if($extractor->supports($type))return $extractor;return new GenericExtractor();}
}
