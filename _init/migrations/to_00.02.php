<?php
$_db_migration_to = '00.02';
if (!isset($migrations) || !is_object($migrations))
	die("This file cannot be executed directly");
$migrations->CheckValid($_db_migration_to);
//===========================================================================================



$migrations->Run('01_initial.php', <<<'DB_UPDATE_FILE'
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

DB_UPDATE_FILE
);


$migrations->Run('02_add_plugin.php', <<<'DB_UPDATE_FILE'
<?php
/** @var $migrations \Claromentis\Setup\SetupFacade */

$config_file = $migrations->GetConfigEditor();
$config_file->AddText('$cfg_cla_plugins[] = "\\\\Claromentis\\\\ThankYou\\\\Plugin";'."\r\n");


DB_UPDATE_FILE
);


//===========================================================================================
$migrations->SetVersion('00.02');
