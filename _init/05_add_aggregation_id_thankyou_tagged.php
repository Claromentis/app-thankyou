<?php
/**
 * @var \Claromentis\Setup\SetupFacade $migrations
 */
$db = $migrations->GetDb();

$table_description = [
	'id'          => 'IDENTITY',
	'item_id' => 'INT',
	'tag_id'      => 'INT'
];

$db->AddColumn('thankyou_tagged', 'aggregation_id', 'INT');

$db->CreateIndex('thankyou_tagged', 'idx_aggregation_id', 'aggregation_id');

$db->query("UPDATE thankyou_tagged SET aggregation_id = 143");
