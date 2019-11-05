<?php
namespace Claromentis\ThankYou;

use ActiveRecord;
use ClaAggregation;
use Claromentis\Core\Services;
use Claromentis\ThankYou\Exception\ThankYouException;
use Exception;
use InvalidArgumentException;
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
class ThanksItem extends ActiveRecord implements ClaAggregation
{
	//TODO: add constructor so that the object can only exist fully instantiated

	const AGGREGATION = 143;

	protected $users_ids;

	private $thanked;

	/**
	 * @return bool|void
	 * @throws ThankYouException
	 */
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
			throw new ThankYouException("Failed to delete Thank You, ID unknown");
		}

		$id = (int) $id;

		$db = Services::I()->GetDb();
		$db->query('DELETE FROM thankyou_user WHERE thanks_id = int:id', $id);
		$db->query('DELETE FROM thankyou_thanked WHERE item_id = int:id', $id);

		parent::Delete();
	}

	/**
	 * @return int|null
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

	/**
	 * @return string|null
	 */
	public function GetDateCreated()
	{
		try
		{
			return $this->GetProperty('date_created');
		} catch (Exception $exception)
		{
			throw new LogicException("Unexpected Exception thrown by GetProperty", null, $exception);
		}
	}

	/**
	 * @return string|null
	 */
	public function GetDescription()
	{
		try
		{
			return $this->GetProperty('description');
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

	public function Load($id)
	{
		if (!($parent_load = parent::Load($id)))
		{
			return $parent_load;
		}

		$db = Services::I()->GetDb();

		$thank_you_users = $db->query("SELECT user_id FROM thankyou_user WHERE thanks_id IN in:int:ids", $id)->fetchAllValues();
		$this->SetUsers($thank_you_users);

		return true;
	}

	/**
	 * @return int
	 * @throws ThankYouException
	 */
	public function Save()
	{
		if (!parent::Save())
		{
			throw new ThankYouException("Failed to Save Thank You");
		}

		$db = Services::I()->GetDb();

		try
		{
			$id = $this->GetProperty('id');
		} catch (Exception $exception)
		{
			throw new LogicException("Unexpected Exception thrown by GetProperty", null, $exception);
		}

		if (isset($this->users_ids))
		{
			$db->query("DELETE FROM thankyou_user WHERE thanks_id=int:id", $id);

			foreach ($this->users_ids as $user_id)
			{
				$db->query("INSERT INTO thankyou_user (thanks_id, user_id) VALUES (int:th, int:u)", $id, $user_id);
			}
		}

		if (isset($this->thanked))
		{
			$db->query("DELETE FROM thankyou_thanked WHERE item_id=int:id", $id);
			foreach ($this->thanked as $thank)
			{
				$db->query("INSERT INTO thankyou_thanked (item_id, object_type, object_id) VALUES (int:tyid, int:otid, int:oid)", $id, $thank['object_type'], $thank['object_id']);
			}
		}

		return (int) $this->GetId();
	}

	/**
	 * @param int $value
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

	/**
	 * @param int $id
	 */
	public function SetId(int $id)
	{
		try
		{
			$this->SetProperty('id', $id);
		} catch (Exception $exception)
		{
			throw new LogicException("Unexpected Exception thrown by Set Property", null, $exception);
		}
	}

	/**
	 * @param array $thanked
	 */
	public function SetThanked(array $thanked)
	{
		foreach ($thanked as $thank)
		{
			if (!is_array($thank) || !isset($thank['object_type']) || !is_int($thank['object_type']) || !isset($thank['object_id']) || !is_int($thank['object_id']))
			{
				throw new InvalidArgumentException("Failed to Set Thanked, invalid Thank provided");
			}
		}
		$this->thanked = $thanked;
	}

	/**
	 * @param $users_ids
	 */
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

	/**
	 * {@inheritDoc}
	 */
	public function GetAggregation(): int
	{
		return self::AGGREGATION;
	}

	/**
	 * {@inheritDoc}
	 */
	public function GetUrl()
	{
		//TODO Add a URL if one ever exists...
		return '';
	}
}
