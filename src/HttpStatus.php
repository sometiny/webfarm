<?php


namespace Jazor\WebFarm;


class HttpStatus
{
    private static array $httpStatusDescriptions = [
        null,
        ["Continue", "Switching Protocols", "Processing"],
        ["OK", "Created", "Accepted", "Non-Authoritative Information", "No Content", "Reset Content", "Partial Content", "Multi-Status"],
        ["Multiple Choices", "Moved Permanently", "Found", "See Other", "Not Modified", "Use Proxy", null, "Temporary Redirect"],
        ["Bad Request", "Unauthorized", "Payment Required", "Forbidden", "Not Found", "Method Not Allowed", "Not Acceptable", "Proxy Authentication Required", "Request Timeout", "Conflict", "Gone", "Length Required", "Precondition Failed", "Request Entity Too Large", "Request-Uri Too Long", "Unsupported Media Type",
            "Requested Range Not Satisfiable", "Expectation Failed", null, null, null, null, "Unprocessable Entity", "Locked", "Failed Dependency"],
        ["Internal Server Error", "Not Implemented", "Bad Gateway", "Service Unavailable", "Gateway Timeout", "Http Version Not Supported", null, "Insufficient Storage"],
    ];

    public static function getStatus(int $code): ?string
    {
        $main = floor($code / 100);
        $sub = $code % 100;
        if ($main <= 0 || $main > 5) return null;

        $texts = self::$httpStatusDescriptions[$main];
        if ($sub < 0 || $sub >= count($texts)) return null;

        return $texts[$sub];
    }

    public static function getStatusHeader(int $code): string
    {
        return sprintf('HTTP/1.1 %s %s', $code, self::getStatus($code));
    }
}
