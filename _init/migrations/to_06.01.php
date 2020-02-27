<?php
$_db_migration_to = '06.01'; // 4.0.0-rc
if (!isset($migrations) || !is_object($migrations))
	die("This file cannot be executed directly");
$migrations->CheckValid($_db_migration_to);
//===========================================================================================



$migrations->Run('01_add_table_thankyou_thanked.php', <<<'DB_UPDATE_FILE'
<?php
/**
 * @var \Claromentis\Setup\SetupFacade $migrations
 */
$db = $migrations->GetDb();

$table_description = [
	'id' => 'IDENTITY',
	'item_id' => 'INT',
	'object_type' => 'INT',
	'object_id' => 'INT'
];

$db->CreateTable('thankyou_thanked', $table_description);

$db->CreateIndex('thankyou_thanked', 'idx_item_id', 'item_id');
$db->CreateIndex('thankyou_thanked', 'idx_object_type', 'object_type', 'object_id');

DB_UPDATE_FILE
);


$migrations->Run('02_populate_thankyou_thanked_from_thankyou_user.php', <<<'DB_UPDATE_FILE'
<?php
/**
 * @var \Claromentis\Setup\SetupFacade $migrations
 */
$db = $migrations->GetDb();

$const_PERM_OCLASS_INDIVIDUAL = 1;

$query = $db->query("SELECT thankyou_item.id, thankyou_user.user_id FROM thankyou_item LEFT JOIN thankyou_user ON thankyou_user.thanks_id = thankyou_item.id LEFT JOIN thankyou_thanked ON thankyou_thanked.item_id = thankyou_item.id WHERE thankyou_item.id NOT IN(SELECT DISTINCT thankyou_thanked.item_id FROM thankyou_thanked)");

while ($thanked_users = $query->fetchArray())
{
	$db->query("INSERT INTO thankyou_thanked (item_id, object_type, object_id) VALUES (int:tyid, int:otid, int:oid)", (int) $thanked_users['id'], $const_PERM_OCLASS_INDIVIDUAL, $thanked_users['user_id']);
}

DB_UPDATE_FILE
);


$migrations->Run('03_add_table_thankyou_tag.php', <<<'DB_UPDATE_FILE'
<?php
/**
 * @var \Claromentis\Setup\SetupFacade $migrations
 */
$db = $migrations->GetDb();

$table_description = [
	'id'            => 'IDENTITY',
	'active'        => 'BOOL NOT_NULL DEFAULT 1',
	'name'          => 'VARCHAR(100)',
	'created_by'    => 'INT',
	'created_date'  => 'INT_DATE',
	'modified_by'   => 'INT',
	'modified_date' => 'INT_DATE',
	'metadata'      => 'CLOB NULL'
];

$db->CreateTable('thankyou_tag', $table_description);

$db->CreateIndex('thankyou_tag', 'idx_active', 'active');
$db->CreateIndex('thankyou_tag', 'idx_created_by', 'created_by');
$db->CreateIndex('thankyou_tag', 'idx_modified_by', 'modified_by');

DB_UPDATE_FILE
);


$migrations->Run('04_add_table_thankyou_tagged.php', <<<'DB_UPDATE_FILE'
<?php
/**
 * @var \Claromentis\Setup\SetupFacade $migrations
 */
$db = $migrations->GetDb();

$table_description = [
	'id'          => 'IDENTITY',
	'item_id' => 'INT',
	'tag_id'      => 'INT'
];

$db->CreateTable('thankyou_tagged', $table_description);

$db->CreateIndex('thankyou_tagged', 'idx_item_id', 'item_id');
$db->CreateIndex('thankyou_tagged', 'idx_tag_id', 'tag_id');

DB_UPDATE_FILE
);


$migrations->Run('05_add_aggregation_id_thankyou_tagged.php', <<<'DB_UPDATE_FILE'
<?php
/**
 * @var \Claromentis\Setup\SetupFacade $migrations
 */
$db = $migrations->GetDb();

$db->AddColumn('thankyou_tagged', 'aggregation_id', 'INT');

$db->CreateIndex('thankyou_tagged', 'idx_aggregation_id', 'aggregation_id');

$db->query("UPDATE thankyou_tagged SET aggregation_id = 143");

DB_UPDATE_FILE
);


$migrations->Run('06_adjusting_thankyou_tagged_indexes.php', <<<'DB_UPDATE_FILE'
<?php
/**
 * @var \Claromentis\Setup\SetupFacade $migrations
 */
$db = $migrations->GetDb();

$db->DropIndex('thankyou_tagged', 'idx_item_id');

$db->CreateIndex('thankyou_tagged', 'idx_tagged_item', 'aggregation_id', 'item_id');

DB_UPDATE_FILE
);


//===========================================================================================
$migrations->SetVersion('06.01');
