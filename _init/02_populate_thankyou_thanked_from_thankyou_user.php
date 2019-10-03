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
