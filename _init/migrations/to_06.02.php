<?php
$_db_migration_to = '06.02'; // 4.0.1
if (!isset($migrations) || !is_object($migrations))
	die("This file cannot be executed directly");
$migrations->CheckValid($_db_migration_to);
//===========================================================================================



//===========================================================================================
$migrations->SetVersion('06.02');
