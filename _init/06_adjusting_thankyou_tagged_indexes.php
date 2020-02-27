<?php
/**
 * @var \Claromentis\Setup\SetupFacade $migrations
 */
$db = $migrations->GetDb();

$db->DropIndex('thankyou_tagged', 'idx_item_id');

$db->CreateIndex('thankyou_tagged', 'idx_tagged_item', 'aggregation_id', 'item_id');
