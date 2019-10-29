<?php

namespace Claromentis\ThankYou\Tags;

use Claromentis\ThankYou\Tags\Exceptions\TagInvalidNameException;
use Date;
use InvalidArgumentException;
use User;

class Tag
{
	private $active;

	private $created_by;

	private $created_date;

	private $id;

	private $metadata;

	private $modified_by;

	private $modified_date;

	private $name;

	/**
	 * Tag constructor.
	 *
	 * @param string $name
	 * @param bool   $active
	 * @throws TagInvalidNameException
	 */
	public function __construct(string $name, bool $active)
	{
		$this->SetActive($active);
		$this->SetName($name);
	}

	public function GetActive(): bool
	{
		return $this->active;
	}

	public function GetCreatedBy(): ?User
	{
		return $this->created_by;
	}

	public function GetCreatedDate(): ?Date
	{
		return $this->created_date;
	}

	public function GetId(): ?int
	{
		return $this->id;
	}

	public function GetMetadata(): ?array
	{
		return $this->metadata;
	}

	public function GetModifiedBy(): ?User
	{
		return $this->modified_by;
	}

	public function GetModifiedDate(): ?Date
	{
		return $this->modified_date;
	}

	public function GetName(): string
	{
		return $this->name;
	}

	public function SetActive(bool $active)
	{
		$this->active = $active;
	}

	public function SetCreatedBy(?User $user)
	{
		$this->created_by = $user;
	}

	public function SetId(?int $id)
	{
		$this->id = $id;
	}

	public function SetCreatedDate(?Date $date)
	{
		$this->created_date = $date;
	}

	public function SetMetadata(?array $metadata)
	{
		$this->metadata = $metadata;
	}

	public function SetModifiedBy(?User $user)
	{
		$this->modified_by = $user;
	}

	public function SetModifiedDate(?Date $date)
	{
		$this->modified_date = $date;
	}

	/**
	 * @param string $name
	 * @throws TagInvalidNameException
	 */
	public function SetName(string $name)
	{
		if (trim($name) === '')
		{
			throw new TagInvalidNameException("Failed to Set Tag's Name, cannot use an empty string");
		}
		$this->name = $name;
	}
}
