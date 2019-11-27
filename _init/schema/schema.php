<?php
// This file contains the database schema version 05.03
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



// thankyou_user
$table_descr = array(
	'thanks_id'	=>	"INT NOT_NULL",
	'user_id'	=>	"INT NOT_NULL",
);

$db->CreateTable('thankyou_user', $table_descr, true);





//===========================================================================================
$migrations->SetVersion('05.03');
