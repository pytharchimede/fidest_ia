<?php

namespace FidestIA;

class DocumentClassifier
{
    public static function classify(string $filePath): array
    {
        $result = [
            'class' => 'autre',
            'signals' => [],
        ];

        // Basic file info
        $filename = basename($filePath);
        $result['signals'][] = 'filename=' . $filename;

        // get image size
        $w = $h = null;
        if (is_file($filePath)) {
            try {
                $info = @getimagesize($filePath);
                if ($info && isset($info[0], $info[1])) {
                    $w = (int)$info[0];
                    $h = (int)$info[1];
                    $result['signals'][] = 'size=' . $w . 'x' . $h;
                }
            } catch (\Throwable $e) {
                // ignore
            }
        }

        // EXIF (camera metadata -> photo de papier probable)
        $hasExifCamera = false;
        if (function_exists('exif_read_data')) {
            try {
                $exif = @exif_read_data($filePath, 0, true);
                if (is_array($exif)) {
                    $make = $exif['IFD0']['Make'] ?? null;
                    $model = $exif['IFD0']['Model'] ?? null;
                    $orientation = $exif['IFD0']['Orientation'] ?? null;
                    if ($make || $model || $orientation) {
                        $hasExifCamera = true;
                        $result['signals'][] = 'exif_camera=' . trim(($make ?: '') . ' ' . ($model ?: ''));
                    }
                }
            } catch (\Throwable $e) {
                // ignore
            }
        }

        $nameLower = mb_strtolower($filename);
        $looksLikeScreenshot = false;
        // filename hints for screenshots/pages
        $screenshotHints = ['screenshot', 'screen', 'capture', 'chrome', 'firefox', 'edge', 'whatsapp', 'katana'];
        foreach ($screenshotHints as $hints) {
            if (mb_strpos($nameLower, $hints) !== false) {
                $looksLikeScreenshot = true;
                $result['signals'][] = 'hint=' . $hints;
                break;
            }
        }

        // size/ratio hints (typical screen ratios and large widths)
        if ($w && $h) {
            $ratio = $w / max(1, $h);
            if ($w >= 900 && $ratio >= 1.3 && $ratio <= 2.6) {
                $looksLikeScreenshot = true;
                $result['signals'][] = 'ratio_hint=' . round($ratio, 2);
            }
        }

        if ($looksLikeScreenshot) {
            $result['class'] = 'screenshot_site_prix';
            return $result;
        }

        if ($hasExifCamera) {
            $result['class'] = 'photo_papier';
            return $result;
        }

        // fallback by extension (png often screenshots; jpg often photos) -> weak signal
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (in_array($ext, ['png', 'webp'])) {
            $result['signals'][] = 'ext_hint=' . $ext;
        }

        return $result;
    }
}
