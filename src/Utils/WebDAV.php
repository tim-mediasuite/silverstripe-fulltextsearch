<?php
namespace SilverStripe\FullTextSearch\Utils;

class WebDAV
{
    public static function curl_init($url, $method)
    {
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        return $ch;
    }

    public static function exists($url)
    {
        // WebDAV expects that checking a directory exists has a trailing slash
        if (substr($url, -1) != '/') {
            $url .= '/';
        }

        $ch = self::curl_init($url, 'PROPFIND');

        curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $err = curl_error($ch);

        curl_close($ch);

        if ($code == 404) {
            return false;
        }
        if ($code == 200 || $code == 207) {
            return true;
        }

        user_error("Got error from webdav server - " . $err, E_USER_ERROR);
    }

    public static function mkdir($url)
    {
        $ch = self::curl_init(rtrim($url, '/') . '/', 'MKCOL');

        curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $code == 201;
    }

    public static function put($handle, $url)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);

        curl_setopt($ch, CURLOPT_PUT, true);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);

        curl_setopt($ch, CURLOPT_INFILE, $handle);

        curl_exec($ch);
        fclose($handle);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $code;
    }

    public static function upload_from_string($string, $url)
    {
        $fh = tmpfile();
        fwrite($fh, $string);
        fseek($fh, 0);
        return self::put($fh, $url);
    }

    public static function upload_from_file($string, $url)
    {
        return self::put(fopen($string, 'rb'), $url);
    }
}
