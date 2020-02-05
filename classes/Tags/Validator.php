<?php

namespace Claromentis\ThankYou\Tags;

use Claromentis\Core\Localization\Lmsg;
use Claromentis\ThankYou\Tags\Exceptions\ValidationException;

class Validator
{
	const NAME_MAX_CHARACTERS = 20;

	private $lmsg;

	public function __construct(Lmsg $lmsg)
	{
		$this->lmsg = $lmsg;
	}

	/**
	 * Determines whether a Tag is in a suitable state to be save to the Repository.
	 *
	 * @param Tag $tag
	 * @throws ValidationException - If the Tag has one or more issues making it unsuitable to save to the Repository.
	 */
	public function Validate(Tag $tag)
	{
		$name = $tag->GetName();

		$errors = [];

		if ($this->IsNameTooLong($name))
		{
			$errors[] = ['name' => 'name', 'reason' => ($this->lmsg)('thankyou.tag.name.error.too_long')];
		}

		if (!empty($errors))
		{
			throw new ValidationException($errors);
		}
	}

	private function IsNameTooLong(string $name)
	{
		return mb_strlen($name) > self::NAME_MAX_CHARACTERS;
	}
}
