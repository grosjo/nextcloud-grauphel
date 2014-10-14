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
namespace OCA\Grauphel\Lib;

/**
 * Flat file storage for notes
 *
 * @category  Tools
 * @package   Grauphel
 * @author    Christian Weiske <cweiske@cweiske.de>
 * @copyright 2014 Christian Weiske
 * @license   http://www.gnu.org/licenses/agpl.html GNU AGPL v3
 * @version   Release: @package_version@
 * @link      http://cweiske.de/grauphel.htm
 */
class NoteStorage
{
    protected $urlGen;
    protected $username;

    public function __construct($urlGen)
    {
        $this->urlGen   = $urlGen;
    }

    public function setUsername($username)
    {
        $this->username = $username;
    }

    /**
     * Create a new sync data object for fresh users.
     * Used by loadSyncData()
     *
     * @return SyncData New synchronization statistics
     */
    protected function getNewSyncData()
    {
        $syncdata = new SyncData();
        $syncdata->initNew($this->username);
        return $syncdata;
    }

    public function getTags()
    {
        $result = \OC_DB::executeAudited(
            'SELECT `note_tags` FROM `*PREFIX*grauphel_notes`'
            . ' WHERE note_user = ?',
            array($this->username)
        );

        $tags = array();
        while ($row = $result->fetchRow()) {
            $tags = array_merge($tags, json_decode($row['note_tags']));
        }
        return array_unique($tags);
    }

    /**
     * Updates the given $note object with data from $noteUpdate.
     * Sets the last-sync-revision to $syncRevision
     *
     * @param object  $note         Original note object
     * @param object  $noteUpdate   Update note object from a PUT to the API
     * @param integer $syncRevision Current sync revision number
     *
     * @return void
     */
    public function update($note, $noteUpdate, $syncRevision)
    {
        static $updateFields = array(
            'create-date',
            'last-change-date',
            'last-metadata-change-date',
            'note-content',
            'note-content-version',
            'open-on-startup',
            'pinned',
            'tags',
            'title',
        );

        $changed = array();
        foreach ($updateFields as $field) {
            $changed[$field] = false;
            if (isset($noteUpdate->$field)) {
                if ($note->$field != $noteUpdate->$field) {
                    $note->$field = $noteUpdate->$field;
                    $changed[$field] = true;
                }
            }
        }

        if (!isset($noteUpdate->{'last-change-date'})
            && ($changed['title'] || $changed['note-content'])
        ) {
            //no idea how to get the microseconds in there
            $note->{'last-change-date'} = date('c');
        }

        if (!isset($noteUpdate->{'last-metadata-change-date'})) {
            //no idea how to get the microseconds in there
            $note->{'last-metadata-change-date'} = date('c');
        }

        if (isset($noteUpdate->{'node-content'})
            && $note->{'note-content-version'} == 0
        ) {
            $note->{'note-content-version'} = 0.3;
        }

        $note->{'last-sync-revision'} = $syncRevision;
    }

    /**
     * Loads synchronization data for the given user.
     * Creates fresh sync data if there are none for the user.
     *
     * @return SyncData Synchronization statistics (revision, sync guid)
     */
    public function loadSyncData()
    {
        $row = \OC_DB::executeAudited(
            'SELECT * FROM `*PREFIX*grauphel_syncdata`'
            . ' WHERE `syncdata_user` = ?',
            array($this->username)
        )->fetchRow();

        if ($row === false) {
            $syncdata = $this->getNewSyncData();
            $this->saveSyncData($syncdata);
        } else {
            $syncdata = new SyncData();
            $syncdata->latestSyncRevision = (int) $row['syncdata_latest_sync_revision'];
            $syncdata->currentSyncGuid    = $row['syncdata_current_sync_guid'];
        }

        return $syncdata;
    }

    /**
     * Save synchronization data for the given user.
     *
     * @param SyncData $syncdata Synchronization data object
     *
     * @return void
     */
    public function saveSyncData(SyncData $syncdata)
    {
        $row = \OC_DB::executeAudited(
            'SELECT * FROM `*PREFIX*grauphel_syncdata`'
            . ' WHERE `syncdata_user` = ?',
            array($this->username)
        )->fetchRow();

        if ($row === false) {
            //INSERT
            $sql = 'INSERT INTO `*PREFIX*grauphel_syncdata`'
                . '(`syncdata_user`, `syncdata_latest_sync_revision`, `syncdata_current_sync_guid`)'
                . ' VALUES(?, ?, ?)';
            $params = array(
                $this->username,
                $syncdata->latestSyncRevision,
                $syncdata->currentSyncGuid
            );
        } else {
            //UPDATE
            $data = array(
                'syncdata_latest_sync_revision' => $syncdata->latestSyncRevision,
                'syncdata_current_sync_guid'    => $syncdata->currentSyncGuid,
            );
            $sql = 'UPDATE `*PREFIX*grauphel_syncdata` SET'
                . ' `' . implode('` = ?, `', array_keys($data)) . '` = ?'
                . ' WHERE `syncdata_user` = ?';
            $params = array_values($data);
            $params[] = $this->username;
        }
        \OC_DB::executeAudited($sql, $params);
    }

    /**
     * Delete synchronization data for the given user.
     *
     * @param SyncData $syncdata Synchronization data object
     *
     * @return void
     */
    public function deleteSyncData()
    {
        \OC_DB::executeAudited(
            'DELETE FROM `*PREFIX*grauphel_syncdata`'
            . ' WHERE `syncdata_user` = ?',
            array($this->username)
        );
    }

    /**
     * Load a note from the storage.
     *
     * @param string  $guid      Note identifier
     * @param boolean $createNew Create a new note if it does not exist
     *
     * @return object Note object, NULL if !$createNew and note does not exist
     */
    public function load($guid, $createNew = true)
    {
        $row = \OC_DB::executeAudited(
            'SELECT * FROM `*PREFIX*grauphel_notes`'
            . ' WHERE `note_user` = ? AND `note_guid` = ?',
            array($this->username, $guid)
        )->fetchRow();

        if ($row === false) {
            if (!$createNew) {
                return null;
            }
            return (object) array(
                'guid' => $guid,

                'create-date'               => null,
                'last-change-date'          => null,
                'last-metadata-change-date' => null,

                'title'                => null,
                'note-content'         => null,
                'note-content-version' => 0.3,

                'open-on-startup' => false,
                'pinned'          => false,
                'tags'            => array(),
            );
        }

        return $this->noteFromRow($row);
    }

    /**
     * Save a note into storage.
     *
     * @param object $note Note to save
     *
     * @return void
     */
    public function save($note)
    {
        $row = \OC_DB::executeAudited(
            'SELECT * FROM `*PREFIX*grauphel_notes`'
            . ' WHERE `note_user` = ? AND `note_guid` = ?',
            array($this->username, $note->guid)
        )->fetchRow();

        $data = $this->rowFromNote($note);
        if ($row === false) {
            //INSERT
            $data['note_user'] = $this->username;
            $sql = 'INSERT INTO `*PREFIX*grauphel_notes`'
                . ' (`' . implode('`, `', array_keys($data)) . '`)'
                . ' VALUES(' . implode(', ', array_fill(0, count($data), '?')) . ')';
            $params = array_values($data);
        } else {
            //UPDATE
            $sql = 'UPDATE `*PREFIX*grauphel_notes` SET '
                . '`' . implode('` = ?, `', array_keys($data)) . '` = ?'
                . ' WHERE `note_user` = ? AND `note_guid` = ?';
            $params = array_values($data);
            $params[] = $this->username;
            $params[] = $note->guid;
        }
        \OC_DB::executeAudited($sql, $params);
    }

    /**
     * Delete a note from storage.
     *
     * @param object $guid ID of the note
     *
     * @return void
     */
    public function delete($guid)
    {
        \OC_DB::executeAudited(
            'DELETE FROM `*PREFIX*grauphel_notes`'
            . ' WHERE `note_user` = ? AND `note_guid` = ?',
            array($this->username, $guid)
        );
    }

    /**
     * Delete all notes from storage.
     *
     * @return void
     */
    public function deleteAll()
    {
        \OC_DB::executeAudited(
            'DELETE FROM `*PREFIX*grauphel_notes`'
            . ' WHERE `note_user` = ?',
            array($this->username)
        );
    }

    /**
     * Load notes for the given user in short form.
     * Optionally only those changed after $since revision
     *
     * @param integer $since  Revision number after which the notes changed
     * @param string  $rawtag Filter by tag. Special tags:
     *                        - grauphel:special:all
     *                        - grauphel:special:untagged
     *
     * @return array Array of short note objects
     */
    public function loadNotesOverview($since = null, $rawtag = null)
    {
        $result = \OC_DB::executeAudited(
            'SELECT `note_guid`, `note_title`, `note_last_sync_revision`, `note_tags`'
            . ' FROM `*PREFIX*grauphel_notes`'
            . ' WHERE note_user = ?',
            array($this->username)
        );

        $notes = array();
        if ($rawtag == 'grauphel:special:all') {
            $rawtag = null;
        } else if ($rawtag == 'grauphel:special:untagged') {
            $jsRawtag = json_encode(array());
        } else {
            $jsRawtag = json_encode($rawtag);
        }
        while ($row = $result->fetchRow()) {
            if ($since !== null && $row['note_last_sync_revision'] <= $since) {
                continue;
            }
            if ($rawtag !== null && strpos($row['note_tags'], $jsRawtag) === false) {
                continue;
            }
            $notes[] = array(
                'guid' => $row['note_guid'],
                'ref'  => array(
                    'api-ref' => $this->urlGen->getAbsoluteURL(
                        $this->urlGen->linkToRoute(
                            'grauphel.api.note',
                            array(
                                'username' => $this->username,
                                'guid' => $row['note_guid']
                            )
                        )
                    ),
                    'href' => null,//FIXME
                ),
                'title' => $row['note_title'],
            );
        }

        return $notes;
    }

    /**
     * Load notes for the given user in full form.
     * Optionally only those changed after $since revision
     *
     * @param integer $since Revision number after which the notes changed
     *
     * @return array Array of full note objects
     */
    public function loadNotesFull($since = null)
    {
        $result = \OC_DB::executeAudited(
            'SELECT * FROM `*PREFIX*grauphel_notes`'
            . ' WHERE note_user = ?',
            array($this->username)
        );

        $notes = array();
        while ($row = $result->fetchRow()) {
            if ($since !== null && $row['note_last_sync_revision'] <= $since) {
                continue;
            }
            $notes[] = $this->noteFromRow($row);
        }

        return $notes;
    }

    protected function fixDate($date)
    {
        if (strlen($date) == 32) {
            //Bug in grauphel 0.1.1; date fields in DB had only 32 instead of 33
            // characters. The last digit of the time zone was missing
            $date .= '0';
        }
        return $date;
    }

    protected function noteFromRow($row)
    {
        return (object) array(
            'guid'  => $row['note_guid'],

            'create-date'               => $this->fixDate($row['note_create_date']),
            'last-change-date'          => $this->fixDate($row['note_last_change_date']),
            'last-metadata-change-date' => $this->fixDate($row['note_last_metadata_change_date']),

            'title'                => $row['note_title'],
            'note-content'         => $row['note_content'],
            'note-content-version' => $row['note_content_version'],

            'open-on-startup' => (bool) $row['note_open_on_startup'],
            'pinned'          => (bool) $row['note_pinned'],
            'tags'            => json_decode($row['note_tags']),

            'last-sync-revision' => (int) $row['note_last_sync_revision'],
        );
    }

    protected function rowFromNote($note)
    {
        return array(
            'note_guid'  => $note->guid,
            'note_title' => (string) $note->title,

            'note_content'         => (string) $note->{'note-content'},
            'note_content_version' => (string) $note->{'note-content-version'},

            'note_create_date'               => $note->{'create-date'},
            'note_last_change_date'          => $note->{'last-change-date'},
            'note_last_metadata_change_date' => $note->{'last-metadata-change-date'},

            'note_open_on_startup' => (int) $note->{'open-on-startup'},
            'note_pinned'          => (int) $note->pinned,
            'note_tags'            => json_encode($note->tags),

            'note_last_sync_revision' => $note->{'last-sync-revision'},
        );
    }
}
?>