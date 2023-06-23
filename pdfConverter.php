<?php
$fileName = readline("File to convert: ");
$prefix = readline("output prefix: ");
$pages = readline('How may pages: ');
$pages = $pages - 1; // 0 index remember...
$resolution = readline("Target resolution: ");
for($i=0; $i < $pages; $i++)  {
    echo 'Converting page ' . $i . PHP_EOL;
    $imagick = new Imagick();
    $imagick->setResolution((int)$resolution, (int)$resolution);
    $imagick->readImage('pdf/' . $fileName . '[' . $i . ']');
    $imagick = $imagick->flattenImages();
    $imagick->writeImage('pdf/processed/' . $prefix . $i . '.jpg');
}
