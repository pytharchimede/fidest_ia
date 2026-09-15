<?php
declare(strict_types=1);
namespace FidestIA\Contracts;
interface PdfToImageConverterInterface
{
    /** @return array{pages:list<string>,temporary_directory:string,converter:string} */
    public function convert(string $pdfPath): array;
    public function activeConverter(): ?string;
}
