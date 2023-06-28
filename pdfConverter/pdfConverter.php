<?php
require_once(__DIR__ . '/../helper/WebHelper.php');
$webHelper = new WebHelper($argv);

$fileName =  $webHelper->opts['--source'] ?? readline("File to convert: ");
$prefix = $webHelper->opts['--output-prefix'] ?? readline("output prefix: ");
$pages = $webHelper->opts['--pages'] ?? readline('How may pages: ');
$pages = $pages - 1; // 0 index remember...
$resolution = $webHelper->opts['--resolution'] ?? readline("Target resolution: ");
for($i=0; $i < $pages; $i++)  {
    echo 'Converting page ' . $i . PHP_EOL;
    $imagick = new Imagick();
    $imagick->setResolution((int)$resolution, (int)$resolution);
    $imagick->readImage(__DIR__ . '/source/' . $fileName . '[' . $i . ']');
    $imagick = $imagick->flattenImages();
    $imagick->writeImage(__DIR__ . '/../processed/' . $prefix . $i . '.jpg');
}
echo 'Finished' . PHP_EOL;