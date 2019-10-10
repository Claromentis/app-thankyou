<?php

namespace Claromentis\ThankYou\Comments;

use ClaAggregation;
use Claromentis\Comments\CommentableInterface;
use Claromentis\Comments\CommentLocationInterface;
use Claromentis\Comments\Model\Comment;
use Claromentis\Comments\Notification\Notification;
use Claromentis\Comments\SupportedOptions;
use Claromentis\Core\Localization\Lmsg;
use Claromentis\Core\Security\SecurityContext;
use Claromentis\ThankYou\ThanksItem;
use LogicException;

class CommentableThankYou implements CommentableInterface, CommentLocationInterface
{
	private $thanks_item;

	/**
	 * Load an object by ID and optional extra information.
	 *
	 * @param int   $id
	 * @param array $extra
	 * @return mixed
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
	 * Returns enabled/supported options for this object. Returned value is an
	 * array with keys as SupportedOptions::... constants and boolean true/false
	 * values.
	 *
	 * @return array
	 */
	public function GetOptions()
	{
		return [
			SupportedOptions::REPLIES     => true,
			SupportedOptions::LIKES       => true,
			SupportedOptions::ATTACHMENTS => true,
		];
	}

	/**
	 * Check whether a user has VIEW/ADD/EDIT/DELETE permissions.
	 *
	 * If the $comment parameter is given, permissions are checked for that
	 * particular comment (for example, to allow the author to edit own
	 * comment). If it's not given, permissions check is done for all comments
	 * for this commentable object.
	 *
	 * Note, permissions \Claromentis\Comments\Rights::PERM_EDIT and
	 * \Claromentis\Comments\Rights::PERM_DELETE without the comment object mean
	 * that the user has these permissions for _all_ comments for that object
	 * (moderator access).
	 *
	 * @param SecurityContext $context
	 * @param int             $perms   Permission constant - \Claromentis\Comments\Rights::PERM_VIEW, ...::PERM_EDIT, ...::PERM_ADD, ...::PERM_DELETE
	 * @param Comment         $comment Optional comment object
	 * @return bool
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
	 * This method should send the required notifications, if any.
	 *
	 * Provided Notification object is pre-configured with the default
	 * notification and can be used directly by calling
	 * $default_notification->Send() or can be modified before doing this.
	 *
	 * It's also totally fine to not use the default notification at all, but
	 * send a custom one. The default notification will not be sent
	 * automatically.
	 *
	 * @param Comment      $comment
	 * @param Notification $default_notification
	 */
	public function Notify(Comment $comment, Notification $default_notification)
	{
		// TODO: Implement Notify() method.
	}

	/**
	 * Get breadcrumb data for a given object of this aggregation (sits on a class alongside CommentableInterface)
	 *
	 * Return data should be an array of individual breadcrumbs, each an array with name and url parts -
	 *
	 * return [
	 *      [
	 *          'name' => 'Application name',
	 *          'url' => create_internal_link('/app_name')  // i.e. 'knowledgebase'
	 *      ],
	 *      [
	 *          'name' => 'Object type',
	 *          'url' => create_internal_link('/app_name/object') // i.e. 'knowledgebase/articles'
	 *      ],
	 *      [
	 *          'name' => 'Object name',
	 *          'url' => create_internal_link('/app_name/object/$object_id') // i.e. 'knowledgebase/articles/1'
	 *      ]
	 * ]
	 *
	 * @param int $object_id
	 *
	 * @return array
	 */
	public function GetBreadcrumbs(int $object_id)
	{
		// TODO: Implement GetBreadcrumbs() method.
	}
}
