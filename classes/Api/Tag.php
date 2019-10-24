<?php

namespace Claromentis\ThankYou\Api;

use Claromentis\ThankYou\Tags\Exceptions\TagDuplicateNameException;
use Claromentis\ThankYou\Tags\TagFactory;
use Claromentis\ThankYou\Tags\TagRepository;
use Date;
use InvalidArgumentException;
use LogicException;
use OutOfBoundsException;
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
	 * @param User       $user
	 * @param string     $name
	 * @param array|null $metadata
	 * @return \Claromentis\ThankYou\Tags\Tag
	 * @throws LogicException
	 * @throws TagDuplicateNameException
	 */
	public function CreateAndSave(User $user, string $name, ?array $metadata): \Claromentis\ThankYou\Tags\Tag
	{
		//TODO: Add Permissions.
		$tag = $this->factory->Create($name);
		$tag->SetCreatedBy($user);
		$tag->SetCreatedDate(new Date());
		$tag->SetMetadata($metadata);
		$tag->SetModifiedBy($user);
		$tag->SetModifiedDate(new Date());

		try
		{
			$this->Save($tag);

			return $tag;
		} catch (InvalidArgumentException $exception)
		{
			throw new LogicException("Unexpected Exception thrown when Saving Tag to database", null, $exception);
		}
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
	 * @throws LogicException
	 * @throws OutOfBoundsException
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
			throw new OutOfBoundsException("Failed to Get Tag, Tag with ID '" . $id . "' could not be found");
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
	 * @throws InvalidArgumentException
	 * @throws TagDuplicateNameException
	 */
	public function Save(\Claromentis\ThankYou\Tags\Tag $tag)
	{
		$this->repository->Save($tag);
	}
}
