<?php
$_db_migration_to = '04.01'; // 3.2.0-rc
if (!isset($migrations) || !is_object($migrations))
	die("This file cannot be executed directly");
$migrations->CheckValid($_db_migration_to);
//===========================================================================================



$migrations->Run('01_enable_admin_panel.php', <<<'DB_UPDATE_FILE'
<?php
/**
 * @var \Claromentis\Setup\SetupFacade $migrations
 */
$panels = $migrations->GetAdminPanelCreator();
$panels->Enable('thankyou');

DB_UPDATE_FILE
);


//===========================================================================================
$migrations->SetVersion('04.01');
