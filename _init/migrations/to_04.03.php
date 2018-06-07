<?php
$_db_migration_to = '04.03'; // 3.2.1
if (!isset($migrations) || !is_object($migrations))
	die("This file cannot be executed directly");
$migrations->CheckValid($_db_migration_to);
//===========================================================================================



//===========================================================================================
$migrations->SetVersion('04.03');
