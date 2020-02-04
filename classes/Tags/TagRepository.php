<?php

namespace Claromentis\ThankYou\Tags;

use Claromentis\Core\DAL\Exceptions\TransactionException;
use Claromentis\Core\DAL\Interfaces\DbInterface;
use Claromentis\Core\DAL\QueryBuilder;
use Claromentis\Core\DAL\QueryFactory;
use Claromentis\Core\DAL\ResultInterface;
use Claromentis\Core\Repository\Exception\StorageException;
use Claromentis\People\InvalidFieldIsNotSingle;
use Claromentis\People\UsersListProvider;
use Claromentis\ThankYou\Tags\Exceptions\TagDuplicateNameException;
use Claromentis\ThankYou\Tags\Exceptions\TagInvalidNameException;
use Claromentis\ThankYou\Tags\Exceptions\TagNotFoundException;
use Date;
use DateTimeZone;
use InvalidArgumentException;
use LogicException;
use Psr\Log\LoggerInterface;
use User;

class TagRepository
{
	const TABLE_NAME = 'thankyou_tag';

	const TAGGING_TABLE = 'thankyou_tagged';

	/**
	 * @var DbInterface
	 */
	private $db;

	/**
	 * @var LoggerInterface
	 */
	protected $logger;

	/**
	 * @var QueryFactory
	 */
	private $query_factory;

	/**
	 * @var TagFactory
	 */
	private $tag_factory;

	public function __construct(DbInterface $db, QueryFactory $query_factory, LoggerInterface $logger, TagFactory $tag_factory)
	{
		$this->db            = $db;
		$this->logger        = $logger;
		$this->query_factory = $query_factory;
		$this->tag_factory   = $tag_factory;
	}

	/**
	 * Returns an array of Tags indexed by their ID.
	 *
	 * @param int[] $ids
	 * @return Tag[]
	 */
	public function GetTags(array $ids): array
	{
		if (count($ids) === 0)
		{
			return [];
		}

		foreach ($ids as $id)
		{
			if (!is_int($id))
			{
				throw new InvalidArgumentException("Failed to Load Tags from Database, Tag IDs must be integers");
			}
		}

		$query   = "SELECT * FROM " . self::TABLE_NAME . " WHERE id IN in:int:ids";
		$results = $this->db->query($query, $ids);

		return $this->GetTagsFromDbResults($results);
	}

	/**
	 * @param int|null    $limit
	 * @param int|null    $offset
	 * @param string|null $name
	 * @param bool|null   $active
	 * @param array|null  $orders
	 * @return Tag[]
	 */
	public function GetFilteredTags(?int $limit = null, ?int $offset = null, ?string $name = null, ?bool $active = null, ?array $orders = null): array
	{
		$query_string = "SELECT * FROM " . self::TABLE_NAME;
		if (isset($orders))
		{
			$query_string .= $this->QueryBuildOrderString($orders);
		}

		$query = new QueryBuilder($query_string);

		if (isset($name))
		{
			$query->AddSubstringFilter('name', $name);
		}

		if ($active !== null)
		{
			$query->AddWhereAndClause("active = int:active", (int) $active);
		}

		$query->setLimit($limit, $offset);

		$results = $this->db->query($query->GetQuery());

		return $this->GetTagsFromDbResults($results);
	}

	/**
	 * Returns the total number of Tags in the Database.
	 *
	 * @return int
	 */
	public function GetTotalTags(): int
	{
		return (int) $this->db->query_row("SELECT COUNT(1) FROM " . self::TABLE_NAME)[0];
	}

	/**
	 * Filters Tags and returns an array of the number of Taggings they have, indexed by their IDs.
	 *
	 * @param int|null   $limit
	 * @param int|null   $offset
	 * @param bool|null  $active
	 * @param array|null $orders
	 * @return array
	 */
	public function GetTagsTaggingTotals(?int $limit = null, ?int $offset = null, ?bool $active = null, ?array $orders = null): array
	{
		$query_string = "SELECT COUNT(" . self::TAGGING_TABLE . ".item_id) AS total, " . self::TAGGING_TABLE . ".tag_id FROM " . self::TAGGING_TABLE . " GROUP BY " . self::TAGGING_TABLE . ".tag_id";

		if (isset($orders))
		{
			$query_string .= $this->QueryBuildOrderString($orders);
		}

		$query = new QueryBuilder($query_string);
		$query->AddJoin(self::TAGGING_TABLE, self::TABLE_NAME, self::TABLE_NAME, self::TABLE_NAME . ".id = " . self::TAGGING_TABLE . ".tag_id");

		if (isset($active))
		{
			$query->AddWhereAndClause("active = int:active", (int) $active);
		}

		$query->setLimit($limit, $offset);

		return $this->GetTagsTotalsFromDbResults($this->db->query($query->GetQuery()));
	}

	/**
	 * Given an array of Tag IDs,
	 * returns the number of times the Tags have been used for Tagging, indexed by the Tag's IDs.
	 *
	 * @param int[] $ids
	 * @return array
	 */
	public function GetTagsTaggingTotalsFromIds(array $ids): array
	{
		if (count($ids) === 0)
		{
			return [];
		}

		$query_string = "SELECT COUNT(item_id) AS total, tag_id FROM " . self::TAGGING_TABLE . " WHERE tag_id IN in:int:ids GROUP BY tag_id";

		return $this->GetTagsTotalsFromDbResults($this->db->query($query_string, $ids));
	}

	/**
	 * Saves a Tag to the database. If the Tag does not have an ID, it will be added.
	 *
	 * @param Tag $tag
	 * @throws TagDuplicateNameException - If the Tag's Name is not unique to the Repository.
	 */
	public function Save(Tag $tag)
	{
		$name = $tag->GetName();

		$id = $tag->GetId();

		if (!$this->IsTagNameUnique($name, $id))
		{
			throw new TagDuplicateNameException("Failed to save Tag, Tag's Name is not unique");
		}

		$db_fields = [
			'str(255):name'     => $name,
			'int:active'        => (int) $tag->GetActive(),
			'int:created_by'    => null,
			'int:created_date'  => null,
			'int:modified_by'   => null,
			'int:modified_date' => null,
		];

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
		if (isset($modified_by))
		{
			$db_fields['int:modified_by'] = $modified_by->GetId();
		}

		$modified_date = $tag->GetModifiedDate();
		if (isset($modified_date))
		{
			$db_fields['int:modified_date'] = $modified_date->format('YmdHis');
		}

		$metadata  = null;
		$bg_colour = $tag->GetBackgroundColour();
		if (isset($bg_colour))
		{
			$metadata = json_encode(['bg_colour' => $bg_colour]);
		}

		$db_fields['clob:metadata'] = $metadata;

		if (!isset($id))
		{
			$query = $this->query_factory->GetQueryInsert(self::TABLE_NAME, $db_fields);
			$this->db->query($query);
			$tag->SetId($this->db->insertId());
		} else
		{
			$query = $this->query_factory->GetQueryUpdate(self::TABLE_NAME, "id=int:id", $db_fields);
			$query->Bind('id', $id);
			$this->db->query($query);
		}
	}

	/**
	 * Delete a Tag and its Taggings records.
	 *
	 * @param int $id
	 * @throws StorageException - If the Tag could not be Deleted from the Repository.
	 */
	public function Delete(int $id)
	{
		try
		{
			$this->db->DoTransaction(function () use ($id) {
				$this->DeleteAllTagTaggings($id);
				$this->db->query("DELETE FROM " . self::TABLE_NAME . " WHERE id=int:id", $id);
			});
		} catch (TransactionException $transaction_exception)
		{
			throw new StorageException("Failed to Delete Tag from the Repository", null, $transaction_exception);
		}
	}

	/**
	 * Given an array of Taggable IDs and their Aggregation ID,
	 * returns a multidimensional array, primarily indexed by each Taggable's ID.
	 * Each of these contains an array of Tags, indexed by the Tagging's ID.
	 *
	 * @param int[] $taggable_ids
	 * @param int   $aggregation_id
	 * @return array[]
	 */
	public function GetTaggablesTags(array $taggable_ids, int $aggregation_id): array
	{
		if (count($taggable_ids) === 0)
		{
			return [];
		}

		$query_string = "SELECT * FROM " . self::TAGGING_TABLE;
		$query        = $this->db->GetQueryBuilder($query_string);

		$this->QueryFilterAggregationId($query, $aggregation_id);
		$this->QueryFilterTaggableId($query, $taggable_ids);

		$results = $this->db->query($query->GetQuery());

		$tag_ids = [];
		$rows    = [];
		while ($row = $results->fetchArray())
		{
			$id          = (int) $row['id'];
			$taggable_id = (int) $row['item_id'];
			$tag_id      = (int) $row['tag_id'];

			$rows[$id]        = ['taggable_id' => $taggable_id, 'tag_id' => $tag_id];
			$tag_ids[$tag_id] = true;
		}

		$tag_ids = array_keys($tag_ids);

		$tags = $this->GetTags($tag_ids);

		$taggables_tags = [];
		foreach ($rows as $tagging_id => $row)
		{
			if (isset($tags[$row['tag_id']]))
			{
				$taggables_tags[$row['taggable_id']][$tagging_id] = $tags[$row['tag_id']];
			}
		}

		return $taggables_tags;
	}

	/**
	 * Saves a Tagging.
	 * If an ID is provided, an existing record will be updated, if not a new entry will be created.
	 *
	 * @param int      $taggable_id
	 * @param int      $aggregation_id
	 * @param int      $tag_id
	 * @param int|null $id
	 * @return int
	 * @throws TagNotFoundException If the Tag with the given ID could not be found.
	 */
	public function SaveTagging(int $taggable_id, int $aggregation_id, int $tag_id, ?int $id = null): int
	{
		$tags = $this->GetTags([$tag_id]);
		if (!isset($tags[$tag_id]))
		{
			throw new TagNotFoundException("Failed to Save Taggable's Tag, Tag with ID '" . (string) $tag_id . "' could not be found");
		}

		$db_fields = [
			'int:item_id'        => $taggable_id,
			'int:aggregation_id' => $aggregation_id,
			'int:tag_id'         => $tag_id
		];

		if (isset($id))
		{
			$query = $this->query_factory->GetQueryUpdate(self::TAGGING_TABLE, "id=int:id", $db_fields);
			$query->Bind('id', $id);
			$this->db->query($query);
		} else
		{
			$query = $this->query_factory->GetQueryInsert(self::TAGGING_TABLE, $db_fields);
			$this->db->query($query);
			$id = $this->db->insertId();
		}

		return $id;
	}

	/**
	 * Deletes a Taggable's Taggings.
	 * If a Tag ID is specified, only Taggings with that Tag will be deleted.
	 *
	 * @param int      $taggable_id
	 * @param int      $aggregation_id
	 * @param int|null $tag_id
	 */
	public function DeleteTaggableTaggings(int $taggable_id, int $aggregation_id, ?int $tag_id = null)
	{
		$params       = [$taggable_id, $aggregation_id];
		$query_string = "DELETE FROM " . self::TAGGING_TABLE . " WHERE item_id=int:taggable_id AND aggregation_id=int:aggregation_id";

		if (isset($tag_id))
		{
			$params[]     = $tag_id;
			$query_string .= " AND tag_id=int:tag_id";
		}

		$this->db->query($query_string, ...$params);
	}

	/**
	 * Deletes a Tag's Taggings.
	 *
	 * @param int $tag_id
	 */
	public function DeleteAllTagTaggings(int $tag_id)
	{
		$query_string = "DELETE FROM " . self::TAGGING_TABLE . " WHERE tag_id=int:tag_id";

		$this->db->query($query_string, $tag_id);
	}

	/**
	 * Detemines whether a Tag's Name is unique. If an ID is given, results for this ID will not be retrieved.
	 *
	 * @param string   $name
	 * @param int|null $id
	 * @return bool
	 */
	public function IsTagNameUnique(string $name, ?int $id): bool
	{
		$params       = [$name];
		$query_string = "SELECT COUNT(1) FROM " . self::TABLE_NAME . " WHERE name=str:name";

		if (isset($id))
		{
			$params[]     = $id;
			$query_string .= " AND id!=int:id";
		}

		return !(bool) $this->db->query_row($query_string, ...$params)[0];
	}

	/**
	 * Returns an array of Tags indexed by their ID.
	 *
	 * @param ResultInterface $results
	 * @return Tag[]
	 */
	private function GetTagsFromDbResults(ResultInterface $results): array
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
				$this->logger->error("Corrupted Tag data for Tag ID $id, invalid Name", $row);
				continue;
			}
			try
			{
				$tag = $this->tag_factory->Create($row['name'], $row['active']);
			} catch (TagInvalidNameException $exception)
			{
				$this->logger->error("Corrupted Tag data for Tag ID $id, invalid Name", $row);
				continue;
			}
			$tag->SetId($id);
			$tag->SetCreatedBy($users[(int) $row['created_by']] ?? null);
			$tag->SetCreatedDate(new Date($row['created_date'], new DateTimeZone('UTC')));
			$tag->SetModifiedBy($users[(int) $row['modified_by']] ?? null);
			$tag->SetModifiedDate(new Date($row['modified_date'], new DateTimeZone('UTC')));

			$metadata = json_decode($row['metadata'], true);
			if (isset($metadata['bg_colour']) && is_string($metadata['bg_colour']))
			{
				$tag->SetBackgroundColour($metadata['bg_colour']);
			}

			$tags[$id] = $tag;
		}

		return $tags;
	}

	/**
	 * Returns the number of times the given Tags have been used for Tagging, indexed by the Tag's IDs.
	 *
	 * @param ResultInterface $results
	 * @return int[]
	 */
	private function GetTagsTotalsFromDbResults(ResultInterface $results): array
	{
		$tag_tagging_totals = [];
		while ($row = $results->fetchArray())
		{
			$tag_tagging_totals[(int) $row['tag_id']] = (int) $row['total'];
		}

		return $tag_tagging_totals;
	}

	/**
	 * Returns an array of Users indexed by their ID.
	 *
	 * @param array $user_ids
	 * @return User[]
	 */
	private function GetUsers(array $user_ids): array
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

	private function QueryFilterAggregationId(QueryBuilder $query, int $aggregation_id)
	{
		$query->AddWhereAndClause(self::TAGGING_TABLE . ".aggregation_id=int:aggregation_id", $aggregation_id);
	}

	private function QueryFilterTaggableId(QueryBuilder $query, $taggable_ids)
	{
		if (is_int($taggable_ids))
		{
			$query->AddWhereAndClause(self::TAGGING_TABLE . ".item_id=int:taggable_ids", $taggable_ids);
		} elseif (is_array($taggable_ids))
		{
			$query->AddWhereAndClause(self::TAGGING_TABLE . ".item_id IN in:int:taggable_ids", $taggable_ids);
		} else
		{
			throw new InvalidArgumentException("Failed to Add Taggable ID Filter to Query, invalid value for parameter taggable_ids given: " . (string) $taggable_ids);
		}
	}

	/**
	 * Given an array of Orders, returns the Order String to be added to the Query.
	 *
	 * @param array[] $orders
	 * @return string
	 */
	private function QueryBuildOrderString(array $orders)
	{
		if (count($orders) === 0)
		{
			return '';
		}

		$query_string = " ORDER BY";
		foreach ($orders as $offset => $order)
		{
			$column    = $order['column'] ?? null;
			$direction = (isset($order['desc']) && $order['desc'] === true) ? 'DESC' : 'ASC';
			if (!isset($column) || !is_string($column))
			{
				throw new InvalidArgumentException("Failed to Tag Query Order String, one or more Orders does not have a column");
			}
			$query_string .= " " . $column . " " . $direction;
		}

		return $query_string;
	}
}
