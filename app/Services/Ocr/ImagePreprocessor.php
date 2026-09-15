<?php
declare(strict_types=1);
namespace FidestIA\Services\Ocr;
use RuntimeException;
final class ImagePreprocessor
{
    public function prepare(string $source):string
    {
        if(!extension_loaded('gd'))return $source;$info=@getimagesize($source);if(!$info||($info[0]*$info[1])>40000000)throw new RuntimeException('Dimensions de l’image trop importantes.');
        $image=match($info[2]){IMAGETYPE_JPEG=>@imagecreatefromjpeg($source),IMAGETYPE_PNG=>@imagecreatefrompng($source),IMAGETYPE_WEBP=>@imagecreatefromwebp($source),default=>false};if(!$image)return $source;
        try{if($info[2]===IMAGETYPE_JPEG&&function_exists('exif_read_data')){$exif=@exif_read_data($source);$orientation=(int)($exif['Orientation']??1);$angle=match($orientation){3=>180,6=>-90,8=>90,default=>0};if($angle!==0){$rotated=imagerotate($image,$angle,0);if($rotated!==false){imagedestroy($image);$image=$rotated;}}}imagefilter($image,IMG_FILTER_GRAYSCALE);imagefilter($image,IMG_FILTER_CONTRAST,-12);$width=imagesx($image);if($width<1600){$scaled=imagescale($image,1600,-1,IMG_BICUBIC_FIXED);if($scaled!==false){imagedestroy($image);$image=$scaled;}}imagefilter($image,IMG_FILTER_SMOOTH,-2);$target=rtrim(sys_get_temp_dir(),DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'fidest_ocr_'.bin2hex(random_bytes(12)).'.png';if(!imagepng($image,$target,6))throw new RuntimeException('Échec du prétraitement OCR.');@chmod($target,0600);return $target;}finally{imagedestroy($image);}
    }
}
