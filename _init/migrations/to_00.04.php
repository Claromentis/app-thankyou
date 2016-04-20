<?php
$_db_migration_to = '00.04';
if (!isset($migrations) || !is_object($migrations))
	die("This file cannot be executed directly");
$migrations->CheckValid($_db_migration_to);
//===========================================================================================



$migrations->Run('01_multi_users.php', <<<'DB_UPDATE_FILE'
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

DB_UPDATE_FILE
);


//===========================================================================================
$migrations->SetVersion('00.04');
