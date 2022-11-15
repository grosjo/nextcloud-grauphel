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
		$table = $schema->getTable('grauphel_notes');
		$table->addColumn('note_x', 'integer', [ 'notnull' => true, 'length' => 11, 'default' => 0 ]);
		$table->addColumn('note_y', 'integer', [ 'notnull' => true, 'length' => 11, 'default' => 0 ]);
		$table->addColumn('note_height', 'integer', [ 'notnull' => true, 'length' => 11, 'default' => 0 ]);
		$table->addColumn('note_width', 'integer', [ 'notnull' => true, 'length' => 11, 'default' => 0 ]);
		$table->addColumn('note_selection_bound_position', 'integer', [ 'notnull' => true, 'length' => 11, 'default' => 0 ]);
		$table->addColumn('note_cursor_position', 'integer', [ 'notnull' => true, 'length' => 11, 'default' => 0 ]);
	
		return $schema;
	}
}
