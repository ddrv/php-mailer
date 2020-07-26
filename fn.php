<?php

if (!function_exists('random_int')) {

    /**
     * @param int $min
     * @param int $max
     * @return int
     */
    function random_int($min, $max)
    {
        return rand((int)$min, (int)$max);
    }
}

if (!function_exists('mime_content_type')) {

    /**
     * @param string $filename
     * @return string
     */
    function mime_content_type($filename)
    {
        if (function_exists('finfo_open')) {
            /** @noinspection PhpComposerExtensionStubsInspection */
            $info = finfo_open(FILEINFO_MIME);
            /** @noinspection PhpComposerExtensionStubsInspection */
            $mime = finfo_file($info, $filename);
            /** @noinspection PhpComposerExtensionStubsInspection */
            finfo_close($info);
            return $mime;
        }
        $types = array(
            'txt' => 'text/plain',
            'htm' => 'text/html',
            'html' => 'text/html',
            'php' => 'text/html',
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'xml' => 'application/xml',
            'swf' => 'application/x-shockwave-flash',
            'flv' => 'video/x-flv',

            // images
            'png' => 'image/png',
            'jpe' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            'ico' => 'image/vnd.microsoft.icon',
            'tiff' => 'image/tiff',
            'tif' => 'image/tiff',
            'svg' => 'image/svg+xml',
            'svgz' => 'image/svg+xml',

            // archives
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed',
            'exe' => 'application/x-msdownload',
            'msi' => 'application/x-msdownload',
            'cab' => 'application/vnd.ms-cab-compressed',

            // audio/video
            'mp3' => 'audio/mpeg',
            'qt' => 'video/quicktime',
            'mov' => 'video/quicktime',

            // adobe
            'pdf' => 'application/pdf',
            'psd' => 'image/vnd.adobe.photoshop',
            'ai' => 'application/postscript',
            'eps' => 'application/postscript',
            'ps' => 'application/postscript',

            // ms office
            'doc' => 'application/msword',
            'rtf' => 'application/rtf',
            'xls' => 'application/vnd.ms-excel',
            'ppt' => 'application/vnd.ms-powerpoint',

            // open office
            'odt' => 'application/vnd.oasis.opendocument.text',
            'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
        );
        $arr = explode('.', $filename);
        $ext = strtolower(array_pop($arr));
        if (array_key_exists($ext, $types)) {
            return $types[$ext];
        }
        return 'application/octet-stream';
    }
}

/**
 * @param string $fileOrContents
 * @return string
 */
function ddrv_mailer_define_mime_type($fileOrContents)
{
    if (file_exists($fileOrContents)) {
        return mime_content_type($fileOrContents);
    }
    if (function_exists('finfo_open')) {
        /** @noinspection PhpComposerExtensionStubsInspection */
        $info = finfo_open(FILEINFO_MIME);
        /** @noinspection PhpComposerExtensionStubsInspection */
        $mime = finfo_buffer($info, $fileOrContents);
        /** @noinspection PhpComposerExtensionStubsInspection */
        finfo_close($info);
        return $mime;
    }
    return 'application/octet-stream';
}

function ddrv_mailer_encode_mime_header($string, $offset = 0)
{
    $max = 74;
    $map = array(
        '=00', '=01', '=02', '=03', '=04', '=05', '=06', '=07', '=08', '=09', '=0A', '=0B', '=0C', '=0D', '=0E', '=0F',
        '=10', '=11', '=12', '=13', '=14', '=15', '=16', '=17', '=18', '=19', '=1A', '=1B', '=1C', '=1D', '=1E', '=1F',
        '=20', '=21', '=22', '=23', '=24', '=25', '=26', '=27', '=28', '=29', '=2A', '=2B', '=2C', '=2D', '=2E', '=2F',
        '=30', '=31', '=32', '=33', '=34', '=35', '=36', '=37', '=38', '=39', '=3A', '=3B', '=3C', '=3D', '=3E', '=3F',
        '=40', '=41', '=42', '=43', '=44', '=45', '=46', '=47', '=48', '=49', '=4A', '=4B', '=4C', '=4D', '=4E', '=4F',
        '=50', '=51', '=52', '=53', '=54', '=55', '=56', '=57', '=58', '=59', '=5A', '=5B', '=5C', '=5D', '=5E', '=5F',
        '=60', '=61', '=62', '=63', '=64', '=65', '=66', '=67', '=68', '=69', '=6A', '=6B', '=6C', '=6D', '=6E', '=6F',
        '=70', '=71', '=72', '=73', '=74', '=75', '=76', '=77', '=78', '=79', '=7A', '=7B', '=7C', '=7D', '=7E', '=7F',
        '=80', '=81', '=82', '=83', '=84', '=85', '=86', '=87', '=88', '=89', '=8A', '=8B', '=8C', '=8D', '=8E', '=8F',
        '=90', '=91', '=92', '=93', '=94', '=95', '=96', '=97', '=98', '=99', '=9A', '=9B', '=9C', '=9D', '=9E', '=9F',
        '=A0', '=A1', '=A2', '=A3', '=A4', '=A5', '=A6', '=A7', '=A8', '=A9', '=AA', '=AB', '=AC', '=AD', '=AE', '=AF',
        '=B0', '=B1', '=B2', '=B3', '=B4', '=B5', '=B6', '=B7', '=B8', '=B9', '=BA', '=BB', '=BC', '=BD', '=BE', '=BF',
        '=C0', '=C1', '=C2', '=C3', '=C4', '=C5', '=C6', '=C7', '=C8', '=C9', '=CA', '=CB', '=CC', '=CD', '=CE', '=CF',
        '=D0', '=D1', '=D2', '=D3', '=D4', '=D5', '=D6', '=D7', '=D8', '=D9', '=DA', '=DB', '=DC', '=DD', '=DE', '=DF',
        '=E0', '=E1', '=E2', '=E3', '=E4', '=E5', '=E6', '=E7', '=E8', '=E9', '=EA', '=EB', '=EC', '=ED', '=EE', '=EF',
        '=F0', '=F1', '=F2', '=F3', '=F4', '=F5', '=F6', '=F7', '=F8', '=F9', '=FA', '=FB', '=FC', '=FD', '=FE', '=FF',
    );
    $symbols = str_split($string);
    unset($string);
    $result = '';
    $coding = false;
    $all = count($symbols);
    $position = 0;
    foreach ($symbols as $num => $symbol) {
        $line = '';
        $add = 0;
        $char = ord($symbol);
        $ascii = ($char >= 32 && $char <= 60) || ($char >= 62 && $char <= 126);
        if ($char === 32 && $num + 1 === $all) {
            $ascii = false;
        }
        if ($num < $offset) {
            $ascii = true;
            $coding = false;
        }
        if (!$coding && $char === 61 && preg_match('/;(\s+)?([a-z0-9\-]+)(\s+)?(=(\s+)?\"[^\"]+)?/ui', $result)) {
            $ascii = true;
        }
        if ($ascii) {
            if ($coding) {
                $coding = false;
                $line = '?=' . $symbol;
                $add = 3;
            } else {
                $line = $symbol;
                $add = 1;
            }
        } else {
            if (!$coding) {
                $coding = true;
                $line = '=?utf-8?Q?';
                $add = 10;
            }
            $line .= $map[$char];
            $add += 3;
        }
        if ($position + $add >= $max) {
            $line = "=\r\n $line";
            $position = $add + 1;
        }
        $result .= $line;
        $position += $add;
    }
    if ($coding) {
        $line = '?=';
        if ($position + 3 >= $max) {
            $line = "=\r\n $line";
        }
        $result .= $line;
    }
    return $result;
}
