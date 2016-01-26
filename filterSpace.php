<?php

class FilterSpace {
    public static function filter($file) {
        $string = file_get_contents($file);
        $reg = "/\/\*[\s\S]+\*\//";
        $i = 0;
        $str = "";
        $len = strlen($string);
        while ($i < $len) {
            $str .= $string[$i++];
            if (preg_match($reg, $str)) {
                $str = preg_replace($reg, "", $str);
            }
        }
        file_put_contents($file, $str);
    }
}
FilterSpace::filter("class.htmlParser.php");
