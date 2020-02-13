<?php

namespace Claromentis\ThankYou\ThankYous;

use Claromentis\Core\Acl\PermOClass;
use Claromentis\ThankYou\Exception\OwnerClassNameException;
use DateTime;
use DateTimeZone;
use InvalidArgumentException;

class ThankYouUtility
{
	/**
	 * Given the an Owner Class' ID, returns it's Name.
	 *
	 * @param int $id
	 * @return string
	 * @throws OwnerClassNameException - If the Name of the oClass could not be determined.
	 */
	public function GetOwnerClassName(int $id): string
	{
		return $this->GetOwnerClassNames([$id])[$id];
	}

	/**
	 * Returns an array of Owner Class Names indexed by their IDs.
	 *
	 * @param int[] $ids
	 * @return string[]
	 * @throws OwnerClassNameException - If the Name of the oClass could not be determined.
	 */
	public function GetOwnerClassNames(array $ids): array
	{
		$names = [];
		foreach ($ids as $id)
		{
			if (!is_int($id))
			{
				throw new InvalidArgumentException("Failed to Get Thanked Object Type's Name From ID, non-integer value given");
			}
			$names[$id] = PermOClass::GetName($id);
			if (!is_string($names[$id]))
			{
				throw new OwnerClassNameException("Failed to Get Thanked Object Type's Name From ID, oClass did not return string");
			}
		}

		return $names;
	}

	/**
	 * Create a Thank You's URL (not including the site's name)
	 *
	 * @param int $id
	 * @return string
	 */
	public function GetThankYouUrl(int $id)
	{
		return '/thankyou/thanks/' . $id;
	}
}
