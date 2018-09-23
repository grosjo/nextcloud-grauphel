<?php
require_once __DIR__ . '/../../../lib/converter/base.php';
require_once __DIR__ . '/../../../lib/converter/html.php';

class Lib_Converter_HtmlTest extends \PHPUnit\Framework\TestCase
{
    public function testConvert()
    {
        $input = file_get_contents(__DIR__ . '/../../data/formattest.tomboynotecontent');

        $converter = new OCA\Grauphel\Converter\Html();
        $output = $converter->convert($input);
        $this->assertEquals(
            file_get_contents(__DIR__ . '/../../data/formattest.html'),
            $output
        );
    }

    public function testXSS()
    {
        $input = file_get_contents(__DIR__ . '/../../data/xss.tomboynotecontent');

        $converter = new OCA\Grauphel\Converter\Html();
        $output = $converter->convert($input);
        $this->assertEquals(
            file_get_contents(__DIR__ . '/../../data/xss.html'),
            $output
        );
    }
}
?>
