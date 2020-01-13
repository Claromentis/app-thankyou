<?php

namespace Claromentis\ThankYou\ThankYous;

use Claromentis\Core\Localization\Lmsg;
use Claromentis\ThankYou\Configuration;
use Claromentis\ThankYou\Exception\ValidationException;
use Date;
use User;

class Validator
{
	/**
	 * @var Configuration\Api
	 */
	private $config_api;

	/**
	 * @var Lmsg
	 */
	private $lmsg;

	/**
	 * @var ThankYousRepository
	 */
	private $repository;

	public function __construct(Lmsg $lmsg, Configuration\Api $config_api, ThankYousRepository $repository)
	{
		$this->config_api = $config_api;
		$this->lmsg       = $lmsg;
		$this->repository = $repository;
	}

	/**
	 * Given a Thank You, determines whether it is in a valid state to save to the Repository.
	 *
	 * @param ThankYou $thank_you
	 * @throws ValidationException - If the Thank You has one or more issues making it unsuitable to save to the
	 * Repository.
	 */
	public function ValidateThankYou(ThankYou $thank_you): void
	{
		$errors = [];
		$id     = $thank_you->GetId();

		$original_thank_you = null;
		if (isset($id))
		{
			$thank_yous = $this->repository->GetThankYous([$id]);
			if (!isset($thank_yous[$id]))
			{
				$original_thank_you = null;
				$errors[]           = ['name' => 'id', 'reason' => ($this->lmsg)('thankyou.thankyou.id.error.not_found', $id)];
			} else
			{
				$original_thank_you = $thank_yous[$id];
			}
		}

		//Author
		$author = $thank_you->GetAuthor();
		//If it's new or the Author has changed
		if (!isset($original_thank_you) || $author->GetId() !== $original_thank_you->GetAuthor()->GetId())
		{
			$author_id = $author->GetId();
			if (!$this->CanUserBeAuthor($author))
			{
				$errors[] = ['name' => 'author', 'reason' => ($this->lmsg)('thankyou.thankyou.author.error.invalid')];
			} else
			{
				$users = $this->repository->GetUsers([$author_id]);
				if (!isset($users[$author_id]))
				{
					$errors[] = ['name' => 'author', 'reason' => ($this->lmsg)('thankyou.thankyou.author.error.not_found', $author_id)];
				}
			}
		}

		//Description
		$description = $thank_you->GetDescription();
		if ($description === '')
		{
			$errors[] = ['name' => 'description', 'reason' => ($this->lmsg)('thankyou.thankyou.description.error.empty')];
		}

		//Dates
		$date_created = $thank_you->GetDateCreated();
		if (Date::compare($date_created, new Date(), true) > 0)
		{
			$errors[] = ['name' => 'date_created', 'reason' => ($this->lmsg)('thankyou.thankyou.date_created.error.future')];
		}

		//Thanked
		$thankeds = $thank_you->GetThankables();
		if (isset($thankeds))
		{
			if (count($thankeds) === 0)
			{
				$errors[] = ['name' => 'thankeds', 'reason' => ($this->lmsg)('thankyou.thankyou.thanked.error.empty')];
			}

			foreach ($thankeds as $thanked)
			{
				$owner_class_id = $thanked->GetOwnerClass();
				$item_id        = $thanked->GetItemId();

				if (!isset($owner_class_id))
				{
					$errors[] = ['name' => 'thankeds', 'reason' => ($this->lmsg)('thankyou.thanked.owner_class.error.undefined')];
				}
				if (!isset($item_id))
				{
					$errors[] = ['name' => 'thankeds', 'reason' => ($this->lmsg)('thankyou.thanked.item_id.error.undefined')];
				}
			}
		} elseif (!isset($original_thank_you))
		{
			$errors[] = ['name' => 'thankeds', 'reason' => ($this->lmsg)('thankyou.thankyou.thanked.error.empty')];
		}

		//Users
		//TODO: check Thanking a Group with a deleted Users!
		$users = $thank_you->GetUsers();
		if (isset($users))
		{
			foreach ($users as $user)
			{
				if (!$this->CanUserBeThanked($user))
				{
					$errors[] = [
						'name'   => 'users',
						'reason' => ($this->lmsg)('thankyou.thankyou.users.user.id.error.invalid',
							(int) $user->GetId())
					];
				}
			}
		} elseif (!isset($original_thank_you))
		{
			$errors[] = ['name' => 'users', 'reason' => ($this->lmsg)('thankyou.thankyou.users.error.empty')];
		}

		//Tags
		$tags_mandatory = $this->config_api->IsTagsMandatory();
		$tags           = $thank_you->GetTags();
		if (isset($tags))
		{
			if ($tags_mandatory && empty($tags))
			{
				$errors[] = ['name' => 'tags', 'reason' => ($this->lmsg)('thankyou.thankyou.tags.error.empty')];
			} elseif (!$this->config_api->IsTagsEnabled())
			{
				$errors[] = ['name' => 'tags', 'reason' => ($this->lmsg)('thankyou.thankyou.tags.error.disabled')];
			}
		} elseif (!isset($original_thank_you) && $tags_mandatory)
		{
			$errors[] = ['name' => 'tags', 'reason' => ($this->lmsg)('thankyou.thankyou.tags.error.empty')];
		}

		if (!empty($errors))
		{
			throw new ValidationException($errors);
		}
	}

	/**
	 * Determines whether a User can be a Thank You's Author.
	 *
	 * @param User $user
	 * @return bool
	 */
	public function CanUserBeAuthor(User $user): bool
	{
		$id = $user->GetId();

		return $id !== 0 && is_int($id);
	}

	/**
	 * Determines whether a Thanked User can be thanked.
	 *
	 * @param User $user
	 * @return bool
	 */
	public function CanUserBeThanked(User $user): bool
	{
		$id = $user->GetId();

		return $id !== 0 && is_int($id);
	}
}