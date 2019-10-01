<?php

namespace Claromentis\ThankYou\ThankYous;

use Claromentis\Core\Acl\AclRepository;
use Claromentis\People\InvalidFieldIsNotSingle;
use Claromentis\People\UsersListProvider;
use Claromentis\ThankYou\Exception\ThankYouInvalidAuthor;
use Claromentis\ThankYou\Exception\ThankYouInvalidUsers;
use Claromentis\ThankYou\Exception\ThankYouRuntimeException;
use Date;
use LogicException;
use User;

class ThankYouFactory
{
	/**
	 * @param User|int $author
	 * @param string $description
	 * @param Date|null $date_created
	 * @param null $users
	 * @throws ThankYouRuntimeException
	 * @throws ThankYouInvalidAuthor
	 * @throws LogicException
	 * @throws ThankYouInvalidUsers
	 * @return ThankYou
	 */
	public function Create(User $author, Date $date_created, string $description)
	{
		return new ThankYou($author, $date_created, $description);
	}
}
