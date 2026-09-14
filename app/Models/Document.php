<?php

namespace FidestIA\Models;

final class Document
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $uuid,
        public readonly string $documentTypeCode,
        public readonly string $originalName,
        public readonly string $mimeType,
        public readonly string $status,
        public readonly array $extractedData = []
    ) {}
}
