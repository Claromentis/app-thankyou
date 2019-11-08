<?php
/**
 * @var \Claromentis\Setup\SetupFacade $migrations
 */
$db = $migrations->GetDb();

$table_description = [
	'id'          => 'IDENTITY',
	'thankyou_id' => 'INT',
	'tag_id'      => 'INT'
];

$db->CreateTable('thankyou_tagged', $table_description);

$db->CreateIndex('thankyou_tagged', 'idx_thankyou_id', 'thankyou_id');
$db->CreateIndex('thankyou_tagged', 'idx_tag_id', 'tag_id');
