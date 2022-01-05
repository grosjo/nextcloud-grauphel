<?php
/**
 * Part of grauphel
 *
 * PHP version 5
 *
 * @category  Tools
 * @package   Grauphel
 * @author    Christian Weiske <cweiske@cweiske.de>
 * @copyright 2014 Christian Weiske
 * @license   http://www.gnu.org/licenses/agpl.html GNU AGPL v3
 * @link      http://cweiske.de/grauphel.htm
 */
namespace OCA\Grauphel\Search;

/**
 * User search query parser
 *
 * @category  Tools
 * @package   Grauphel
 * @author    Christian Weiske <cweiske@cweiske.de>
 * @copyright 2014 Christian Weiske
 * @license   http://www.gnu.org/licenses/agpl.html GNU AGPL v3
 * @version   Release: @package_version@
 * @link      http://cweiske.de/grauphel.htm
 */
class QueryParser
{
    /**
     * Splits the user's query string up into several keywords
     * that all have to be within or not appear in the note (AND, NOT).
     *
     * Split by space, quotes are supported:
     * - foo bar
     *   -> searches for notes that contain "foo" and "bar"
     * - foo "bar baz"
     *   -> searches for notes that contain "foo" and "bar baz"
     *
     * Exclusion is supported:
     * - foo -bar
     *   -> search for notes that contain "foo" but not "bar"
     * - foo -"bar baz"
     *   -> search for notes that contain "foo" but not "bar baz"
     *
     * @param string $query User-given query string
     *
     * @return array Array of keyword arrays, grouped by "AND" and "NOT"
     */
    public function parse($query)
    {
        $keywords = array();
        $query    = trim($query);

        $groupMap = array(
            '+' => 'AND',
            '-' => 'NOT',
        );

        $chQuote    = null;
        $curKeyword = '';
        $group      = 'AND';
        foreach (str_split($query) as $char) {
            if ($char == '"' || $char == '\'') {
                if ($chQuote === null) {
                    //new quote
                    $chQuote = $char;
                    continue;
                } else if ($char == $chQuote) {
                    //quote end
                    if (strlen($curKeyword)) {
                        $keywords[$group][] = $curKeyword;
                        $curKeyword = '';
                    }
                    $chQuote = null;
                    continue;
                }
            } else if ($char == ' ' && $chQuote === null) {
                if (strlen($curKeyword)) {
                    $keywords[$group][] = $curKeyword;
                    $curKeyword = '';
                    $group = 'AND';
                }
                continue;
            } else if ($char == '+' || $char == '-' && $curKeyword == '') {
                $group = $groupMap[$char];
                continue;
            }

            $curKeyword .= $char;
        }
        if (strlen($curKeyword)) {
            $keywords[$group][] = $curKeyword;
        }
        return $keywords;
    }

}
?>
