<?php
namespace Claromentis\ThankYou;

use ErrorHandler;
use IMessage;
use NotificationMessage;
use User;

class LineManagerNotifier extends ErrorHandler
{
	public function SendMessage($params, $user_ids)
	{
		$thanker_id = \AuthUser::I()->GetId();

		foreach ($user_ids as $user_id) {
			$user = new User($user_id);
			$user->Load();

			$params['recipient_name'] = $user->GetFullName();

			$line_manager = \UsersHierarchy::GetManager($user_id);

			NotificationMessage::Send('thankyou.new_thanks_manager', $params, [$line_manager], Constants::IM_TYPE_THANKYOU, null, $thanker_id);
		}
	}
}