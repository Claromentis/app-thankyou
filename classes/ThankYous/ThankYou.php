<?php

namespace Claromentis\ThankYou\ThankYous;

use Date;
use InvalidArgumentException;
use User;

class ThankYou
{
	private $author;

	private $date_created;

	private $description;

	private $id;

	private $thanked;

	private $users;

	/**
	 * ThankYou constructor.
	 *
	 * @param User   $author
	 * @param Date   $date_created
	 * @param string $description
	 */
	public function __construct(User $author, Date $date_created, string $description)
	{
		$this->SetAuthor($author);
		$this->SetDateCreated($date_created);
		$this->SetDescription($description);
	}

	/**
	 * @return User
	 */
	public function GetAuthor(): User
	{
		return $this->author;
	}

	/**
	 * @return int|null
	 */
	public function GetId(): ?int
	{
		return $this->id;
	}

	/**
	 * @return Date
	 */
	public function GetDateCreated(): Date
	{
		return $this->date_created;
	}

	/**
	 * @return string
	 */
	public function GetDescription(): string
	{
		return $this->description;
	}

	/**
	 * @return Thankable[]|null
	 */
	public function GetThankable(): ?array
	{
		return $this->thanked;
	}

	/**
	 * @return User[]|null
	 */
	public function GetUsers(): ?array
	{
		return $this->users;
	}

	/**
	 * @param string $description
	 */
	public function SetDescription(string $description)
	{
		$this->description = $description;
	}

	/**
	 * @param int|null $id
	 */
	public function SetId(?int $id)
	{
		$this->id = $id;
	}

	/**
	 * @param Thankable[] $thanked
	 */
	public function SetThanked(?array $thanked)
	{
		if (is_array($thanked))
		{
			foreach ($thanked as $thanked_object)
			{
				if (!($thanked_object instanceof Thankable))
				{
					throw new InvalidArgumentException("Failed to Set Thank You's Thanked, invalid Thankable provided");
				}
			}
		}
		$this->thanked = $thanked;
	}

	/**
	 * @param User[] $users
	 */
	public function SetUsers(array $users)
	{
		foreach ($users as $user)
		{
			if (!($user instanceof User))
			{
				throw new InvalidArgumentException("Failed to Set Thank You's Users, invalid User provided");
			}
		}

		$this->users = $users;
	}

	/**
	 * @param User $author
	 */
	private function SetAuthor(User $author)
	{
		$this->author = $author;
	}

	/**
	 * @param Date $date_created
	 */
	private function SetDateCreated(Date $date_created)
	{
		$this->date_created = $date_created;
	}
}
