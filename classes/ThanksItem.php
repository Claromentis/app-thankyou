<?php
namespace Claromentis\ThankYou;

use ActiveRecord;
use Claromentis\Core\Services;
use Claromentis\ThankYou\Exception\ThankYouRuntimeException;
use Exception;
use LogicException;
use ObjectsStorage;

/**
 * A thank you item.
 *
 * @property-read int    $id
 * @property-read int    $author
 * @property-read int    $date_created
 * @property-read string $description
 */
class ThanksItem extends ActiveRecord
{
	//TODO: add constructor so that the object can only exist fully instantiated

	const AGGREGATION = 143;

	protected $users_ids = null;

	public function Delete()
	{
		try
		{
			$id = $this->GetProperty('id');
		} catch (Exception $exception)
		{
			throw new LogicException("Unexpected Exception thrown by GetProperty", null, $exception);
		}

		if (!isset($id))
		{
			throw new ThankYouRuntimeException("Failed to delete Thank You, ID unknown");
		}

		$id = (int) $id;

		$db = Services::I()->GetDb();
		$db->query('DELETE FROM thankyou_user WHERE thanks_id = int:id', $id);

		parent::Delete();
	}

	/**
	 * @return int
	 * @throws LogicException
	 */
	public function GetAuthor()
	{
		try
		{
			return (int) $this->GetProperty('author');
		} catch (Exception $exception)
		{
			throw new LogicException("Unexpected Exception thrown by GetProperty", null, $exception);
		}
	}

	public function InitDbMapping(ObjectsStorage $storage)
	{
		$storage->MapDbTable($this, 'thankyou_item', 'id');

		$storage->MapDbColumn($this, 'id', ObjectsStorage::T_INT);
		$storage->MapDbColumn($this, 'author', ObjectsStorage::T_INT);
		$storage->MapDbColumn($this, 'date_created', ObjectsStorage::T_INT);
		$storage->MapDbColumn($this, 'description', ObjectsStorage::T_CLOB);
	}

	/**
	 * @throws ThankYouRuntimeException
	 * @throws LogicException
	 */
	public function Save()
	{
		if (!parent::Save())
		{
			throw new ThankYouRuntimeException("Failed to Save Thank You");
		}

		$db = Services::I()->GetDb();

		try{
			$id = $this->GetProperty('id');
		} catch (Exception $exception)
		{
			throw new LogicException("Unexpected Exception thrown by GetProperty", null, $exception);
		}

		$db->query("DELETE FROM thankyou_user WHERE thanks_id=int:id", $id);

		foreach ($this->users_ids as $user_id)
		{
			$db->query("INSERT INTO thankyou_user (thanks_id, user_id) VALUES (int:th, int:u)", $id, $user_id);
		}
	}

	/**
	 * @param int $value
	 * @throws LogicException
	 */
	public function SetAuthor(int $value)
	{
		try
		{
			$this->SetProperty('author', $value);
		} catch (Exception $exception)
		{
			throw new LogicException("Unexpected Exception thrown by SetProperty", null, $exception);
		}
	}

	/**
	 * @param string $value
	 * @throws LogicException
	 */
	public function SetDateCreated(string $value)
	{
		try
		{
			$this->SetProperty('date_created', $value);
		} catch (Exception $exception)
		{
			throw new LogicException("Unexpected Exception thrown by SetProperty", null, $exception);
		}
	}

	/**
	 * @param $value
	 * @throws LogicException
	 */
	public function SetDescription($value)
	{
		try
		{
			$this->SetProperty('description', $value);
		} catch (Exception $exception)
		{
			throw new LogicException("Unexpected Exception thrown by Set Property", null, $exception);
		}
	}

	public function SetUsers($users_ids)
	{
		//TODO: Validate Users
		if (!is_array($users_ids))
		{
			$users_ids = [$users_ids];
		}

		foreach ($users_ids as $offset => $user_id)
		{
			$users_ids[$offset] = (int) $user_id;
		}

		$this->users_ids = $users_ids;
	}

	public function GetUsers()
	{
		// potentially can load users from the database, if they are not loaded yet
		// but not doing this now
		return $this->users_ids;
	}
}
