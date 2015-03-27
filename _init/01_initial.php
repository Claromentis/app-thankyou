<?php
/** @var $migrations \Claromentis\Setup\SetupFacade */
$db = $migrations->GetDb();

$db->CreateTable('thankyou_item', array(
	'id' => "IDENTITY",
	'user_id' => 'INT NOT_NULL DEFAULT 0',
	'author' => 'INT NOT_NULL DEFAULT 0',
	'date_created' => "INT_DATE",
	'description' => 'CLOB',
), true);
