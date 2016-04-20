<?php
namespace Claromentis\ThankYou;

use Claromentis\Core\DAL;
use Claromentis\Core\Services;

/**
 *
 * @author Alexander Polyanskikh
 */
class ThanksRepository
{
	protected $db;

	public function __construct()
	{
		$this->db = Services::I()->GetDb();
	}

	/**
	 * @param int $limit
	 *
	 * @return ThanksItem[]
	 */
	public function GetRecent($limit)
	{
		$items = \ObjectsStorage::I()->GetMultiple(new ThanksItem(), '', 'date_created DESC', $limit);
		$this->PopulateUsers($items);
		return $items;
	}

	/**
	 * Loads thanked users into all thanks items
	 *
	 * @param ThanksItem[] $items
	 */
	protected function PopulateUsers($items)
	{
		if (!is_array($items) || empty($items))
			return;
		$ids = array_map(function ($item) { return $item->GetProperty('id'); }, $items);

		$res = $this->db->query("SELECT thanks_id, user_id FROM thankyou_user WHERE thanks_id IN in:int:ids", $ids);
		$users = [];
		while ($arr = $res->fetchArray())
		{
			if (empty($users[$arr['thanks_id']]))
				$users[$arr['thanks_id']] = [];
			$users[$arr['thanks_id']][] = $arr['user_id'];
		}

		foreach ($items as $item)
		{
			if (isset($users[$item->GetProperty('id')]))
				$item->SetUsers($users[$item->GetProperty('id')]);
			else
				$item->SetUsers([]); // this is actually an error state as at least one user should be thanked
		}
	}

	/**
	 * @param int $user_id
	 * @param int $limit
	 *
	 * @return ThanksItem[]
	 */
	public function GetForUser($user_id, $limit)
	{
		$items = \ObjectsStorage::I()->GetMultipleExt(new ThanksItem(), function (DAL\QueryBuilder $qb, $table_name) use ($user_id, $limit) {
			$qb->AddJoin($table_name, 'thankyou_user', 'tu', "tu.thanks_id={$table_name}.id");
			$qb->AddWhereAndClause(new DAL\QueryPart("tu.user_id=int:id", $user_id));
			$qb->SetLimit($limit);
		}, 'date_created DESC');

		//foreach ($items as $item)
		//{
		//	/** @var ThanksItem $item */
		//	$item->SetUsers([$user_id]);
		//}
		$this->PopulateUsers($items);
		return $items;
	}

	/**
	 * Returns number of "thanks" for user
	 *
	 * @param int $user_id
	 *
	 * @return int
	 */
	public function GetCount($user_id)
	{
		list($count) = $this->db->query_row("SELECT COUNT(1) FROM thankyou_user WHERE user_id=int:uid", $user_id);

		return $count;
	}
}