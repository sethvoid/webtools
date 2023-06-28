<?php
require_once(__DIR__ . '/../helper/WebHelper.php');
$webHelper = new WebHelper($argv);
$webHelper->displayBanner(__DIR__);
$dev = isset($webHelper->opts['--dev']);
$webHelper->setDevMode($dev);
$compare = isset($webHelper->opts['--compare']);
$baseAddress = $webHelper->opts['--url'] ?? readline('Please enter address (do not include protocal) :');

if ($compare) {
    $comparisonFile = $webHelper->opts['--file'] ?? readline('Please enter comparison file: ');
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
    $scrape = $webHelper->scrape($url);
    echo 'Checking ' . $url . '(' . $scrape['response_size'] . ')'.PHP_EOL;
    if ($scrape['code'] == 200) {
        $hashStr = $webHelper->getHash($scrape['response']);
        echo 'Generating hash ' . $url .PHP_EOL;
        $footprints[$url] = $hashStr;

        $potentialLinks = explode('href="', $scrape['response']);
        foreach ($potentialLinks as $linkalue) {
            $linkalue = substr($linkalue, 0, strpos($linkalue, '"'));
            $ext = pathinfo($linkalue, PATHINFO_EXTENSION);
            if (stripos($linkalue, $baseAddress) !== false && !isset($urls[$linkalue]) && $webHelper->allowedTypes($ext)) {
                $scrape2 = $webHelper->scrape($linkalue);
                if ($scrape2['code'] == 200) {
                    echo 'Generating Linked hash ' . $linkalue .PHP_EOL;
                    $hashSt = $webHelper->getHash($scrape2['response']);
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

        $webHelper->saveReport(
            'change-report-'  . $baseAddress . '-' . date('d-m-Y'),
            $reportString,
            'txt'
        );
    } catch (Exception $ex) {
        echo $ex->getMessage();
    }
} else {
    echo 'saving content' . PHP_EOL;

    $jsonString = json_encode($footprints);
    $webHelper->saveReport(
        'wsfp-' . $baseAddress . '-' . date('Y-m-d-H-i-s'),
       $jsonString,
        'json'
    );
}
echo 'Finished.' . PHP_EOL;

