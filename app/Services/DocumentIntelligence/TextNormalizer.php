<?php
declare(strict_types=1);
namespace FidestIA\Services\DocumentIntelligence;
final class TextNormalizer
{
    public function normalize(string $raw):string
    {
        $text=str_replace(["\r\n","\r","\xC2\xA0"],["\n","\n",' '],$raw);$text=preg_replace('/[^\P{C}\n\t]/u','',$text)??$text;$lines=[];
        foreach(explode("\n",$text) as $line){$line=trim((string)preg_replace('/[ \t]+/u',' ',$line));if($line!==''||end($lines)!=='')$lines[]=$line;}
        return trim(implode("\n",$lines));
    }
    public function amount(string $value):?float
    {
        $v=mb_strtoupper(trim($value));$v=str_replace(['FCFA','F CFA','CFA','XOF'], '',$v);$v=preg_replace('/[^0-9.,\- ]/u','',$v)??'';$v=trim($v);if($v==='')return null;
        $v=preg_replace('/(?<=\d)[ .](?=\d{3}(?:\D|$))/u','',$v)??$v;
        if(substr_count($v,',')>1&&preg_match('/,\d{3}(?:,\d{3})*$/',$v))$v=str_replace(',','',$v);elseif(str_contains($v,','))$v=str_replace(',','.',$v);
        $v=str_replace(' ','',$v);return is_numeric($v)?(float)$v:null;
    }
    public function date(string $value):?string
    {
        $months=['janvier'=>'01','février'=>'02','fevrier'=>'02','mars'=>'03','avril'=>'04','mai'=>'05','juin'=>'06','juillet'=>'07','août'=>'08','aout'=>'08','septembre'=>'09','octobre'=>'10','novembre'=>'11','décembre'=>'12','decembre'=>'12'];$v=mb_strtolower(trim($value));foreach($months as $name=>$number)$v=str_replace($name,$number,$v);
        foreach(['d/m/Y','d-m-Y','d.m.Y','d m Y','Y-m-d'] as $format){$d=\DateTimeImmutable::createFromFormat('!'.$format,$v);$errors=\DateTimeImmutable::getLastErrors();if($d&&($errors===false||($errors['warning_count']===0&&$errors['error_count']===0))&&$d->format($format)===$v)return $d->format('Y-m-d');}return null;
    }
}
