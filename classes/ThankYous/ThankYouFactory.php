<?php

namespace Claromentis\ThankYou\ThankYous;

use Claromentis\Core\Localization\Lmsg;
use Claromentis\Core\ORM\Exceptions\EntityNotFoundException;
use Claromentis\People\Entity\User;
use Claromentis\People\Repository\UserRepository;
use Date;
use InvalidArgumentException;

class ThankYouFactory
{
	/**
	 * @var Lmsg
	 */
	private $lmsg;

	/**
	 * @var UserRepository
	 */
	private $user_repository;

	/**
	 * ThankYouFactory constructor.
	 *
	 * @param Lmsg           $lmsg
	 * @param UserRepository $user_repository
	 */
	public function __construct(Lmsg $lmsg, UserRepository $user_repository)
	{
		$this->lmsg            = $lmsg;
		$this->user_repository = $user_repository;
	}

	/**
	 * Creates a Thank You.
	 * If the Author is provided as an ID, and the ID cannot be found in the User Repository, a User will be created
	 *
	 * @param User|int  $author
	 * @param string    $description
	 * @param Date|null $date_created
	 * @return ThankYou
	 */
	public function Create($author, string $description, ?Date $date_created)
	{
		if (is_int($author))
		{
			/**
			 * @var User $author
			 */
			try
			{
				$author = $this->user_repository->findOrFail($author);
			} catch (EntityNotFoundException $exception)
			{
				$author            = $this->user_repository->newInstance();
				$author->firstname = '';
				$author->surname   = ($this->lmsg)('orgchart.common.deleted_user');
			}
		} elseif (!($author instanceof User))
		{
			throw new InvalidArgumentException("Failed to Create Thank You, invalid Author");
		}

		if (!isset($date_created))
		{
			$date_created = new Date();
		}

		return new ThankYou($author, $description, $date_created);
	}
}
