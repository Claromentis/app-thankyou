<?php
namespace Claromentis\ThankYou;

use ActiveRecord;
use ClaAggregation;
use Claromentis\Core\Services;
use Claromentis\ThankYou\ThankYous\ThankYousRepository;
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
class ThanksItem extends ActiveRecord implements ClaAggregation
{
	protected $users_ids;

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
	 * @param $users_ids
	 */
	public function SetUsers($users_ids)
	{
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
		return ThankYousRepository::AGGREGATION_ID;
	}

	/**
	 * {@inheritDoc}
	 */
	public function GetUrl()
	{
		return '';
	}
}
