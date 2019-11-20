<?php

namespace Claromentis\ThankYou\ThankYous;

use Claromentis\Core\Acl\PermOClass;
use Claromentis\ThankYou\Exception\ThankYouOClass;
use DateTime;
use DateTimeZone;
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

	/**
	 * @param DateTime[]|int[] $date_range
	 * @return int[]
	 */
	public function FormatDateRange(array $date_range): array
	{
		if (isset($date_range[0]) && ($date_range[0] instanceof DateTime))
		{
			/**
			 * @var DateTime $from_date
			 */
			$from_date = clone $date_range[0];
			$from_date->setTimezone(new DateTimeZone('UTC'));

			$date_range[0] = (int) $from_date->format('YmdHis');
		}
		if (isset($date_range[1]) && ($date_range[1] instanceof DateTime))
		{
			/**
			 * @var DateTime $from_date
			 */
			$to_date = clone $date_range[1];
			$to_date->setTimezone(new DateTimeZone('UTC'));

			$date_range[1] = (int) $to_date->format('YmdHis');
		}

		return $date_range;
	}
}
