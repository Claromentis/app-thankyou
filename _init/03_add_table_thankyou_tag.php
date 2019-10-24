<?php
/**
 * @var \Claromentis\Setup\SetupFacade $migrations
 */
$db = $migrations->GetDb();

$table_description = [
	'id'            => 'IDENTITY',
	'active'        => 'BOOL NOT_NULL DEFAULT 1',
	'name'          => 'VARCHAR(100)',
	'created_by'    => 'INT',
	'created_date'  => 'INT_DATE',
	'modified_by'   => 'INT',
	'modified_date' => 'INT_DATE',
	'metadata'      => 'CLOB NULL'
];

$db->CreateTable('thankyou_tag', $table_description);

$db->CreateIndex('thankyou_tag', 'idx_active', 'active');
$db->CreateIndex('thankyou_tag', 'idx_created_by', 'created_by');
$db->CreateIndex('thankyou_tag', 'idx_modified_by', 'modified_by');
