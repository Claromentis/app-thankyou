<?php

namespace Claromentis\ThankYou\Comments;

use ClaAggregation;
use Claromentis\Comments\CommentableInterface;
use Claromentis\Comments\CommentLocationInterface;
use Claromentis\Comments\Model\Comment;
use Claromentis\Comments\Notification\Notification;
use Claromentis\Comments\SupportedOptions;
use Claromentis\Core\Security\SecurityContext;
use Claromentis\ThankYou\ThanksItem;
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
	 * {@inheritDoc}
	 */
	public function UserHasPermission(SecurityContext $context, $perms, Comment $comment = null): bool
	{
		// TODO: Implement UserHasPermission() method.
	//	if ($perms === PERM_VIEW && $comment === null)
	//	{
			return true;
	//	}
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
