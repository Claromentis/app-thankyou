<?php
namespace Claromentis\ThankYou;

use Claromentis\Core\DAL;
use Date;
use Exception;
use ObjectsStorage;

/**
 * A repository for thank you items.
 */
class ThanksRepository
{
	/**
	 * @var DAL\Db
	 */
	protected $db;

	/**
	 * Create a new thanks repository.
	 *
	 * @param DAL\Db $db
	 */
	public function __construct(DAL\Db $db)
	{
		$this->db = $db;
	}

	/**
	 * Gets a single thank you item by ID.
	 *
	 * Returns false if the item failed to load.
	 *
	 * @param int $id
	 * @return ThanksItem|bool
	 * @throws Exception
	 */
	public function GetById($id)
	{
		$item = new ThanksItem();
		$item->Load($id);

		if (!$item->id)
			return false;

		$this->PopulateUsers([$item]);

		return $item;
	}

	/**
	 * Gets the most recent thank you items.
	 *
	 * @param int $limit
	 *
	 * @return ThanksItem[]
	 * @throws Exception
	 */
	public function GetRecent($limit)
	{
		/** @var ThanksItem[] $items */
		$items = ObjectsStorage::I()->GetMultiple(new ThanksItem(), '', 'date_created DESC', $limit);
		$this->PopulateUsers($items);
		return $items;
	}

	/**
	 * Gets thank you items between a given date range.
	 *
	 * If an end date is not provided, the current date is used in its place.
	 *
	 * @param Date $start_date
	 * @param Date $end_date [optional]
	 * @return ThanksItem[]
	 * @throws Exception
	 */
	public function GetByDate(Date $start_date, Date $end_date = null)
	{
		$end_date = $end_date ?: new Date();

		$filter = new DAL\QueryPart('date_created >= int:start_date AND date_created <= int:end_date', $start_date->getDate(), $end_date->getDate());

		/**
		 * @var ThanksItem[] $items
		 */
		$items = ObjectsStorage::I()->GetMultiple(new ThanksItem(), $filter, 'date_created DESC');

		$this->PopulateUsers($items);

		return $items;
	}

	/**
	 * Loads thanked users into the given thanks items.
	 *
	 * @param ThanksItem[] $items
	 * @throws Exception
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
	 * Get thanks for a given user.
	 *
	 * @param int $user_id
	 * @param int $limit
	 *
	 * @return ThanksItem[]
	 * @throws Exception
	 */
	public function GetForUser($user_id, $limit)
	{
		/** @var ThanksItem[] $items */
		$items = ObjectsStorage::I()->GetMultipleExt(new ThanksItem(), function (DAL\QueryBuilder $qb, $table_name) use ($user_id, $limit) {
			$qb->AddJoin($table_name, 'thankyou_user', 'tu', "tu.thanks_id={$table_name}.id");
			$qb->AddWhereAndClause(new DAL\QueryPart("tu.user_id=int:id", $user_id));
			$qb->SetLimit($limit);
		}, 'date_created DESC');

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

	/**
	 * Delete the given thank you note and its user associations.
	 *
	 * @param ThanksItem $item
	 */
	public function Delete(ThanksItem $item)
	{
		$id = $item->id;

		if (!$id)
			return;

		$item->Delete();

		$this->db->query('DELETE FROM thankyou_user WHERE thanks_id = int:id', $id);
	}
}