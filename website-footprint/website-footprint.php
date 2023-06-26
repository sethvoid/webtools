<?php
echo file_get_contents('banner.txt');
echo PHP_EOL . PHP_EOL;

/**
 * Scrape using in built curl request.
 * @param $url
 * @return array
 */
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

/**
 *
 * Returns hash of site content, please remember it removes scripts and style tags.
 * @param $html
 * @param $dev
 * @return string
 */
function getHash($html, $dev) {
    $html = preg_replace('#<script(.*?)>(.*?)</script>#is', '',$html);
    $html = preg_replace('#<style(.*?)>(.*?)</style>#is', '',$html);
    $html = strip_tags($html);
    $html = preg_replace('/\s+/', '', $html);

    if ($dev) {
        file_put_contents('processed/' . md5($html) . '.txt', $html);
    }

    return md5($html);
}

function allowedTypes($extention) {
    if ($extention == '') {
        return true;
    }
    
    $excluded = [
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

    return in_array($extention, $excluded);
}

$dev = in_array('--dev', $argv);
$compare = in_array('--compare', $argv);
$argumentBaseAddress = null;
$argumentBaseFile = null;

foreach ($argv as $arg) {
    if (stripos($arg, '--url') !== false) {
        $splitArg = explode('=', $arg);
        if (isset($splitArg[1]) && strlen($splitArg[1]) > 1) {
            $argumentBaseAddress = $splitArg[1];
        }
    }

    if (stripos($arg, '--file') !== false) {
        $splitArg = explode('=', $arg);
        if (isset($splitArg[1]) && strlen($splitArg[1]) > 1) {
            $argumentBaseFile = $splitArg[1];
        }
    }
}
if (!empty($argumentBaseAddress)) {
    $baseAddress = $argumentBaseAddress;
} else {
    $baseAddress = readline('Please enter address (do not include protocal) :');
}

$comparisonFile = null;
if ($compare) {
    if (!empty($argumentBaseFile)) {
        $comparisonFile = $argumentBaseFile;
    } else {
        $comparisonFile = readline('Please enter comparison file: ');
    }
}
$url = 'https://' . $baseAddress . '/sitemap.xml';
$xml = file_get_contents($url);
$obj = simplexml_load_string($xml);

$amount = count($obj);

$footprints = [];
foreach ($obj as $loc) {
    $urls[(string)$loc->loc] = (string)$loc->loc;
}

$counter = 0;
foreach ($urls as $url) {
    $scrape = scrape($url);
    echo 'Checking ' . $url . '(' . $scrape['response_size'] . ')'.PHP_EOL;
    if ($scrape['code'] == 200) {
        $hashStr = getHash($scrape['response'], $dev);
        echo 'Generating hash ' . $url .PHP_EOL;
        $footprints[$url] = $hashStr;

        $potentialLinks = explode('href="', $scrape['response']);
        foreach ($potentialLinks as $linkalue) {
            $linkalue = substr($linkalue, 0, strpos($linkalue, '"'));
            $ext = pathinfo($linkalue, PATHINFO_EXTENSION);
            if (stripos($linkalue, $baseAddress) !== false && !isset($urls[$linkalue]) && allowedTypes($ext)) {
                $scrape2 = scrape($linkalue);
                if ($scrape2['code'] == 200) {
                    echo 'Generating Linked hash ' . $linkalue .PHP_EOL;
                    $hashSt = getHash($scrape2['response'], $dev);
                    $footprints[$linkalue] = $hashSt;
                }
            }
        }
    }
    $counter++;

    if ($dev && $counter > 5) {
        break;
    }
}

if ($compare) {
    echo 'starting comparison' . PHP_EOL;
    $lost = [];
    $incorrect = [];
    $correct = [];
    try {
        $comparisonHashes = json_decode(file_get_contents(__DIR__ . '/processed/' . $comparisonFile), true);
        foreach ($comparisonHashes as $url => $hash) {
            if (isset($footprints[$url])) {
                if ($footprints[$url] !== $hash) {
                    $incorrect[$url] = '[old:' . $hash . '][new: '. $footprints[$url] . ']';
                } else {
                    $correct[$url] = $url;
                }
            } else {
                $lost[$url] = $url;
            }
        }
        echo 'Generating report';
        $reportString = 'FOOTPRINT COMPARISON REPORT ORIGINAL FILE ' . $comparisonFile. ' TODAYS: ' . date('Y-m-d-H-i-s') . PHP_EOL;
        $reportString .= '================================================================================================================' . PHP_EOL;
        $reportString .= 'LOST (no longer present)' . PHP_EOL;
        if (count($lost) == 0) {
            $reportString .= 'NONE FOUND' . PHP_EOL;
        }
        foreach ($lost as $loster) {
            $reportString .= '[' . $loster . ']' . PHP_EOL;
        }
        $reportString .= '================================================================================================================' . PHP_EOL;
        $reportString .= 'CHANGED' . PHP_EOL;
        if (count($incorrect) == 0) {
            $reportString .= 'NONE FOUND' . PHP_EOL;
        }
        foreach ($incorrect as $url => $inco) {
            $reportString .= '[' . $url . '][' . $inco . ']' . PHP_EOL;
        }
        $reportString .= '================================================================================================================' . PHP_EOL;
        $reportString .= 'CORRECT' . PHP_EOL;
        foreach ($correct as $url) {
            $reportString .= '[' . $url . ']' . PHP_EOL;
        }

        file_put_contents('../processed/change-report-'  . $baseAddress . '-' . date('d-m-Y') . '.txt', $reportString);
    } catch (Exception $ex) {
        echo $ex->getMessage();
    }
} else {
    echo 'saving content' . PHP_EOL;

    $jsonString = json_encode($footprints);
    file_put_contents('../processed/wsfp-' . $baseAddress . '-' . date('Y-m-d-H-i-s') . '.json', $jsonString);
}
echo 'Finished.' . PHP_EOL;

