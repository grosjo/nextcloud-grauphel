<?php
require_once __DIR__ . '/../../../lib/search/queryparser.php';

use OCA\Grauphel\Search\Queryparser;

class Lib_Search_QueryParserTest extends PHPUnit_Framework_TestCase
{
    public function testParseSimple()
    {
        $qp = new QueryParser();
        $this->assertEquals(
            array('AND' => array('foo')),
            $qp->parse('foo')
        );

        $this->assertEquals(
            array('AND' => array('foo', 'bar')),
            $qp->parse('foo bar')
        );
    }

    public function testParseQuotes()
    {
        $qp = new QueryParser();
        $this->assertEquals(
            array('AND' => array('foo bar')),
            $qp->parse('"foo bar"')
        );

        $this->assertEquals(
            array('AND' => array('foo bar', 'baz')),
            $qp->parse('"foo bar" baz')
        );

        $this->assertEquals(
            array('AND' => array('foo \'bar\' baz', 'bat')),
            $qp->parse('"foo \'bar\' baz" bat')
        );

        $this->assertEquals(
            array('AND' => array('foo bar baz')),
            $qp->parse('"foo bar baz"')
        );

        $this->assertEquals(
            array('AND' => array('one two three', 'four', 'five six', 'seven')),
            $qp->parse('"one two three" four "five six" seven')
        );
    }

    public function testParseWhitespace()
    {
        $qp = new QueryParser();
        $this->assertEquals(
            array('AND' => array('foo')),
            $qp->parse(' foo ')
        );

        $this->assertEquals(
            array('AND' => array('foo', 'bar')),
            $qp->parse(' foo    bar ')
        );

        $this->assertEquals(
            array('AND' => array('foo ', '  bar')),
            $qp->parse(' "foo " "  bar" ')
        );
    }

    public function testParseNot()
    {
        $qp = new QueryParser();
        $this->assertEquals(
            array('AND' => array('foo')),
            $qp->parse('+foo')
        );

        $this->assertEquals(
            array('AND' => array('foo'), 'NOT' => array('bar')),
            $qp->parse('+foo -bar')
        );

        $this->assertEquals(
            array(
                'AND' => array('foo', 'bat'),
                'NOT' => array('bar baz')
            ),
            $qp->parse('+foo -"bar baz" +bat')
        );
    }
}
?>
