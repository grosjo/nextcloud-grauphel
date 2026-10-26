#!/usr/bin/env php
<?php
require __DIR__ . '/../lib/Converter/Base.php';
require __DIR__ . '/../lib/Converter/ReStructuredText.php';

if ($argc != 2) {
    echo "Tomboy note file name missing\n";
    exit(1);
}

$file = $argv[1];
$conv = new \OCA\Grauphel\Converter\ReStructuredText();
echo $conv->convert(file_get_contents($file));
?>
