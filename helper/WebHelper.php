<?php

class WebHelper
{
    public $opts = [];
    public $devMode = false;

    public $excluded = [
        'html',
        'php',
        'asp',
        'aspx',
        'jsp',
        'cfm',
        'jsx',
        'shtml',
        'rhtml'
    ];

    public $savePath = __DIR__ . '/../processed/';

    public function __construct($argv)
    {
        foreach ($argv as $arg) {
            if (str_contains($arg, '=')) {
                $explode = explode('=', $arg);
                $this->opts[$explode[0]] = $explode[1];
            } else {
                $this->opts[$arg] = true;
            }
        }
    }

    public function displayBanner(string $base): WebHelper
    {
        echo file_get_contents($base . '/banner.txt');
        echo PHP_EOL . PHP_EOL;

        return $this;
    }

    public function setSavePath(string $path): WebHelper
    {
        $this->savePath = $path;

        return $this;
    }

    public function setDevMode(bool $devMode): WebHelper
    {
        $this->devMode = $devMode;

        return $this;
    }

    public function scrape(string $url): array
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER , false);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt($curl, CURLOPT_TIMEOUT, 2);
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $header_size);
        $body = substr($response, $header_size);
        curl_close($curl);

        if ($httpCode == 0) {
            $httpCode = 404;
        }

        return array(
            'code' => $httpCode,
            'header' => $header,
            'response_size' => strlen($response),
            'response' => $response
        );
    }

    public function getHash(string $html): string
    {
        $html = preg_replace('#<script(.*?)>(.*?)</script>#is', '',$html);
        $html = preg_replace('#<style(.*?)>(.*?)</style>#is', '',$html);
        $html = strip_tags($html);
        $html = preg_replace('/\s+/', '', $html);

        if ($this->devModedev) {
            file_put_contents('processed/' . md5($html) . '.txt', $html);
        }

        return md5($html);
    }

    public function allowedTypes(string $extention): bool
    {
        if ($extention == '') {
            return true;
        }

        return in_array($extention, $this->excluded);
    }

    public function saveReport(string $filename, string $data, string $ext): void
    {
        try {
            file_put_contents($this->savePath . $filename . '.' . $ext, $data);
        } catch (Exception $ex) {
            echo $ex->getMessage();
        }
    }
}