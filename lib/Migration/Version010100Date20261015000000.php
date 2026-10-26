<?php

namespace OCA\Grauphel\Migration;

use Closure;
use Doctrine\DBAL\Types\Type;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Turn short "text" columns into "string" columns so that they can be
 * indexed, and add indexes for the lookups done on every sync request.
 */
class Version010100Date20261015000000 extends SimpleMigrationStep
{
	/**
	 * Column name => array(length, notnull)
	 */
	protected const STRING_COLUMNS = array(
		'grauphel_oauth_tokens' => array(
			'token_user'     => array(64, false),
			'token_type'     => array(16, true),
			'token_key'      => array(128, true),
			'token_secret'   => array(128, true),
			//empty for all but verification tokens; Oracle stores '' as NULL
			'token_verifier' => array(128, false),
		),
		'grauphel_notes' => array(
			'note_user'                      => array(64, false),
			'note_guid'                      => array(128, true),
			'note_create_date'               => array(64, true),
			'note_last_change_date'          => array(64, true),
			'note_last_metadata_change_date' => array(64, true),
			'note_content_version'           => array(16, true),
		),
		'grauphel_syncdata' => array(
			'syncdata_user'              => array(64, true),
			//uniqid() with the user name as prefix
			'syncdata_current_sync_guid' => array(128, true),
		),
	);

	protected const INDEXES = array(
		'grauphel_oauth_tokens' => array(
			'grauphel_tok_key'  => array('token_key', 'token_type'),
			'grauphel_tok_user' => array('token_user', 'token_type'),
		),
		'grauphel_notes' => array(
			'grauphel_note_guid' => array('note_user', 'note_guid'),
		),
		'grauphel_syncdata' => array(
			'grauphel_sync_user' => array('syncdata_user'),
		),
	);

	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper
	{
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		foreach (self::STRING_COLUMNS as $tableName => $columns) {
			$table = $schema->getTable($tableName);
			foreach ($columns as $columnName => list($length, $notnull)) {
				$table->getColumn($columnName)
					->setType(Type::getType(Types::STRING))
					->setLength($length)
					->setNotnull($notnull);
			}
		}

		foreach (self::INDEXES as $tableName => $indexes) {
			$table = $schema->getTable($tableName);
			foreach ($indexes as $indexName => $columns) {
				if (!$table->hasIndex($indexName)) {
					$table->addIndex($columns, $indexName);
				}
			}
		}

		return $schema;
	}
}
