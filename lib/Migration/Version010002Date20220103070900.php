<?php

namespace OCA\Grauphel\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version010002Date20220103070900 extends SimpleMigrationStep 
{
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper 
	{
		$schema = $schemaClosure();

		if (!$schema->hasTable('grauphel_oauth_tokens')) 
		{
			$table = $schema->createTable('grauphel_oauth_tokens');
			$table->addColumn('token_id', 'integer', [ 'autoincrement' => true, 'notnull' => true, 'length' => 20 ]);
			$table->addColumn('token_user', 'text', [ 'length' => 64, 'notnull' => false ]);
			$table->addColumn('token_type', 'text', [ 'length' => 16, 'notnull' => true ]);
			$table->addColumn('token_key', 'text', [ 'length' => 128, 'notnull' => true ]);
			$table->addColumn('token_secret', 'text', [ 'length' => 128, 'notnull' => true ]);
			$table->addColumn('token_verifier', 'text', [ 'length' => 128, 'notnull' => true ]);
			$table->addColumn('token_callback', 'text', [ 'length' => 2048, 'notnull' => true ]);
			$table->addColumn('token_client', 'text', [ 'length' => 256, 'notnull' => true ]);
			$table->addColumn('token_lastuse', 'datetime', [ 'notnull' => true ]);
			$table->setPrimaryKey(['token_id']);
		}

		if (!$schema->hasTable('grauphel_notes'))
		{
			$table = $schema->createTable('grauphel_notes');
			$table->addColumn('note_id', 'integer', [ 'autoincrement' => true, 'notnull' => true, 'length' => 20 ]);
			$table->addColumn('note_user', 'text', [ 'length' => 64, 'notnull' => false ]);
			$table->addColumn('note_guid', 'text', [ 'length' => 128, 'notnull' => true ]);
			$table->addColumn('note_last_sync_revision', 'integer', [ 'notnull' => true, 'length' => 20, 'default' => 0 ]);
			$table->addColumn('note_create_date', 'text', [ 'length' => 33, 'notnull' => true ]);
			$table->addColumn('note_last_change_date', 'text', [ 'length' => 33, 'notnull' => true ]);
			$table->addColumn('note_last_metadata_change_date', 'text', [ 'length' => 33, 'notnull' => true ]);
			$table->addColumn('note_title', 'text', [ 'length' => 1024, 'notnull' => true ]);
			$table->addColumn('note_content', 'text', [ 'notnull' => true ]);
			$table->addColumn('note_content_version', 'text', [ 'length' => 16, 'notnull' => true ]);
			$table->addColumn('note_open_on_startup', 'integer', [ 'length' => 1, 'notnull' => true, 'default' => 0 ]);
			$table->addColumn('note_pinned', 'integer', [ 'length' => 1, 'notnull' => true, 'default' => 0 ]);
			$table->addColumn('note_tags', 'text', [ 'length' => 1024, 'notnull' => true ]);
			$table->setPrimaryKey(['note_id']);
		}

		if (!$schema->hasTable('grauphel_syncdata'))
		{
			$table = $schema->createTable('grauphel_syncdata');
			$table->addColumn('syncdata_id', 'integer', [ 'autoincrement' => true, 'notnull' => true, 'length' => 20 ]);
			$table->addColumn('syncdata_user', 'text', [ 'length' => 64, 'notnull' => true ]);
			$table->addColumn('syncdata_current_sync_guid', 'text', [ 'length' => 64, 'notnull' => true ]);
			$table->addColumn('syncdata_latest_sync_revision', 'integer', [ 'length' => 20, 'notnull' => true, 'default' => 0 ]);
			$table->setPrimaryKey(['syncdata_id']);
		}
	
		return $schema;
	}
}
