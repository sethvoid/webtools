<?php
require_once(__DIR__ . '/../helper/WebHelper.php');
$webHelper = new WebHelper($argv);
$webHelper->displayBanner(__DIR__);
$baselineAddress = $webHelper->opts['--url'] ?? readline("Enter Base url Address: ");
$primaryScrapeAddress = $webHelper->opts['--fullurl'] ?? readline("Enter primary search address (in full!): ");
$delay =  $webHelper->opts['--delay'] ?? readline("Enter delay between requessts (in seconds!) : ");

$html = file_get_contents($primaryScrapeAddress);

$dom = new DOMDocument();
$doc = $dom->loadHTML($html);

$anchors = $doc->getElementsByTagName('a');
echo 'Found ' . count($anchors) . '  in document. Searching for pictures...' . PHP_EOL;
$scrapable = [];
foreach ( $anchors as $a ) {
    if (stripos($a->getAttribute('href'), '.jpg') !== false
    || stripos($a->getAttribute('href'), '.jpeg') !== false
    || stripos($a->getAttribute('href'), '.png') !== false) {
        $scrapable[] = $a->getAttribute('href');
    }
}

echo 'Identified ' . count($scrapable) . ' images.' . PHP_EOL;
$continue = readline("Continue to scrape all " . count($scrapable) . ' images (y/n)? ');
if ($continue == 'y') {
    $folderName = date('d-m-Y');
    echo 'Creating folder' . PHP_EOL;
    mkdir(__DIR__ . '/../processed/' . $folderName);
    foreach($scrapable as $scrape) {
        $name = basename($scrape);
        if (stripos($scrape, $baselineAddress) == false) {
            $scrape = $baselineAddress . $scrape;
        }
        echo 'Hovering up  ' . $scrape . PHP_EOL;
        file_put_contents(__DIR__ . '/../processed/' . $folderName . '/' . $name, file_get_contents($scrape));
        sleep($delay);
    }
    echo 'Finished. Your images can be found here ' . __DIR__ . '/../processed/' . $folderName . '/' . PHP_EOL;
}