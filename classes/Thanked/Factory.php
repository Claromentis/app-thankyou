<?php

namespace Claromentis\ThankYou\Thanked;

use Claromentis\Core\Acl\PermOClass;
use Claromentis\Core\Localization\Lmsg;
use Claromentis\ThankYou\Exception\OwnerClassNameException;
use Claromentis\ThankYou\ThankYous\ThankYouUtility;

class Factory
{
	/**
	 * @var Lmsg
	 */
	private $lmsg;

	/**
	 * @var ThankYouUtility
	 */
	private $utility;

	/**
	 * Factory constructor.
	 *
	 * @param Lmsg            $lmsg
	 * @param ThankYouUtility $utility
	 */
	public function __construct(Lmsg $lmsg, ThankYouUtility $utility)
	{
		$this->lmsg    = $lmsg;
		$this->utility = $utility;
	}

	/**
	 * @param string      $name
	 * @param int|null    $item_id
	 * @param int|null    $owner_class_id
	 * @param int|null    $extranet_id
	 * @param string|null $image_url
	 * @param string|null $profile_url
	 * @return Thanked
	 */
	public function Create(
		string $name,
		?int $item_id = null,
		?int $owner_class_id = null,
		?int $extranet_id = null,
		?string $image_url = null,
		?string $profile_url = null
	) {
		$owner_class_name = null;
		if (isset($owner_class_id))
		{
			$owner_class_name = $this->GetOwnerClassName($owner_class_id);
		}

		$thanked = new Thanked($name);
		$thanked->SetExtranetId($extranet_id);
		$thanked->SetItemId($item_id);
		$thanked->SetImageUrl($image_url);
		$thanked->SetOwnerClassId($owner_class_id);
		$thanked->SetOwnerClassName($owner_class_name);
		$thanked->SetObjectUrl($profile_url);

		return $thanked;
	}

	/**
	 * Creates and returns a Thanked to represent Thanked objects which could not be identified.
	 *
	 * @param int|null $owner_class_id
	 * @return Thanked
	 */
	public function CreateUnknown(?int $owner_class_id = null)
	{
		$owner_class_name = null;
		if (isset($owner_class_id))
		{
			$owner_class_name = $this->GetOwnerClassName($owner_class_id);
		}

		if ($owner_class_id === PermOClass::INDIVIDUAL)
		{
			$name = ($this->lmsg)('thankyou.thanked.user.deleted');
		} elseif ($owner_class_id === PermOClass::GROUP)
		{
			$name = ($this->lmsg)('thankyou.thanked.group.deleted');
		} else
		{
			$name = ($this->lmsg)('thankyou.thanked.deleted');
		}

		$thanked = new Thanked($name);
		$thanked->SetOwnerClassId($owner_class_id);
		$thanked->SetOwnerClassName($owner_class_name);

		return $thanked;
	}

	/**
	 * @param int $owner_class_id
	 * @return string
	 */
	private function GetOwnerClassName(int $owner_class_id): string
	{
		try
		{
			return $this->utility->GetOwnerClassName($owner_class_id);
		} catch (OwnerClassNameException $exception)
		{
			return ($this->lmsg)('thankyou.owner_class.not_found');
		}
	}
}
