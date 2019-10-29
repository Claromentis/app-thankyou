<?php

namespace Claromentis\ThankYou\Tags;

use Claromentis\Core\DAL\Interfaces\DbInterface;
use Claromentis\Core\DAL\QueryFactory;
use Claromentis\Core\DAL\ResultInterface;
use Claromentis\People\InvalidFieldIsNotSingle;
use Claromentis\People\UsersListProvider;
use Claromentis\ThankYou\Tags\Exceptions\TagDuplicateNameException;
use Claromentis\ThankYou\Tags\Exceptions\TagInvalidNameException;
use Date;
use DateTimeZone;
use InvalidArgumentException;
use LogicException;
use Psr\Log\LoggerInterface;
use RuntimeException;
use User;

class TagRepository
{
	private $db;

	protected $log;

	private $query_factory;

	private $tag_factory;

	public function __construct(DbInterface $db, QueryFactory $query_factory, LoggerInterface $log, TagFactory $tag_factory)
	{
		$this->db            = $db;
		$this->log           = $log;
		$this->query_factory = $query_factory;
		$this->tag_factory   = $tag_factory;
	}

	/**
	 * @param int $limit
	 * @param int $offset
	 * @return Tag[]
	 */
	public function GetActiveAlphabeticTags(int $limit, int $offset): array
	{
		$query   = "SELECT * FROM thankyou_tag ORDER BY active DESC, name ASC LIMIT int:limit OFFSET int:offset";
		$results = $this->db->query($query, $limit, $offset);

		return $this->GetTagsFromDbQuery($results);
	}

	/**
	 * @param int $limit
	 * @param int $offset
	 * @return Tag[]
	 */
	public function GetRecentTags(int $limit, int $offset): array
	{
		$query   = "SELECT * FROM thankyou_tag ORDER BY modified_date DESC LIMIT int:limit OFFSET int:offset";
		$results = $this->db->query($query, $limit, $offset);

		return $this->GetTagsFromDbQuery($results);
	}

	/**
	 * @return int
	 */
	public function GetTotalTags(): int
	{
		return (int) $this->db->query_row("SELECT COUNT(1) FROM thankyou_tag")[0];
	}

	/**
	 * @param Tag $tag
	 * @throws TagDuplicateNameException if the Tags Name is not unique to the database.
	 * @throws InvalidArgumentException if the Tags Modified By is null.
	 * @throws InvalidArgumentException if the Tags Modified Date is null.
	 * @throws InvalidArgumentException if the Tags Created By and ID are null.
	 * @throws InvalidArgumentException if the Tags Created Date and ID are null.
	 */
	public function Save(Tag $tag)
	{
		$name = $tag->GetName();

		$id = $tag->GetId();

		if (!$this->IsTagNameUnique($name, $id))
		{
			throw new TagDuplicateNameException("Failed to save Tag, Tag's Name is not unique");
		}

		$db_fields = ['str(255):name' => $name, 'int:active' => (int) $tag->GetActive()];

		$created_by = $tag->GetCreatedBy();
		if (isset($created_by))
		{
			$db_fields['int:created_by'] = $created_by->GetId();
		}

		$created_date = $tag->GetCreatedDate();
		if (isset($created_date))
		{
			$db_fields['int:created_date'] = $created_date->format('YmdHis');
		}

		$modified_by = $tag->GetModifiedBy();
		if (!isset($modified_by))
		{
			throw new InvalidArgumentException("Failed to Save Tag, Modified By undefined");
		}
		$db_fields['int:modified_by'] = $modified_by->GetId();

		$modified_date = $tag->GetModifiedDate();
		if (!isset($modified_date))
		{
			throw new InvalidArgumentException("Failed to Save Tag, Modified Date undefined");
		}
		$db_fields['int:modified_date'] = $modified_date->format('YmdHis');

		$metadata = $tag->GetMetadata();
		if (isset($metadata))
		{
			$metadata = json_encode($metadata);
		}
		$db_fields['clob:metadata'] = $metadata;

		if (!isset($id))
		{
			if (!isset($created_by))
			{
				throw new InvalidArgumentException("Failed to Save new Tag, Created By undefined");
			}

			if (!isset($created_date))
			{
				throw new InvalidArgumentException("Failed to Save new Tag, Created Date undefined");
			}

			$query = $this->query_factory->GetQueryInsert('thankyou_tag', $db_fields);
			$this->db->query($query);
			$tag->SetId($this->db->insertId());
		} else
		{
			$query = $this->query_factory->GetQueryUpdate('thankyou_tag', "id=int:id", $db_fields);
			$query->Bind('id', $id);
			$this->db->query($query);
		}
	}

	/**
	 * @param int $id
	 */
	public function Delete(int $id)
	{
		$this->db->query("DELETE FROM thankyou_tag WHERE id=int:id", $id);
	}

	public function IsTagNameUnique(string $name, ?int $id): bool
	{
		if (!isset($id))
		{
			return !(bool) $this->db->query_row("SELECT COUNT(1) FROM thankyou_tag WHERE name=str:name", $name)[0];
		} else
		{
			return !(bool) $this->db->query_row("SELECT COUNT(1) FROM thankyou_tag WHERE name=str:name AND id!=int:id", $name, $id)[0];
		}
	}

	/**
	 * @param int[] $ids
	 * @return Tag[]
	 * @throws InvalidArgumentException
	 */
	public function Load(array $ids): array
	{
		foreach ($ids as $id)
		{
			if (!is_int($id))
			{
				throw new InvalidArgumentException("Failed to Load Tags from Database, Tag IDs must be integers");
			}
		}

		$query   = "SELECT * FROM thankyou_tag WHERE id IN in:int:ids";
		$results = $this->db->query($query, $ids);

		return $this->GetTagsFromDbQuery($results);
	}

	/**
	 * @param ResultInterface $results
	 * @return Tag[]
	 * @throws LogicException
	 */
	private function GetTagsFromDbQuery(ResultInterface $results): array
	{
		$rows  = [];
		$users = [];
		while ($row = $results->fetchArray())
		{
			$rows[$row['id']] = $row;

			$users[(int) $row['created_by']]  = null;
			$users[(int) $row['modified_by']] = null;
		}

		$users = $this->GetUsers(array_keys($users));

		$tags = [];
		foreach ($rows as $id => $row)
		{
			if (!isset($row['name']) || !is_string($row['name']))
			{
				$this->log->error("Failed to Get Tags From Db Query, one or more Tags could not be constructed due to invalid database data");
				continue;
			}
			try
			{
				$tag = $this->tag_factory->Create($row['name'], $row['active']);
			} catch (TagInvalidNameException $exception)
			{
				$this->log->error("Failed to Get Tags From Db Query, one or more Tags could not be constructed due to invalid database data");
				continue;
			}
			$tag->SetCreatedBy($users[(int) $row['created_by']] ?? null);
			$tag->SetCreatedDate(new Date($row['created_date'], new DateTimeZone('UTC')));
			$tag->SetId($id);
			$tag->SetMetadata(json_decode($row['metadata'], true));
			$tag->SetModifiedBy($users[(int) $row['modified_by']] ?? null);
			$tag->SetModifiedDate(new Date($row['created_date'], new DateTimeZone('UTC')));
			$tags[$id] = $tag;
		}

		return $tags;
	}

	/**
	 * Returns an array of Users indexed by their ID.
	 *
	 * @param array $user_ids
	 * @return User[]
	 * @throws LogicException
	 */
	private function GetUsers(array $user_ids)
		//TODO: Get rid of this method when possible, this class should be able to use something else to mass build Users really.
	{
		$users_list_provider = new UsersListProvider();
		$users_list_provider->SetFilterProtectExtranets(false);
		$users_list_provider->SetFilterIds($user_ids);
		try
		{
			return $users_list_provider->GetListObjects();
		} catch (InvalidFieldIsNotSingle $invalid_field_is_not_single)
		{
			throw new LogicException("Unexpected InvalidFieldIsNotSingle Exception throw by UserListProvider, GetListObjects", null, $invalid_field_is_not_single);
		}
	}

	//TODO: Add method GetTagFromDbRow for direct calls. Currently only works as an extension of the ThankTagRepository.
}
