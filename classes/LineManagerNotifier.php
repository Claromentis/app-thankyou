<?php
namespace Claromentis\ThankYou;

use AuthUser;
use Claromentis\ThankYou\ThankYous;
use Exception;
use NotificationMessage;
use Psr\Log\LoggerInterface;
use User;
use UsersHierarchy;

class LineManagerNotifier
{
	/**
	 * @var LoggerInterface
	 */
	private $logger;

	public function __construct(LoggerInterface $logger)
	{
		$this->logger = $logger;
	}

	/**
	 * @param string $description
	 * @param int[]  $user_ids
	 */
	public function SendMessage($description, $user_ids)
	{
		$params = [
			'author'      => AuthUser::I()->GetFullName(),
			'description' => $description
		];

		$line_managers = [];

		foreach ($user_ids as $user_id)
		{
			if ($manager = UsersHierarchy::GetManager($user_id))
			{
				$user = new User($user_id);
				$user->Load();

				$line_managers[$manager][] = $user->GetFullName();
			}
		}

		$author_id = AuthUser::I()->GetId();

		foreach ($line_managers as $line_manager => $recipients)
		{
			$params['first_recipient'] = $recipients[0];

			$num_recipients                 = count($recipients);
			$params['num_other_recipients'] = $num_recipients - 1;

			if ($num_recipients == 1)
			{
				$params['recipients'] = $recipients[0];
			} else
			{
				$all_except_last_recipient_csv = implode(', ', array_slice($recipients, 0, -1));
				$last_recipient                = array_pop($recipients);
				$params['recipients']          = $all_except_last_recipient_csv . ' ' . lmsg('thankyou.grammar.list.and') . ' ' . $last_recipient;
			}

			try
			{
				NotificationMessage::Send('thankyou.new_thanks_manager', $params, [$line_manager], ThankYous\Api::IM_TYPE_THANKYOU, null, $author_id);
			} catch (Exception $exception)
			{
				$this->logger->error("Unexpected Exception thrown when sending Thank You Line Manager Notifications", [$exception]);
			}
		}
	}
}
