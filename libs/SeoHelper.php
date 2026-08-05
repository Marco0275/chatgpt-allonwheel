<?php
declare(strict_types=1);

class SeoHelper
{

    public static function slug(string $text): string
    {

        $text = strtolower($text);

        $text = iconv('UTF-8','ASCII//TRANSLIT',$text);

        $text = preg_replace('/[^a-z0-9]+/','-',$text);

        $text = trim($text,'-');

        $text = preg_replace('/-+/','-',$text);

        return $text;

    }

    public static function metaTitle(string $title): string
    {

        return mb_substr(trim($title),0,60);

    }

    public static function metaDescription(string $text): string
    {

        $text = strip_tags($text);

        $text = preg_replace('/\s+/',' ',$text);

        return mb_substr(trim($text),0,160);

    }

    public static function excerpt(string $text): string
    {

        $text = strip_tags($text);

        $text = preg_replace('/\s+/',' ',$text);

        return mb_substr(trim($text),0,250);

    }

}