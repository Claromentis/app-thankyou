<?php

namespace Claromentis\ThankYou\ThankYous;

//TODO: Replace with Core instances of similar once available.
use Claromentis\Core\Localization\Lmsg;
use Claromentis\People\Entity\User;

class DeletedUserFactory
{
	private $lmsg;

	public function __construct(Lmsg $lmsg)
	{
		$this->lmsg = $lmsg;
	}

	/**
	 * @param int|null $user_id
	 * @return User
	 */
	public function Create(?int $user_id): User
	{
		$user = new User();
		if (isset($user_id))
		{
			$user->id = $user_id;
		}
		$user->firstname = ($this->lmsg)('thankyou.user.deleted');

		return $user;
	}
}
