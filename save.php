<?php
// TODO: Move to REST API
require_once("../common/sessioncheck.php");
require_once("../common/core.php");
require_once("../common/connect.php");

if (gpc::IsSubmit())
{
	$ref = $_SERVER['HTTP_REFERER'];

	$id = getvar('thank_you_id');
	$user_id = getvar('thank_you_user');
	$description = getvar('thank_you_description');

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

		if ($id > 0)
		{
			// Edit an existing thank you item
			$item->Load($id);

			if (!$item->id)
				httpRedirect($ref, 'Thank you item does not exist', true);

			if ((int) $item->author !== (int) AuthUser::I()->GetId())
				httpRedirect($ref, 'You do not have permission to edit this thank you note', true);

			$item->SetDescription($description);
		} else
		{
			// Create a new thank you item
			$item->LoadFromArray([
				'author' => AuthUser::I()->GetId(),
				'description' => $description,
				'date_created' => Date::getNowTimestamp()
			]);
		}

		$item->SetUsers($users_ids);
		$item->Save();

		$params = array(
			'author' => AuthUser::I()->GetFullName(),
			'other_people_number' => count($users_ids) - 1,
			'description' => $description,
		);

		NotificationMessage::AddApplicationPrefix('thankyou', 'thankyou');
		NotificationMessage::Send('thankyou.new_thanks', $params, $users_ids, IMessage::TYPE_PEOPLE);
	}
}

if (!strlen($ref))
	$ref = '/intranet/main/';

httpRedirect($ref);