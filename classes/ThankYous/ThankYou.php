<?php

namespace Claromentis\ThankYou\ThankYous;

use Claromentis\ThankYou\Tags\Tag;
use Date;
use InvalidArgumentException;
use User;

class ThankYou
{
	/**
	 * @var User $author
	 */
	private $author;

	/**
	 * @var Date $date_created
	 */
	private $date_created;

	/**
	 * @var string $description
	 */
	private $description;

	/**
	 * @var int|null $id
	 */
	private $id;

	/**
	 * @var Thankable[]|null $thanked
	 */
	private $thanked;

	/**
	 * @var User[]|null $users
	 */
	private $users;

	/**
	 * @var Tag[]|null $tag
	 */
	private $tags;

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
	 * @return Tag[]|null
	 */
	public function GetTags(): ?array
	{
		return $this->tags;
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
	public function SetUsers(?array $users)
	{
		if (is_array($users))
		{
			foreach ($users as $user)
			{
				if (!($user instanceof User))
				{
					throw new InvalidArgumentException("Failed to Set Thank You's Users, invalid User provided");
				}
			}
		}

		$this->users = $users;
	}

	/**
	 * @param Tag[]|null $tags
	 */
	public function SetTags(?array $tags)
	{
		if (is_array($tags))
		{
			foreach ($tags as $tag)
			{
				if (!($tag instanceof Tag))
				{
					throw new InvalidArgumentException("Failed to Set Thank You Tags, invalid Tag provided");
				}
			}
		}

		$this->tags = $tags;
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
