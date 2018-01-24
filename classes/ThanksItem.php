<?php
namespace Claromentis\ThankYou;

use Claromentis\Core\Services;
use ObjectsStorage;

/**
 * A thank you item.
 *
 * @property-read int    $id
 * @property-read int    $author
 * @property-read int    $date_created
 * @property-read string $description
 */
class ThanksItem extends \ActiveRecord
{
	const AGGREGATION = 143;

	protected $users_ids = null;

	public function InitDbMapping(ObjectsStorage $storage)
	{
		$storage->MapDbTable($this, 'thankyou_item', 'id');

		$storage->MapDbColumn($this, 'id', ObjectsStorage::T_INT);
		$storage->MapDbColumn($this, 'author', ObjectsStorage::T_INT);
		$storage->MapDbColumn($this, 'date_created', ObjectsStorage::T_INT);
		$storage->MapDbColumn($this, 'description', ObjectsStorage::T_CLOB);
	}

	public function Save()
	{
		parent::Save();

		$db = Services::I()->GetDb();

		$db->query("DELETE FROM thankyou_user WHERE thanks_id=int:id", $this->GetProperty('id'));

		foreach ($this->users_ids as $user_id)
		{
			$db->query("INSERT INTO thankyou_user (thanks_id, user_id) VALUES (int:th, int:u)", $this->GetProperty('id'), $user_id);
		}
	}

	public function SetDescription($value)
	{
		$this->SetProperty('description', $value);
	}

	public function SetUsers($users_ids)
	{
		$this->users_ids = $users_ids;
	}

	public function GetUsers()
	{
		// potentially can load users from the database, if they are not loaded yet
		// but not doing this now
		return $this->users_ids;
	}
}
