<?php

namespace Claromentis\ThankYou\ThankYous;

use Claromentis\ThankYou\Comments\CommentableThankYou;
use Claromentis\ThankYou\Tags\Tag;
use Claromentis\ThankYou\Thankable\Thankable;
use Date;
use InvalidArgumentException;
use User;

class ThankYou
{
	/**
	 * @var User
	 */
	private $author;

	/**
	 * @var CommentableThankYou|null
	 */
	private $comment;

	/**
	 * @var Date
	 */
	private $date_created;

	/**
	 * @var string
	 */
	private $description;

	/**
	 * @var int|null
	 */
	private $id;

	/**
	 * @var Thankable[]|null
	 */
	private $thanked;

	/**
	 * @var User[]|null
	 */
	private $users;

	/**
	 * @var Tag[]|null
	 */
	private $tags;

	/**
	 * ThankYou constructor.
	 *
	 * @param User   $author
	 * @param string $description
	 * @param Date   $date_created
	 */
	public function __construct(User $author, string $description, Date $date_created)
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
	 * @return CommentableThankYou|null
	 */
	public function GetComment(): ?CommentableThankYou
	{
		return $this->comment;
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
	public function GetThankables(): ?array
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
	 * @param CommentableThankYou|null $commentable_thank_you
	 */
	public function SetComment(?CommentableThankYou $commentable_thank_you)
	{
		$this->comment = $commentable_thank_you;
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
