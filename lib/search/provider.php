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
        $rows = $notes->search($query);

        $results = array();
        foreach ($rows as $row) {
            $res = new Note();
            $res->id   = $row['note_guid'];
            $res->name = $row['note_title'];
            $res->link = $urlGen->linkToRoute(
                'grauphel.gui.note', array('guid' => $row['note_guid'])
            );
            $results[] = $res;
        }
        return $results;
    }
}
?>
