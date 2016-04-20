<?php
$_db_migration_to = '01.02';
if (!isset($migrations) || !is_object($migrations))
	die("This file cannot be executed directly");
$migrations->CheckValid($_db_migration_to);
//===========================================================================================



$migrations->Run('01_multi_users-605bf91.php', <<<'DB_UPDATE_FILE'
<?php
/** @var $migrations \Claromentis\Setup\SetupFacade */
$db = $migrations->GetDb();

$is_old_db = $db->GetColumnDescription('thankyou_item', 'user_id');

if ($is_old_db != '')
{
	// thankyou_item
	$table_descr = array(
		'thanks_id' => "INT NOT_NULL",
		'user_id' => "INT NOT_NULL",
	);

	$db->CreateTable('thankyou_user', $table_descr);

	$db->query("INSERT INTO thankyou_user (thanks_id, user_id) SELECT id, user_id FROM thankyou_item");

	$db->DropColumn('thankyou_item', 'user_id');
}

DB_UPDATE_FILE
);


$migrations->Run('02_reinstall_plugin.php', <<<'DB_UPDATE_FILE'
<?php
/** @var $migrations \Claromentis\Setup\SetupFacade */

// re-add plugin to make sure it's installed when Claromentis is upgraded to 8
$plugins = $migrations->GetPluginsRepository();
$plugins->Add('thankyou', 'Claromentis\ThankYou\Plugin');

DB_UPDATE_FILE
);


//===========================================================================================
$migrations->SetVersion('01.02');
