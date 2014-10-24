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

use \OCA\Grauphel\Lib\NoteStorage;

/**
 * Hook for the site-wide owncloud search.
 *
 * @category  Tools
 * @package   Grauphel
 * @author    Christian Weiske <cweiske@cweiske.de>
 * @copyright 2014 Christian Weiske
 * @license   http://www.gnu.org/licenses/agpl.html GNU AGPL v3
 * @version   Release: @package_version@
 * @link      http://cweiske.de/grauphel.htm
 */
class Provider extends \OCP\Search\Provider
{
	/**
	 * Search for notes
	 *
	 * @param string $query
     *
	 * @return array list of \OCA\Grauphel\Search\Note
	 */
	public function search($query)
    {
        $urlGen = \OC::$server->getURLGenerator();
        $notes  = new NoteStorage($urlGen);
        $notes->setUsername(\OC_User::getUser());
        $rows = $notes->search($this->parseQuery($query));

        $results = array();
        foreach ($rows as $row) {
            $res = new Note();
            $res->id   = $row['note_guid'];
            $res->name = htmlspecialchars_decode($row['note_title']);
            $res->link = $urlGen->linkToRoute(
                'grauphel.gui.note', array('guid' => $row['note_guid'])
            );
            $results[] = $res;
        }
        return $results;
    }

    /**
     * Splits the user's query string up into several keywords
     * that all have to be within the note (AND).
     *
     * Split by space, quotes are supported:
     * - foo bar
     *   -> searches for notes that contain "foo" and "bar"
     * - foo "bar baz"
     *   -> searches for notes that contain "foo" and "bar baz"
     *
     * @param string $query User-given query string
     *
     * @return array Array of keywords
     */
    protected function parseQuery($query)
    {
        $keywords = explode(' ', $query);
        array_map('trim', $keywords);
        $loop = 0;
        do {
            $changed = false;
            foreach ($keywords as $key => &$keyword) {
                if ($keyword{0} != '"') {
                    continue;
                }
                if (substr($keyword, -1) == '"') {
                    // "foo"
                    $keyword = trim($keyword, '"');
                    continue;
                }
                if ($key < count($keywords) -1) {
                    //not at the end
                    $keyword .= ' ' . $keywords[$key + 1];
                    unset($keywords[$key + 1]);
                    $changed = true;
                    break;
                }
            }
        } while ($changed && ++$loop < 20);

        return $keywords;
    }
}
?>
