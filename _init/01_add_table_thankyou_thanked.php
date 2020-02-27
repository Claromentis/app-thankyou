<?php
/**
 * @var \Claromentis\Setup\SetupFacade $migrations
 */
$db = $migrations->GetDb();

$table_description = [
	'id' => 'IDENTITY',
	'item_id' => 'INT',
	'object_type' => 'INT',
	'object_id' => 'INT'
];

$db->CreateTable('thankyou_thanked', $table_description);

$db->CreateIndex('thankyou_thanked', 'idx_item_id', 'item_id');
$db->CreateIndex('thankyou_thanked', 'idx_object_type', 'object_type', 'object_id');
