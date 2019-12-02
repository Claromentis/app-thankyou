<?php
namespace Claromentis\ThankYou;

use Claromentis\Core\DAL;
use Exception;

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
}
