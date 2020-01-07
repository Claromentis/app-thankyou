<?php
/**
 * @var \Claromentis\Setup\SetupFacade $migrations
 */
$db = $migrations->GetDb();

$db->AddColumn('thankyou_tagged', 'aggregation_id', 'INT');

$db->CreateIndex('thankyou_tagged', 'idx_aggregation_id', 'aggregation_id');

$db->query("UPDATE thankyou_tagged SET aggregation_id = 143");
