<?php
require_once("../common/sessioncheck.php");
require_once("../common/core.php");
require_once("../common/connect.php");

if (gpc::IsSubmit())
{
	$user_id = getvar('thank_you_user');

	if (is_scalar($user_id) && (int)$user_id > 0)
	{
		$users_ids = array((int)$user_id);
	} elseif (is_array($user_id))
	{
		$users_ids = intval_r($user_id);
	}

	if (!empty($users_ids) && is_array($users_ids))
	{
		$item = new \Claromentis\ThankYou\ThanksItem();
		$item->LoadFromArray([
                 'author' => AuthUser::I()->GetId(),
                 'description' => getvar('thank_you_description'),
                 'date_created' => Date::getNowTimestamp(),
                 'group_id' => $group_id,
             ]);
		$item->SetUsers($users_ids);
		$item->Save();
	}
}

$ref = $_SERVER['HTTP_REFERER'];
if (!strlen($ref))
	$ref = '/intranet/main/';

httpRedirect($ref);