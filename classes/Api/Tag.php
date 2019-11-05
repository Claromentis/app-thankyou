<?php

namespace Claromentis\ThankYou\Api;

use Claromentis\ThankYou\Tags\Exceptions\TagCreatedByException;
use Claromentis\ThankYou\Tags\Exceptions\TagCreatedDateException;
use Claromentis\ThankYou\Tags\Exceptions\TagDuplicateNameException;
use Claromentis\ThankYou\Tags\Exceptions\TagInvalidNameException;
use Claromentis\ThankYou\Tags\Exceptions\TagModifiedByException;
use Claromentis\ThankYou\Tags\Exceptions\TagModifiedDateException;
use Claromentis\ThankYou\Tags\Exceptions\TagNotFound;
use Claromentis\ThankYou\Tags\TagFactory;
use Claromentis\ThankYou\Tags\TagRepository;
use Date;
use InvalidArgumentException;
use LogicException;
use User;

class Tag
{
	private $factory;

	private $repository;

	public function __construct(TagRepository $tag_repository, TagFactory $tag_factory)
	{
		$this->repository = $tag_repository;
		$this->factory    = $tag_factory;
	}

	/**
	 * @param User   $user
	 * @param string $name
	 * @return \Claromentis\ThankYou\Tags\Tag
	 * @throws TagInvalidNameException
	 */
	public function Create(User $user, string $name): \Claromentis\ThankYou\Tags\Tag
	{
		$tag = $this->factory->Create($name);
		$tag->SetCreatedBy($user);
		$tag->SetCreatedDate(new Date());
		$tag->SetModifiedBy($user);
		$tag->SetModifiedDate(new Date());

		return $tag;
	}

	/**
	 * @param int $id
	 */
	public function Delete(int $id)
	{
		$this->repository->Delete($id);
	}

	/**
	 * @param int $limit
	 * @param int $offset
	 * @return Tag[]
	 */
	public function GetAlphabeticTags(int $limit, int $offset): array
	{
		return $this->repository->GetAlphabeticTags($limit, $offset);
	}

	/**
	 * @param int $limit
	 * @param int $offset
	 * @return Tag[]
	 */
	public function GetActiveAlphabeticTags(int $limit, int $offset): array
	{
		return $this->repository->GetActiveAlphabeticTags($limit, $offset);
	}

	/**
	 * @param int $limit
	 * @param int $offset
	 * @return Tag[]
	 */
	public function GetRecentTags(int $limit, int $offset): array
	{
		return $this->repository->GetRecentTags($limit, $offset);
	}

	/**
	 * @param int $id
	 * @return \Claromentis\ThankYou\Tags\Tag
	 * @throws TagNotFound
	 */
	public function GetTag(int $id): \Claromentis\ThankYou\Tags\Tag
	{
		try
		{
			$tag = $this->repository->Load([$id]);
		} catch (InvalidArgumentException $exception)
		{
			throw new LogicException("Failed to Get Tag, unexpected Exception thrown when Loading Tag", null, $exception);
		}

		if (!isset($tag[$id]))
		{
			throw new TagNotFound("Failed to Get Tag, Tag with ID '" . $id . "' could not be found");
		}

		return $tag[$id];
	}

	/**
	 * @return int
	 */
	public function GetTotalTags(): int
	{
		return $this->repository->GetTotalTags();
	}

	/**
	 * @param \Claromentis\ThankYou\Tags\Tag $tag
	 * @throws TagDuplicateNameException - If the Tag's Name is not unique to the Repository.
	 * @throws TagModifiedByException - If the Tag's Modified By has not been defined.
	 * @throws TagModifiedDateException - If the Tag's Modified Date has not been defined.
	 * @throws TagCreatedByException - If the Tag's Created By has not been defined.
	 * @throws TagCreatedDateException - If the Tag's Created Date has not been defined.
	 */
	public function Save(\Claromentis\ThankYou\Tags\Tag $tag)
	{
		$this->repository->Save($tag);
	}
}
