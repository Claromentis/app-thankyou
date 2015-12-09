<?php
// This file contains the database schema version 00.03
if (!isset($migrations) || !is_object($migrations))
	die("This file cannot be executed directly");
if ($migrations->GetVersion() > 0)
	throw new Exception("The database is already initialized");
//===========================================================================================

// thankyou_item
$table_descr = array(
	'id'	=>	"IDENTITY",
	'user_id'	=>	"INT NOT_NULL DEFAULT 0",
	'author'	=>	"INT NOT_NULL DEFAULT 0",
	'date_created'	=>	"INT_DATE NULL",
	'description'	=>	"CLOB NULL",
);

$db->CreateTable('thankyou_item', $table_descr, true);





//===========================================================================================
$migrations->SetVersion('00.03');
