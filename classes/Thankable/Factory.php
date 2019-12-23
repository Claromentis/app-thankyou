<?php

namespace Claromentis\ThankYou\Thankable;

use Claromentis\Core\Acl\PermOClass;
use Claromentis\Core\Localization\Lmsg;
use Claromentis\ThankYou\Exception\ThankYouOClass;
use Claromentis\ThankYou\ThankYous\ThankYouUtility;

class Factory
{
	/**
	 * @var Lmsg $lmsg
	 */
	private $lmsg;

	/**
	 * @var ThankYouUtility $utility
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
	 * @param int|null    $id
	 * @param int|null    $owner_class_id
	 * @param int|null    $extranet_id
	 * @param string|null $image_url
	 * @param string|null $profile_url
	 * @return Thankable
	 */
	public function Create(string $name, ?int $id = null, ?int $owner_class_id = null, ?int $extranet_id = null, ?string $image_url = null, ?string $profile_url = null)
	{
		$owner_class_name = null;
		if (isset($owner_class_id))
		{
			$owner_class_name = $this->GetOwnerClassName($owner_class_id);
		}

		$thankable = new Thankable($name);
		$thankable->SetExtranetId($extranet_id);
		$thankable->SetId($id);
		$thankable->SetImageUrl($image_url);
		$thankable->SetOwnerClassId($owner_class_id);
		$thankable->SetOwnerClassName($owner_class_name);
		$thankable->SetProfileUrl($profile_url);

		return $thankable;
	}

	/**
	 * Creates and returns a Thankable to represent Thanked objects which could not be identified.
	 *
	 * @param int|null $id
	 * @param int|null $owner_class_id
	 * @return Thankable
	 */
	public function CreateUnknown(?int $id = null, ?int $owner_class_id = null)
	{
		$owner_class_name = null;
		if (isset($owner_class_id))
		{
			$owner_class_name = $this->GetOwnerClassName($owner_class_id);
		}

		if ($owner_class_id === PermOClass::INDIVIDUAL)
		{
			$name = ($this->lmsg)('thankyou.user.not_found');
		} elseif ($owner_class_id === PermOClass::GROUP)
		{
			$name = ($this->lmsg)('thankyou.group.not_found');
		} else
		{
			$name = ($this->lmsg)('thankyou.thankable.not_found');
		}

		$thankable = new Thankable($name);
		$thankable->SetId($id);
		$thankable->SetOwnerClassId($owner_class_id);
		$thankable->SetOwnerClassName($owner_class_name);

		return $thankable;
	}

	/**
	 * @param int $owner_class_id
	 * @return string
	 */
	private function GetOwnerClassName(int $owner_class_id): string
	{
		try
		{
			return $this->utility->GetOwnerClassNamesFromIds([$owner_class_id])[$owner_class_id];
		} catch (ThankYouOClass $exception)
		{
			return ($this->lmsg)('thankyou.owner_class.not_found');
		}
	}
}
