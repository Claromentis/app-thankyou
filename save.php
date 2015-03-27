<?php
require_once("../common/sessioncheck.php");
require_once("../common/core.php");
require_once("../common/connect.php");

if (gpc::IsSubmit() && ($user_id = (int)getvar('thank_you_user')) > 0)
{
	$item = new \Claromentis\ThankYou\ThanksItem();
	$item->LoadFromArray([
		'user_id' => $user_id,
		'author' => AuthUser::I()->GetId(),
		'description' => getvar('thank_you_description'),
		'date_created' => Date::getNowTimestamp(),
	]);

	$item->Save();
}

$ref = $_SERVER['HTTP_REFERER'];
if (!strlen($ref))
	$ref = '/intranet/main/';

httpRedirect($ref);