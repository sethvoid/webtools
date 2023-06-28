<?php
require_once(__DIR__ . '/../helper/WebHelper.php');
$webHelper = new WebHelper($argv);
$webHelper->displayBanner(__DIR__);
$url = null;
$baseAddress = $webHelper->opts['--url'] ?? readline('Please enter address (do not include protocal) :');

$url = 'https://' . $baseAddress . '/sitemap.xml';
$xml = file_get_contents($url);
$obj = simplexml_load_string($xml);

$amount = count($obj);
echo 'Identified ' . $amount . ' urls in site map' . PHP_EOL;

echo 'Running quick diagnostic to establish what bad looks like..' . PHP_EOL;
$badArray = [];
for ($i=0; $i < 4; $i++) {
    $badArray[] = $webHelper->scrape('https://' . $baseAddress . '/' . md5(rand(1, 1000)));
    sleep(1);
}

foreach ($badArray as $testNo => $badResult) {
    echo "Test$testNo: " . $badResult['code'] . '[' . $badResult['response_size'] . ']'. PHP_EOL;
}

$error = [];

$cap = 10;
$counter = 0;
$additional = [];
$urls = [];

foreach ($obj as $loc) {
    $urls[(string)$loc->loc] = (string)$loc->loc;
}

foreach ($urls as $url) {
    $scrape = $webHelper->scrape($url);
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
    $scrape = $webHelper->scrape($links);
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
$webHelper->saveReport(
    $baseAddress . '-' . date('d-m-Y'),
    $fileString,
    'txt',
);

echo 'Script finished.' . PHP_EOL;