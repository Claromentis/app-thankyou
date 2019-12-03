<?php

namespace Claromentis\ThankYou\Tags;

use Claromentis\Core\DAL\Interfaces\DbInterface;
use Claromentis\Core\DAL\QueryBuilder;
use Claromentis\Core\DAL\QueryFactory;
use Claromentis\Core\DAL\ResultInterface;
use Claromentis\People\InvalidFieldIsNotSingle;
use Claromentis\People\UsersListProvider;
use Claromentis\ThankYou\Tags\Exceptions\TagDuplicateNameException;
use Claromentis\ThankYou\Tags\Exceptions\TagInvalidNameException;
use Claromentis\ThankYou\Tags\Exceptions\TagNotFound;
use Date;
use DateTimeZone;
use InvalidArgumentException;
use LogicException;
use Psr\Log\LoggerInterface;
use User;

class TagRepository
{
	const TABLE_NAME = 'thankyou_tag';

	const TAGGED_TABLE = 'thankyou_tagged';

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

		return $this->GetTagsFromDbQuery($results);
	}

	/**
	 * @param int|null    $limit
	 * @param int|null    $offset
	 * @param string|null $name
	 * @param array|null  $orders
	 * @return Tag[]
	 */
	public function GetFilteredTags(?int $limit = null, ?int $offset = null, ?string $name = null, ?array $orders = null): array
	{
		$query_string = "SELECT * FROM " . self::TABLE_NAME;
		if (isset($orders) && count($orders) > 0)
		{
			$query_string .= " ORDER BY";
			foreach ($orders as $order)
			{
				$column    = $order['column'] ?? null;
				$direction = (isset($order['desc']) && $order['desc'] === true) ? 'DESC' : 'ASC';
				if (!isset($column) || !is_string($column))
				{
					throw new InvalidArgumentException("Failed to GetTags, one or more Orders does not have a column");
				}
				$query_string .= " " . $column . " " . $direction;
			}
		}

		$query = new QueryBuilder($query_string);
		if (isset($name))
		{
			$query->AddSubstringFilter('name', $name);
		}

		$query->setLimit($limit, $offset);

		$results = $this->db->query($query->GetQuery());

		return $this->GetTagsFromDbQuery($results);
	}

	/**
	 * @return int
	 */
	public function GetTotalTags(): int
	{
		return (int) $this->db->query_row("SELECT COUNT(1) FROM " . self::TABLE_NAME)[0];
	}

	/**
	 * @param int|null   $limit
	 * @param int|null   $offset
	 * @param bool|null  $active
	 * @param array|null $orders
	 * @return array
	 */
	public function GetTagsTaggedTotals(?int $limit = null, ?int $offset = null, ?bool $active = null, ?array $orders = null): array
	{
		$query_string = "SELECT COUNT(" . self::TAGGED_TABLE . ".item_id) AS total, " . self::TAGGED_TABLE . ".tag_id FROM " . self::TAGGED_TABLE . " GROUP BY " . self::TAGGED_TABLE . ".tag_id";

		if (isset($orders) && count($orders) > 0)
		{
			$query_string .= " ORDER BY";
			foreach ($orders as $offset => $order)
			{
				$column    = $order['column'] ?? null;
				$direction = (isset($order['desc']) && $order['desc'] === true) ? 'DESC' : 'ASC';
				if (!isset($column) || !is_string($column))
				{
					throw new InvalidArgumentException("Failed to GetTags, one or more Orders does not have a column");
				}
				$query_string .= " " . $column . " " . $direction;
			}
		}

		$query = new QueryBuilder($query_string);
		$query->AddJoin(self::TAGGED_TABLE, self::TABLE_NAME, 'tag', "tag.id = " . self::TAGGED_TABLE . ".tag_id");

		if (isset($active))
		{
			$query->AddWhereAndClause("active = " . (int) $active);
		}

		$query->setLimit($limit, $offset);

		return $this->GetTagsTotalsFromDbQuery($this->db->query($query->GetQuery()));
	}

	/**
	 * @param int[] $ids
	 * @return array
	 */
	public function GetTagsTaggedTotalsFromIds(array $ids): array
	{
		if (count($ids) === 0)
		{
			return [];
		}

		$query_string = "SELECT COUNT(item_id) AS total, tag_id FROM " . self::TABLE_NAME . " WHERE tag_id IN in:int:ids GROUP BY tag_id";

		return $this->GetTagsTotalsFromDbQuery($this->db->query($query_string, $ids));
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
		if (!isset($modified_date))
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
	 * @param int $id
	 */
	public function Delete(int $id)
	{
		$this->DeleteTagTaggeds($id);
		$this->db->query("DELETE FROM " . self::TABLE_NAME . " WHERE id=int:id", $id);
	}

	/**
	 * Given an Aggregation ID, and Tagged IDs, returns an array of Taggings, indexed by the Tagged's ID.
	 *
	 * @param int[] $tagged_ids
	 * @param int   $aggregation_id
	 * @return array[]
	 */
	public function GetTaggedsTags(array $tagged_ids, int $aggregation_id)
	{
		if (count($tagged_ids) === 0)
		{
			return [];
		}

		$query_string = "SELECT * FROM " . self::TAGGED_TABLE;
		$query        = $this->db->GetQueryBuilder($query_string);

		$this->QueryFilterAggregationId($query, $aggregation_id);
		$this->QueryFilterTaggedId($query, $tagged_ids);

		$results = $this->db->query($query->GetQuery());

		$tag_ids = [];
		$rows    = [];
		while ($row = $results->fetchArray())
		{
			$id        = (int) $row['id'];
			$tagged_id = (int) $row['item_id'];
			$tag_id    = (int) $row['tag_id'];

			$rows[$id]        = ['tagged_id' => $tagged_id, 'tag_id' => $tag_id];
			$tag_ids[$tag_id] = true;
		}

		$tag_ids = array_keys($tag_ids);

		$tags = $this->GetTags($tag_ids);

		$taggeds_tags = [];
		foreach ($rows as $tagging_id => $row)
		{
			if (isset($tags[$row['tag_id']]))
			{
				$taggeds_tags[$row['tagged_id']][$tagging_id] = $tags[$row['tag_id']];
			}
		}

		return $taggeds_tags;
	}

	/**
	 * Saves a Tagged's Tag. If an ID is provided, an existing record will be updated, if not a new entry will be created.
	 *
	 * @param int      $tagged_id
	 * @param int      $aggregation_id
	 * @param int      $tag_id
	 * @param int|null $id
	 * @return int
	 * @throws TagNotFound If the Tag with the given ID could not be found.
	 */
	public function SaveTaggedTag(int $tagged_id, int $aggregation_id, int $tag_id, ?int $id = null)
	{
		$tags = $this->GetTags([$tag_id]);
		if (!isset($tags[$tag_id]))
		{
			throw new TagNotFound("Failed to Save Tagged's Tag, Tag with ID '" . (string) $tag_id . "' could not be found");
		}

		$db_fields = [
			'int:item_id'        => $tagged_id,
			'int:aggregation_id' => $aggregation_id,
			'int:tag_id'         => $tag_id
		];

		if (isset($id))
		{
			$query = $this->query_factory->GetQueryUpdate(self::TAGGED_TABLE, "id=int:id", $db_fields);
			$query->Bind('id', $id);
			$this->db->query($query);
		} else
		{
			$query = $this->query_factory->GetQueryInsert(self::TAGGED_TABLE, $db_fields);
			$this->db->query($query);
			$id = $this->db->insertId();
		}

		return $id;
	}

	/**
	 * Deletes a Tagged's Tags. If a Tag ID is specified, only usages of that Tag will be deleted.
	 *
	 * @param int      $tagged_id
	 * @param int      $aggregation_id
	 * @param int|null $tag_id
	 */
	public function DeleteTaggedTags(int $tagged_id, int $aggregation_id, ?int $tag_id = null)
	{
		$params = [$tagged_id, $aggregation_id];
		$query_string = "DELETE FROM " . self::TAGGED_TABLE . " WHERE item_id=int:tagged_id AND aggregation_id=int:aggregation_id";

		if (isset($tag_id))
		{
			$params[] = $tag_id;
			$query_string .= " AND tag_id=int:tag_id";
		}

		$this->db->query($query_string, ...$params);
	}

	/**
	 * Deletes a Tag's Taggings.
	 *
	 * @param int $tag_id
	 */
	public function DeleteTagTaggeds(int $tag_id)
	{
		$query_string = "DELETE FROM " .self::TAGGED_TABLE . " WHERE tag_id=int:tag_id";

		$this->db->query($query_string, $tag_id);
	}

	public function IsTagNameUnique(string $name, ?int $id): bool
	{
		if (!isset($id))
		{
			return !(bool) $this->db->query_row("SELECT COUNT(1) FROM " . self::TABLE_NAME . " WHERE name=str:name", $name)[0];
		} else
		{
			return !(bool) $this->db->query_row("SELECT COUNT(1) FROM " . self::TABLE_NAME . " WHERE name=str:name AND id!=int:id", $name, $id)[0];
		}
	}

	/**
	 * Returns an array of Tags indexed by their ID.
	 *
	 * @param ResultInterface $results
	 * @return Tag[]
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
	 * @param ResultInterface $results
	 * @return array
	 */
	private function GetTagsTotalsFromDbQuery(ResultInterface $results): array
	{
		$tags_tagged_totals = [];
		while ($row = $results->fetchArray())
		{
			$tags_tagged_totals[(int) $row['tag_id']] = (int) $row['total'];
		}

		return $tags_tagged_totals;
	}

	/**
	 * Returns an array of Users indexed by their ID.
	 *
	 * @param array $user_ids
	 * @return User[]
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

	private function QueryFilterAggregationId(QueryBuilder $query, int $aggregation_id)
	{
		$query->AddWhereAndClause(self::TAGGED_TABLE . ".aggregation_id=int:aggregation_id", $aggregation_id);
	}

	private function QueryFilterTaggedId(QueryBuilder $query, $tagged_ids)
	{
		if (is_int($tagged_ids))
		{
			$query->AddWhereAndClause(self::TAGGED_TABLE . ".item_id=int:tagged_ids", $tagged_ids);
		} elseif (is_array($tagged_ids))
		{
			$query->AddWhereAndClause(self::TAGGED_TABLE . ".item_id IN in:int:tagged_ids", $tagged_ids);
		} else
		{
			throw new InvalidArgumentException("Failed to Add Tagged ID Filter to Query, invalid value for parameter tagged_ids given: " . (string) $tagged_ids);
		}
	}
}
