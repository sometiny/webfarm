<?php


namespace Jazor\WebFarm;


class HttpResponse
{
    public static function end(int $code, string $message){

        header(HttpStatus::getStatusHeader($code));
        echo $message;
        exit(0);
    }
}
