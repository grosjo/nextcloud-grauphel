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
namespace OCA\Grauphel\Storage;

/**
 * Synchronization data model
 *
 * @category  Tools
 * @package   Grauphel
 * @author    Christian Weiske <cweiske@cweiske.de>
 * @copyright 2014 Christian Weiske
 * @license   http://www.gnu.org/licenses/agpl.html GNU AGPL v3
 * @version   Release: @package_version@
 * @link      http://cweiske.de/grauphel.htm
 */
class SyncData
{
    /**
     * The latest sync revision from Tomboy, given from last PUT
     * of a note from Tomboy.
     * Give a -1 here if you have not synced with Tomboy yet.,
     *
     * @var integer
     */
    public $latestSyncRevision;
           
    /**
     * A uuid generated by the sync application.
     * It should change only if the user decides to clear their
     * sync history from the server and start over
     * with an empty note set.
     *
     * @var string
     */
    public $currentSyncGuid;

    /**
     * Initialize the variables to represent the data of a user
     * that never synced
     *
     * @param string $username Name of user
     *
     * @return void
     */
    public function initNew($username)
    {
        $this->latestSyncRevision = -1;
        $this->currentSyncGuid    = uniqid($username . '-', true);
    }
}
?>