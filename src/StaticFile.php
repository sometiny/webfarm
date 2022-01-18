<?php


namespace Jazor\WebFarm;


class StaticFile
{
    private const DATE_FORMAT = 'D, d M Y H:i:s \G\M\T';

    /**
     * @param string $extension
     * @return bool
     */
    public static function isStaticFile(string $extension): bool
    {
        return isset(self::$staticExtensions[ltrim($extension, '.')]);
    }

    private static function sendHeader($mime, $statusCode, $eTag, $lastModified)
    {
        header(HttpStatus::getStatusHeader($statusCode));
        if ($statusCode === 200 && !empty($mime)) {
            header('Content-Type:' . $mime);
        }
        if ($lastModified) {
            header('Expires: ' . (new \DateTime())->setTimezone(new \DateTimeZone('+0000'))->add(new \DateInterval('PT10M'))->format(self::DATE_FORMAT));
            header('Last-Modified: ' . $lastModified->format(self::DATE_FORMAT));
        }
        if ($eTag) header('ETag: ' . $eTag);
    }

    public static function send(string $file, string $uri)
    {
        if (!is_file($file)) {
            HttpResponse::end(404, 'file not found: ' . $uri);
        }


        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

        $mime = self::$staticExtensions[$extension] ?? null;
        if($mime === null){
            HttpResponse::end(404, 'file not found: ' . $uri);
        }

        $lastModify = filemtime($file);
        if ($lastModify === false) {
            $lastModify = filectime($file);
        }
        if ($lastModify === false) {
            @readfile($file);
            return;
        }
        $ifNoneMatch = $_SERVER['HTTP_IF_NONE_MATCH'] ?? null;
        $ifModifiedSince = $_SERVER['HTTP_IF_MODIFIED_SINCE'] ?? null;

        $eTag = substr(strtolower(md5($lastModify . '')), 4, 12);
        $eTag = sprintf('W/"%s-%s"', substr($eTag, 0, 8), substr($eTag, 8, 4));

        $lastModify = (new \DateTime())->setTimestamp($lastModify)->setTimezone(new \DateTimeZone('+0000'));

        if (!$ifNoneMatch && !$ifModifiedSince) {
            self::sendHeader($mime, 200, $eTag, $lastModify);
            @readfile($file);
            return;
        }

        if (!empty($ifNoneMatch)) {
            if ($ifNoneMatch === $eTag) {
                self::sendHeader($mime, 304, $eTag, null);
                return;
            }
            self::sendHeader($mime, 200, $eTag, null);
            @readfile($file);
            return;
        }

        $ifModifiedSince = \DateTime::createFromFormat(self::DATE_FORMAT, $ifModifiedSince);
        if ($ifModifiedSince === false) {
            self::sendHeader($mime, 200, null, $lastModify);
            @readfile($file);
            return;
        }


        if ($lastModify > $ifModifiedSince) {
            self::sendHeader($mime, 200, null, $lastModify);
            @readfile($file);
            return;
        }
        self::sendHeader($mime, 304, null, $lastModify);
    }

    /**
     * @param string|array $extension
     * @param string|null $typeName
     */
    public static function register($extension, $typeName)
    {
        if(is_array($extension)){
            foreach ($extension as $key => $value){
                if($typeName){
                    self::$staticExtensions[$value] = $typeName;
                    continue;
                }
                self::$staticExtensions[$key] = $value;
            }
            return;
        }
        self::$staticExtensions[$extension] = $typeName;
    }

    private static array $staticExtensions = [
        'html' => 'text/html',
        'htm' => 'text/html',
        'shtml' => 'text/html',
        'css' => 'text/css',
        'xml' => 'text/xml',
        'gif' => 'image/gif',
        'jpeg' => 'image/jpeg',
        'jpg' => 'image/jpeg',
        'js' => 'application/javascript',
        'atom' => 'application/atom+xml',
        'rss' => 'application/rss+xml',
        'mml' => 'text/mathml',
        'txt' => 'text/plain',
        'jad' => 'text/vnd.sun.j2me.app-descriptor',
        'wml' => 'text/vnd.wap.wml',
        'htc' => 'text/x-component',
        'png' => 'image/png',
        'svg' => 'image/svg+xml',
        'svgz' => 'image/svg+xml',
        'tif' => 'image/tiff',
        'tiff' => 'image/tiff',
        'wbmp' => 'image/vnd.wap.wbmp',
        'webp' => 'image/webp',
        'ico' => 'image/x-icon',
        'jng' => 'image/x-jng',
        'bmp' => 'image/x-ms-bmp',
        'woff' => 'application/font-woff',
        'jar' => 'application/java-archive',
        'war' => 'application/java-archive',
        'ear' => 'application/java-archive',
        'json' => 'application/json',
        'hqx' => 'application/mac-binhex40',
        'doc' => 'application/msword',
        'pdf' => 'application/pdf',
        'ps' => 'application/postscript',
        'eps' => 'application/postscript',
        'ai' => 'application/postscript',
        'rtf' => 'application/rtf',
        'm3u8' => 'application/vnd.apple.mpegurl',
        'kml' => 'application/vnd.google-earth.kml+xml',
        'kmz' => 'application/vnd.google-earth.kmz',
        'xls' => 'application/vnd.ms-excel',
        'eot' => 'application/vnd.ms-fontobject',
        'ppt' => 'application/vnd.ms-powerpoint',
        'odg' => 'application/vnd.oasis.opendocument.graphics',
        'odp' => 'application/vnd.oasis.opendocument.presentation',
        'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
        'odt' => 'application/vnd.oasis.opendocument.text',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'wmlc' => 'application/vnd.wap.wmlc',
        '7z' => 'application/x-7z-compressed',
        'cco' => 'application/x-cocoa',
        'jardiff' => 'application/x-java-archive-diff',
        'jnlp' => 'application/x-java-jnlp-file',
        'run' => 'application/x-makeself',
        'pl' => 'application/x-perl',
        'pm' => 'application/x-perl',
        'prc' => 'application/x-pilot',
        'pdb' => 'application/x-pilot',
        'rar' => 'application/x-rar-compressed',
        'rpm' => 'application/x-redhat-package-manager',
        'sea' => 'application/x-sea',
        'swf' => 'application/x-shockwave-flash',
        'sit' => 'application/x-stuffit',
        'tcl' => 'application/x-tcl',
        'tk' => 'application/x-tcl',
        'der' => 'application/x-x509-ca-cert',
        'pem' => 'application/x-x509-ca-cert',
        'crt' => 'application/x-x509-ca-cert',
        'xpi' => 'application/x-xpinstall',
        'xhtml' => 'application/xhtml+xml',
        'xspf' => 'application/xspf+xml',
        'zip' => 'application/zip',
        'bin' => 'application/octet-stream',
        'exe' => 'application/octet-stream',
        'dll' => 'application/octet-stream',
        'deb' => 'application/octet-stream',
        'dmg' => 'application/octet-stream',
        'iso' => 'application/octet-stream',
        'img' => 'application/octet-stream',
        'msi' => 'application/octet-stream',
        'msp' => 'application/octet-stream',
        'msm' => 'application/octet-stream',
        'mid' => 'audio/midi',
        'midi' => 'audio/midi',
        'kar' => 'audio/midi',
        'mp3' => 'audio/mpeg',
        'ogg' => 'audio/ogg',
        'm4a' => 'audio/x-m4a',
        'ra' => 'audio/x-realaudio',
        '3gpp' => 'video/3gpp',
        '3gp' => 'video/3gpp',
        'ts' => 'video/mp2t',
        'mp4' => 'video/mp4',
        'mpeg' => 'video/mpeg',
        'mpg' => 'video/mpeg',
        'mov' => 'video/quicktime',
        'webm' => 'video/webm',
        'flv' => 'video/x-flv',
        'm4v' => 'video/x-m4v',
        'mng' => 'video/x-mng',
        'asx' => 'video/x-ms-asf',
        'asf' => 'video/x-ms-asf',
        'wmv' => 'video/x-ms-wmv',
        'avi' => 'video/x-msvideo',
    ];
}
