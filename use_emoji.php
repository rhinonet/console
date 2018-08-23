<?php
include_once "Emoji.php";
class use_emoji(){
    private function encode_comment($comment_detail)
    {
        $comment_detail = strip_tags($comment_detail);
        $comment_detail = preg_replace_callback('/(%[0-9A-F][0-9A-F]){1}/', function($match){
            return '['.$match[0].']';
        }, $comment_detail);
        $comment_detail = encodeEmoji($comment_detail);
        return $comment_detail;
    }

    private function decode_comment($comment_detail)
    {
        $comment_detail = decodeEmoji($comment_detail);
        $comment_detail = preg_replace_callback('/(\[%[0-9A-F][0-9A-F]\]){1}/', function($match){
            return substr($match[0], 1, 3);
        }, $comment_detail);
        return $comment_detail;
    }
}
