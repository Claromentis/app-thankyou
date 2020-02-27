<?php
// This file contains the database schema version 06.01
if (!isset($migrations) || !is_object($migrations))
	die("This file cannot be executed directly");
if ($migrations->GetVersion() > 0)
	throw new Exception("The database is already initialized");
//===========================================================================================

// thankyou_item
$table_descr = array(
	'id'	=>	"IDENTITY",
	'author'	=>	"INT NOT_NULL DEFAULT 0",
	'date_created'	=>	"INT_DATE NULL",
	'description'	=>	"CLOB NULL",
);

$db->CreateTable('thankyou_item', $table_descr, true);



// thankyou_tag
$table_descr = array(
	'id'	=>	"IDENTITY",
	'active'	=>	"BOOL NOT_NULL DEFAULT 1",
	'name'	=>	"VARCHAR(100) NULL",
	'created_by'	=>	"INT NULL",
	'created_date'	=>	"INT_DATE NULL",
	'modified_by'	=>	"INT NULL",
	'modified_date'	=>	"INT_DATE NULL",
	'metadata'	=>	"CLOB NULL",
);

$db->CreateTable('thankyou_tag', $table_descr, true);
$db->CreateIndex('thankyou_tag', 'idx_active', 'active');
$db->CreateIndex('thankyou_tag', 'idx_created_by', 'created_by');
$db->CreateIndex('thankyou_tag', 'idx_modified_by', 'modified_by');



// thankyou_tagged
$table_descr = array(
	'id'	=>	"IDENTITY",
	'item_id'	=>	"INT NULL",
	'tag_id'	=>	"INT NULL",
	'aggregation_id'	=>	"INT NULL",
);

$db->CreateTable('thankyou_tagged', $table_descr, true);
$db->CreateIndex('thankyou_tagged', 'idx_tag_id', 'tag_id');
$db->CreateIndex('thankyou_tagged', 'idx_aggregation_id', 'aggregation_id');
$db->CreateIndex('thankyou_tagged', 'idx_tagged_item', 'aggregation_id', 'item_id');



// thankyou_thanked
$table_descr = array(
	'id'	=>	"IDENTITY",
	'item_id'	=>	"INT NULL",
	'object_type'	=>	"INT NULL",
	'object_id'	=>	"INT NULL",
);

$db->CreateTable('thankyou_thanked', $table_descr, true);
$db->CreateIndex('thankyou_thanked', 'idx_item_id', 'item_id');
$db->CreateIndex('thankyou_thanked', 'idx_object_type', 'object_type', 'object_id');



// thankyou_user
$table_descr = array(
	'thanks_id'	=>	"INT NOT_NULL",
	'user_id'	=>	"INT NOT_NULL",
);

$db->CreateTable('thankyou_user', $table_descr, true);





//===========================================================================================
$migrations->SetVersion('06.01');
