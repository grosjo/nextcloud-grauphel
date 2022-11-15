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
    /**
     * @var \OCP\IDBConnection
     */
    protected $db;

    protected $urlGen;
    protected $username;

    public function __construct($urlGen)
    {
        $this->urlGen = $urlGen;
        $this->db     = \OC::$server->getDatabaseConnection();
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
        $result = $this->db->executeQuery(
            'SELECT `note_tags` FROM `*PREFIX*grauphel_notes`'
            . ' WHERE note_user = ?',
            array($this->username)
        );

        $tags = array();
        while ($row = $result->fetch()) {
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
            'x', 'y',
            'width', 'height',
            'selection-bound-position',
            'cursor-position',
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

        if (!isset($note->{'create-date'})) {
            //no idea how to get the microseconds in there
            $note->{'create-date'} = date('c');
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
        $row = $this->db->executeQuery(
            'SELECT * FROM `*PREFIX*grauphel_syncdata`'
            . ' WHERE `syncdata_user` = ?',
            array($this->username)
        )->fetch();

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
        $row = $this->db->executeQuery(
            'SELECT * FROM `*PREFIX*grauphel_syncdata`'
            . ' WHERE `syncdata_user` = ?',
            array($this->username)
        )->fetch();

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
        $this->db->executeQuery($sql, $params);
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
        $this->db->executeQuery(
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
        $row = $this->db->executeQuery(
            'SELECT * FROM `*PREFIX*grauphel_notes`'
            . ' WHERE `note_user` = ? AND `note_guid` = ?',
            array($this->username, $guid)
        )->fetch();

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

                'open-on-startup'               => false,
                'pinned'                        => false,
                'tags'                          => array(),

                'x'                             => 20,
                'y'                             => 20,
                'width'                         => -1,
                'height'                        => -1,

                'cursor-position'               => 0,
                'selection-bound-position'      => 0,
            );
        }

        return $this->noteFromRow($row);
    }

    /**
     * Load a GUID of a note by the note title.
     *
     * The note title is stored html-escaped in the database because we
     * get it that way from tomboy. Thus we have to escape the search
     * input, too.
     *
     * @param string $title Note title.
     *
     * @return string GUID, NULL if note could not be found
     */
    public function loadGuidByTitle($title)
    {
        $row = $this->db->executeQuery(
            'SELECT note_guid FROM `*PREFIX*grauphel_notes`'
            . ' WHERE `note_user` = ? AND `note_title` = ?',
            array($this->username, htmlspecialchars($title))
        )->fetch();

        if ($row === false) {
            return null;
        }

        return $row['note_guid'];
    }

    /**
     * Search for a note
     *
     * @param array $keywords arrays of query strings within keys AND and NOT
     *
     * @return array Database rows with note_guid and note_title
     */
    public function search($keywordGroups)
    {
        if (!isset($keywordGroups['AND'])) {
            $keywordGroups['AND'] = array();
        }
        if (!isset($keywordGroups['NOT'])) {
            $keywordGroups['NOT'] = array();
        }

        $sqlTplAnd = ' AND (`note_title` ILIKE ? OR `note_tags` ILIKE ? OR `note_content` ILIKE ?)';
        $sqlTplNot = ' AND NOT (`note_title` ILIKE ? OR `note_tags` ILIKE ? OR `note_content` ILIKE ?)';
        $arData = array(
            $this->username
        );
        foreach (array('AND', 'NOT') as $group) {
            $keywords = $keywordGroups[$group];
            foreach ($keywords as $keyword) {
                $arData[] = '%' . $keyword . '%';//title
                $arData[] = '%' . $keyword . '%';//tags
                $arData[] = '%' . $keyword . '%';//content
            }
        }

        $result = $this->db->executeQuery(
            'SELECT `note_guid`, `note_title`'
            . ' FROM `*PREFIX*grauphel_notes`'
            . ' WHERE note_user = ?'
            . str_repeat($sqlTplAnd, count($keywordGroups['AND']))
            . str_repeat($sqlTplNot, count($keywordGroups['NOT'])),
            $arData
        );

        $notes = array();
        while ($row = $result->fetch()) {
            $notes[] = $row;
        }
        return $notes;
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
        $row = $this->db->executeQuery(
            'SELECT * FROM `*PREFIX*grauphel_notes`'
            . ' WHERE `note_user` = ? AND `note_guid` = ?',
            array($this->username, $note->guid)
        )->fetch();

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
        $this->db->executeQuery($sql, $params);
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
        $this->db->executeQuery(
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
        $this->db->executeQuery(
            'DELETE FROM `*PREFIX*grauphel_notes`'
            . ' WHERE `note_user` = ?',
            array($this->username)
        );
    }

    /**
     * Load notes for the given user in short form.
     * Optionally only those changed after $since revision
     *
     * @param integer $since       Revision number after which the notes changed
     * @param string  $rawtag      Filter by tag. Special tags:
     *                             - grauphel:special:all
     *                             - grauphel:special:untagged
     * @param boolean $includeDate Load the last modification date or not
     *
     * @return array Array of short note objects
     */
    public function loadNotesOverview(
        $since = null, $rawtag = null, $includeDate = false
    ) {
        $sql = 'SELECT `note_guid`, `note_title`'
            . ', `note_last_sync_revision`, `note_tags`'
            . ', `note_last_change_date`'
            . ' FROM `*PREFIX*grauphel_notes`'
            . ' WHERE note_user = ?';
        $sqlData = array($this->username);

        if ($since !== null) {
            $sqlData[] = $since;
            $sql .= ' AND note_last_sync_revision > ?';
        }

        if ($rawtag == 'grauphel:special:all') {
            $rawtag = null;
        } else if ($rawtag == 'grauphel:special:untagged') {
            $jsRawtag = json_encode(array());
        } else {
            $jsRawtag = json_encode($rawtag);
        }
        if ($rawtag !== null) {
            $sqlData[] = '%' . $jsRawtag . '%';
            $sql .= ' AND note_tags LIKE ?';
        }

        $result = $this->db->executeQuery($sql, $sqlData);
        $notes = array();
        while ($row = $result->fetch()) {
            $note = array(
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
                    'href' => $this->urlGen->getAbsoluteURL(
                        $this->urlGen->linkToRoute(
                            'grauphel.gui.note',
                            array(
                                'guid' => $row['note_guid']
                            )
                        )
                    ),
                ),
                'title' => $row['note_title'],
            );
            if ($includeDate) {
                $note['last-change-date'] = $row['note_last_change_date'];
            }
            $notes[] = $note;
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
        $result = $this->db->executeQuery(
            'SELECT * FROM `*PREFIX*grauphel_notes`'
            . ' WHERE note_user = ?',
            array($this->username)
        );

        $notes = array();
        while ($row = $result->fetch()) {
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

            'title'                     => $row['note_title'],
            'note-content'              => $row['note_content'],
            'note-content-version'      => $row['note_content_version'],

            'open-on-startup'           => (bool) $row['note_open_on_startup'],
            'pinned'                    => (bool) $row['note_pinned'],
            'tags'                      => json_decode($row['note_tags']),
	    
            'x'                         => (int) $row['note_x'],
            'y'                         => (int) $row['note_y'],

            'height'                    => (int) $row['note_height'],
            'width'                     => (int) $row['note_width'],

            'selection-bound-position'  => (int) $row['note_selection_bound_position'],
            'cursor-position'           => (int) $row['note_cursor_position'], 

            'last-sync-revision'        => (int) $row['note_last_sync_revision'],
        );
    }

    protected function rowFromNote($note)
    {
        return array(
            'note_guid'                      => $note->guid,
            'note_title'                     => (string) $note->title,

            'note_content'                   => (string) $note->{'note-content'},
            'note_content_version'           => (string) $note->{'note-content-version'},

            'note_create_date'               => $note->{'create-date'},
            'note_last_change_date'          => $note->{'last-change-date'},
            'note_last_metadata_change_date' => $note->{'last-metadata-change-date'},

            'note_open_on_startup'           => (int) $note->{'open-on-startup'},
            'note_pinned'                    => (int) $note->pinned,
            'note_tags'                      => json_encode($note->tags),
            'note_x'                         => (int) $note->{'x'},
            'note_y'                         => (int) $note->{'y'},

            'note_height'                    => (int) $note->{'height'},
            'note_width'                     => (int) $note->{'width'},

            'note_selection_bound_position'  => (int) $note->{'selection-bound-position'},
            'note_cursor_position'           => (int) $note->{'cursor-position'},

            'note_last_sync_revision'        => $note->{'last-sync-revision'},
        );
    }
}
?>
