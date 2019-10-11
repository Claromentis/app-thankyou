<?php

namespace Claromentis\ThankYou\Comments;

use ClaAggregation;
use Claromentis\Comments\CommentableInterface;
use Claromentis\Comments\CommentLocationInterface;
use Claromentis\Comments\Model\Comment;
use Claromentis\Comments\Notification\Notification;
use Claromentis\Comments\Rights;
use Claromentis\Comments\SupportedOptions;
use Claromentis\Core\Security\SecurityContext;
use Claromentis\Core\Services;
use Claromentis\ThankYou\Api;
use Claromentis\ThankYou\ThanksItem;
use InvalidArgumentException;
use LogicException;

class CommentableThankYou implements CommentableInterface, CommentLocationInterface
{
	private $thanks_item;

	/**
	 * {@inheritDoc}
	 */
	public function Load($id, $extra = [])
	{
		$this->thanks_item = new ThanksItem();
		$this->thanks_item->SetId((int) $id);
	}

	/**
	 * {@inheritDoc}
	 * @throws LogicException
	 */
	public function GetAggregationObject(): ClaAggregation
	{
		if (!isset($this->thanks_item))
		{
			throw new LogicException("Could not Get Commentable Thank You's Aggregation Object, the object has not had a chance to be instantiated.");
		}

		return $this->thanks_item;
	}

	/**
	 * {@inheritDoc}
	 */
	public function GetOptions(): array
	{
		return [
			SupportedOptions::REPLIES     => true,
			SupportedOptions::LIKES       => true,
			SupportedOptions::ATTACHMENTS => true,
		];
	}

	/**
	 * #Permissions
	 * ##View
	 * Anyone may view any Comment.
	 * ##Add
	 * Anyone may add a Comment.
	 * ##Edit
	 * Only a Comment's Author may Edit it.
	 * ##Delete
	 * Only a Comment's Author or a Thank You Admin may Delete it.
	 *
	 * {@inheritDoc}
	 *
	 * @throws InvalidArgumentException
	 *
	 */
	public function UserHasPermission(SecurityContext $context, $perms, Comment $comment = null): bool
	{
		/**
		 * @var Api $api
		 */
		$api = Services::I()->{Api::class};
		switch ($perms)
		{
			case Rights::PERM_VIEW:
			case Rights::PERM_ADD:
				return true;
				break;
			case Rights::PERM_EDIT:
				if (!isset($comment))
				{
					return false;
				}
				$author_user_id = $comment->user_id;

				return (isset($author_user_id) && (int) $author_user_id === $context->GetUserId()) ? true : false;
				break;
			case Rights::PERM_DELETE:
				if (!isset($comment))
				{
					return false;
				}
				$author_user_id = $comment->user_id;
				if ((isset($author_user_id) && (int) $author_user_id === $context->GetUserId()) || $api->ThankYous()->IsAdmin($context))
				{
					return true;
				}

				return false;
				break;
			default:
				throw new InvalidArgumentException("Invalid argument '" . (string) $perms . "' given for 2nd argument of UserHasPermission");
				break;
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function Notify(Comment $comment, Notification $default_notification)
	{
		// TODO: Implement Notify() method.
	}

	/**
	 * {@inheritDoc}
	 */
	public function GetBreadcrumbs(int $object_id)
	{
		// TODO: Implement GetBreadcrumbs() method.
	}
}
