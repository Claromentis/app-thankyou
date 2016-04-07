<?php

namespace Claromentis\ThankYou;
use ObjectsStorage;

/**
 * Description of ThanksItem.php
 *
 * @author Alexander Polyanskikh
 */
class ThanksItem extends \ActiveRecord
{
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

		global $db;

		$db->query("DELETE FROM thankyou_user WHERE thanks_id=int:id", $this->GetProperty('id'));
		foreach ($this->users_ids as $user_id)
		{
			$db->query("INSERT INTO thankyou_user (thanks_id, user_id) VALUES (int:th, int:u)", $this->GetProperty('id'), $user_id);
		}
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