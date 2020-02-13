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
	 * @param DateTime[] $date_range
	 * @return string[]
	 */
	public function FormatDateRange(array $date_range): array
	{
		if (!isset($date_range[0]) || !($date_range[0] instanceof DateTime))
		{
			throw new InvalidArgumentException("Failed to Format Date Range, Argument 1 offset 0 must be a DateTime");
		}
		if (!isset($date_range[1]) || !($date_range[1] instanceof DateTime))
		{
			throw new InvalidArgumentException("Failed to Format Date Range, Argument 1 offset 1 must be a DateTime");
		}

		/**
		 * @var DateTime $from_date
		 */
		$from_date = clone $date_range[0];
		$from_date->setTimezone(new DateTimeZone('UTC'));

		$date_range[0] = $from_date->format('YmdHis');

		/**
		 * @var DateTime $from_date
		 */
		$to_date = clone $date_range[1];
		$to_date->setTimezone(new DateTimeZone('UTC'));

		$date_range[1] = $to_date->format('YmdHis');

		return $date_range;
	}

	/**
	 * @param array $orders
	 * @return string
	 */
	public function BuildOrderString(array $orders): string
	{
		if (count($orders) === 0)
		{
			return '';
		}

		$query_string = " ORDER BY";
		foreach ($orders as $offset => $order)
		{
			$column    = $order['column'] ?? null;
			$direction = (isset($order['desc']) && $order['desc'] === true) ? 'DESC' : 'ASC';
			if (!isset($column) || !is_string($column))
			{
				throw new InvalidArgumentException("Failed to GetTags, one or more Orders does not have a column");
			}
			$query_string .= " " . $column . " " . $direction;
		}

		return $query_string;
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
