<?php

namespace Claromentis\ThankYou\ThankYous;

use Claromentis\Core\Acl\PermOClass;
use Claromentis\ThankYou\Exception\ThankYouOClass;
use InvalidArgumentException;

class ThankYouUtility
{
	/**
	 * @param int[] $ids
	 * @return string[]
	 * @throws ThankYouOClass - If the Name of the oClass could not be determined.
	 */
	public function GetOwnerClassNamesFromIds(array $ids): array
	{
		$names = [];
		foreach ($ids as $offset => $id)
		{
			if (!is_int($id))
			{
				throw new InvalidArgumentException("Failed to Get Thankable Object Type's Name From ID, non-integer value given");
			}
			$names[$offset] = PermOClass::GetName($id);
			if (!is_string($names[$offset]))
			{
				throw new ThankYouOClass("Failed to Get Thankable Object Type's Name From ID, oClass did not return string");
			}
		}

		return $names;
	}
}
