<?php
    namespace Naomai\Compactorium;

    class Slugger {
        public static function slugFromArtistAndAlbum(string $artist, string $album) {
            return self::slug($artist) . "--" . self::slug($album);
        }

        public static function slug(string $text): string {
            $value = preg_replace('/[^\pL\pN]+/u', '-', mb_strtolower($text));
            return trim($value, '-');
        }
    }