<?php

namespace FidestIA\Components;

final class DocumentUploader
{
    public static function render(string $inputName = 'document'): string
    {
        $safe = htmlspecialchars($inputName, ENT_QUOTES, 'UTF-8');
        return '<label class="drop" for="'.$safe.'"><strong>Déposer un document</strong><span>JPG, PNG, WEBP, TIFF ou PDF selon configuration</span></label><input id="'.$safe.'" name="'.$safe.'" type="file" required>';
    }
}
