<?php
/** @var $migrations \Claromentis\Setup\SetupFacade */
$db = $migrations->GetDb();

// thankyou_item
$table_descr = array(
	'thanks_id'	=>	"INT NOT_NULL",
	'user_id'	=>	"INT NOT_NULL",
);

$db->CreateTable('thankyou_user', $table_descr);

$db->query("INSERT INTO thankyou_user (thanks_id, user_id) SELECT id, user_id FROM thankyou_item");

$db->DropColumn('thankyou_item', 'user_id');
