<?php

namespace Claromentis\ThankYou\Tags;

use Claromentis\ThankYou\Tags\Exceptions\TagInvalidNameException;
use Date;
use User;

class Tag
{
	/**
	 * @var bool $active
	 */
	private $active;

	/**
	 * @var string|null $bg_colour
	 */
	private $bg_colour;

	/**
	 * @var User|null $created_by
	 */
	private $created_by;

	/**
	 * @var Date|null $created_date
	 */
	private $created_date;

	/**
	 * @var int|null $id
	 */
	private $id;

	/**
	 * @var User|null $modified_by
	 */
	private $modified_by;

	/**
	 * @var Date|null $modified_date
	 */
	private $modified_date;

	/**
	 * @var string $name
	 */
	private $name;

	/**
	 * Tag constructor.
	 *
	 * @param string $name
	 * @param bool   $active
	 * @throws TagInvalidNameException - If the Name is an empty string.
	 */
	public function __construct(string $name, bool $active)
	{
		$this->SetActive($active);
		$this->SetName($name);
	}

	/**
	 * @return bool
	 */
	public function GetActive(): bool
	{
		return $this->active;
	}

	/**
	 * @return string|null
	 */
	public function GetBackgroundColour(): ?string
	{
		return $this->bg_colour;
	}

	/**
	 * @return User|null
	 */
	public function GetCreatedBy(): ?User
	{
		return $this->created_by;
	}

	/**
	 * @return Date|null
	 */
	public function GetCreatedDate(): ?Date
	{
		return $this->created_date;
	}

	/**
	 * @return int|null
	 */
	public function GetId(): ?int
	{
		return $this->id;
	}

	/**
	 * @return User|null
	 */
	public function GetModifiedBy(): ?User
	{
		return $this->modified_by;
	}

	/**
	 * @return Date|null
	 */
	public function GetModifiedDate(): ?Date
	{
		return $this->modified_date;
	}

	/**
	 * @return string
	 */
	public function GetName(): string
	{
		return $this->name;
	}

	/**
	 * @param bool $active
	 */
	public function SetActive(bool $active)
	{
		$this->active = $active;
	}

	/**
	 * @param string|null $colour
	 */
	public function SetBackgroundColour(?string $colour)
	{
		$this->bg_colour = $colour;
	}

	/**
	 * @param User|null $user
	 */
	public function SetCreatedBy(?User $user)
	{
		$this->created_by = $user;
	}

	/**
	 * @param int|null $id
	 */
	public function SetId(?int $id)
	{
		$this->id = $id;
	}

	/**
	 * @param Date|null $date
	 */
	public function SetCreatedDate(?Date $date)
	{
		$this->created_date = $date;
	}

	/**
	 * @param User|null $user
	 */
	public function SetModifiedBy(?User $user)
	{
		$this->modified_by = $user;
	}

	/**
	 * @param Date|null $date
	 */
	public function SetModifiedDate(?Date $date)
	{
		$this->modified_date = $date;
	}

	/**
	 * @param string $name
	 * @throws TagInvalidNameException - If the Name is an empty string.
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
