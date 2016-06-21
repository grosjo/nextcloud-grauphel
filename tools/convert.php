#!/usr/bin/env php
<?php
require 'lib/converter/base.php';
require 'lib/converter/restructuredtext.php';

if ($argc != 2) {
    echo "Tomboy note file name missing\n";
    exit(1);
}

$file = $argv[1];
$conv = new \OCA\Grauphel\Converter\ReStructuredText();
echo $conv->convert(file_get_contents($file));
?>
