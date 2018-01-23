<?php
// TODO: Move to REST API
require_once("../common/sessioncheck.php");
require_once("../common/core.php");
require_once("../common/connect.php");

if (gpc::IsSubmit())
{
	$ref = $_SERVER['HTTP_REFERER'];

	if (!strlen($ref))
		$ref = '/intranet/main/';

	$id = getvar('thank_you_id');
	$user_id = getvar('thank_you_user');
	$description = getvar('thank_you_description');

	$repository = new \Claromentis\ThankYou\ThanksRepository($db);

	if (getvar('delete'))
	{
		$item = new \Claromentis\ThankYou\ThanksItem();
		$item->Load($id);

		if (!$item->id)
			httpRedirect($ref, lmsg('thankyou.error.thanks_not_found'), true);

		if ((int) $item->author !== (int) AuthUser::I()->GetId())
			httpRedirect($ref, lmsg('thankyou.error.no_edit_permission'), true);

		$repository->Delete($item);

		httpRedirect($ref, lmsg('thankyou.common.thanks_deleted'));
	}

	if (is_scalar($user_id) && (int) $user_id > 0)
	{
		$users_ids = array((int) $user_id);
	} elseif (is_array($user_id))
	{
		$users_ids = intval_r($user_id);
	}

	if (!empty($users_ids) && is_array($users_ids))
	{
		$item = new \Claromentis\ThankYou\ThanksItem();

		$is_new = $id <= 0;

		if ($is_new)
		{
			// Create a new thank you item
			$item->LoadFromArray([
				'author' => AuthUser::I()->GetId(),
				'description' => $description,
				'date_created' => Date::getNowTimestamp()
			]);
		} else
		{
			// Edit an existing thank you item
			$item->Load($id);

			if (!$item->id)
				httpRedirect($ref, lmsg('thankyou.error.thanks_not_found'), true);

			if ((int) $item->author !== (int) AuthUser::I()->GetId())
				httpRedirect($ref, lmsg('thankyou.error.no_edit_permission'), true);

			$item->SetDescription($description);
		}

		$item->SetUsers($users_ids);
		$item->Save();

		// Send a notification if this is a new thank you item
		if ($is_new)
		{
			$params = array(
				'author' => AuthUser::I()->GetFullName(),
				'other_people_number' => count($users_ids) - 1,
				'description' => $description,
			);

			NotificationMessage::AddApplicationPrefix('thankyou', 'thankyou');
			NotificationMessage::Send('thankyou.new_thanks', $params, $users_ids, IMessage::TYPE_PEOPLE);
		}
	}
}

httpRedirect($ref);
