<?php

namespace Claromentis\ThankYou\Tags;

use Claromentis\Core\Localization\Lmsg;
use Claromentis\ThankYou\Tags\Exceptions\ValidationException;

class Validator
{
	const NAME_MAX_CHARACTERS = 25;

	/**
	 * @var Lmsg
	 */
	private $lmsg;

	/**
	 * @var TagRepository
	 */
	private $repository;

	public function __construct(TagRepository $repository, Lmsg $lmsg)
	{
		$this->repository = $repository;
		$this->lmsg       = $lmsg;
	}

	/**
	 * Determines whether a Tag is in a suitable state to be saved to the Repository.
	 *
	 * @param Tag $tag
	 * @throws ValidationException - If the Tag has one or more issues making it unsuitable to save to the Repository.
	 */
	public function Validate(Tag $tag)
	{
		$name = $tag->GetName();

		$errors = [];

		if (!$this->IsNameUnique($tag))
		{
			$errors[] = ['name' => 'name', 'reason' => ($this->lmsg)('thankyou.tag.error.name.not_unique')];
		}

		if ($this->IsNameTooLong($name))
		{
			$errors[] = ['name' => 'name', 'reason' => ($this->lmsg)('thankyou.tag.name.error.too_long')];
		}

		if (!empty($errors))
		{
			throw new ValidationException($errors);
		}
	}

	/**
	 * Determines if a Tag's Name is too long.
	 *
	 * @param string $name
	 * @return bool
	 */
	private function IsNameTooLong(string $name)
	{
		return mb_strlen($name) > self::NAME_MAX_CHARACTERS;
	}

	/**
	 * Determines whether a Tag's Name is unique, excluding itself.
	 *
	 * @param Tag $tag
	 * @return bool
	 */
	private function IsNameUnique(Tag $tag)
	{
		return $this->repository->IsNameUnique($tag->GetName(), $tag->GetId());
	}
}

