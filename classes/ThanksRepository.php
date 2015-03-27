<?php
namespace Claromentis\ThankYou;

use Claromentis\Core\DAL;

/**
 *
 * @author Alexander Polyanskikh
 */
class ThanksRepository
{
	/**
	 * @param int $limit
	 *
	 * @return ThanksItem[]
	 */
	public function GetRecent($limit)
	{
		$items = \ObjectsStorage::I()->GetMultiple(new ThanksItem(), '', 'date_created DESC', $limit);
		return $items;
	}

	/**
	 * @param int $user_id
	 * @param int $limit
	 *
	 * @return ThanksItem[]
	 */
	public function GetForUser($user_id, $limit)
	{
		$items = \ObjectsStorage::I()->GetMultiple(new ThanksItem(), new DAL\QueryPart("user_id=int:id", $user_id), 'date_created DESC', $limit);
		return $items;
	}
}