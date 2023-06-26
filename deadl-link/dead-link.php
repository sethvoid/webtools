<?php
echo file_get_contents('banner.txt');
echo PHP_EOL . PHP_EOL;

function scrape($url) {
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
$url = null;
foreach ($argv as $arg) {
    if (stripos($arg, '--url') !== false) {
        $urlSplit = explode('=', $arg);
        $url = $urlSplit[1] ?? null;
    }
}

if (!empty($url)) {
    $baseAddress = $url;
} else {
    $baseAddress = readline('Please enter address (do not include protocal) :');
}

$url = 'https://' . $baseAddress . '/sitemap.xml';
$xml = file_get_contents($url);
$obj = simplexml_load_string($xml);

$amount = count($obj);
echo 'Identified ' . $amount . ' urls in site map' . PHP_EOL;

echo 'Running diagonstics to establish what bad looks like' . PHP_EOL;
$test1 = scrape('https://' . $baseAddress . '/blahblah');
$test2 = scrape('https://' . $baseAddress . '/blahbla');
$test3 = scrape('https://' . $baseAddress . '/blahbl');
$test4 = scrape('https://' . $baseAddress . '/blahb');


echo 'TEST1: ' . $test1['code'] . '[' . $test1['response_size'] . PHP_EOL;
echo 'TEST2: ' . $test2['code'] . '[' . $test2['response_size'] . PHP_EOL;
echo 'TEST3: ' . $test3['code'] . '[' . $test3['response_size'] . PHP_EOL;
echo 'TEST4: ' . $test4['code'] . '[' . $test4['response_size'] . PHP_EOL;

$error = [];

$cap = 10;
$counter = 0;
$additional = [];
$urls = [];

foreach ($obj as $loc) {
    $urls[(string)$loc->loc] = (string)$loc->loc;
}

foreach ($urls as $url) {
    $scrape = scrape($url);
    echo 'Checking ' . $url . '(' . $scrape['response_size'] . ')'.PHP_EOL;


    if ($scrape['code'] != 200) {
        $error[$url] = $scrape['code'];
    } else {
        echo 'Scanning for additional links' . PHP_EOL;
        $potentialLinks = explode('href="', $scrape['response']);

        foreach ($potentialLinks as $linkalue) {
            $linkalue = substr($linkalue, 0, strpos($linkalue, '"'));
            if (stripos($linkalue, $baseAddress) !== false && !isset($urls[$linkalue])) {
                $additional[$url] = $linkalue;
            }
        }
    }
}
foreach ($additional as $source => $links) {
    $scrape = scrape($links);
    if ($scrape['code'] != 200) {
        $error[$links] = $scrape['code'] . '(source url:' . $source . ')';
    }
}

$fileString = 'Dead links report: ' . $baseAddress . PHP_EOL;
$fileString .= '========================================================================' . PHP_EOL;

if (!empty($error)) {
    echo 'Found Errors' . PHP_EOL;
    echo '============================================================================================' . PHP_EOL;
    foreach ($error as $url => $code) {
        echo $url . ' returned ' . $code . PHP_EOL;
        $fileString .= $url . ' | return status ' . $code . PHP_EOL;
    }
}
file_put_contents('../processed/report-' . $baseAddress . '-' . date('d-m-Y') . '.txt', $fileString);

echo 'Script finished.' . PHP_EOL;