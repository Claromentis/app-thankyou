<?php

namespace Claromentis\ThankYou\Comments;

use ClaAggregation;
use Claromentis\Comments\CommentableInterface;
use Claromentis\Comments\CommentLocationInterface;
use Claromentis\Comments\Model\Comment;
use Claromentis\Comments\Notification\Notification;
use Claromentis\Comments\Rights;
use Claromentis\Comments\SupportedOptions;
use Claromentis\Core\Http\HttpUtil;
use Claromentis\Core\Localization\Lmsg;
use Claromentis\Core\Security\SecurityContext;
use Claromentis\Core\Services;
use Claromentis\ThankYou\Api;
use Claromentis\ThankYou\Exception\ThankYouNotFound;
use Claromentis\ThankYou\Exception\ThankYouOClass;
use Claromentis\ThankYou\ThanksItem;
use LogicException;
use Psr\Log\LoggerInterface;

class CommentableThankYou implements CommentableInterface, CommentLocationInterface
{
	/**
	 * @var LoggerInterface $log
	 */
	private $log;

	/**
	 * @var ThanksItem|null $thanks_item
	 */
	private $thanks_item;

	/**
	 * @var int|null $total_comments
	 */
	private $total_comments;

	public function __construct()
	{
		$this->log = Services::I()->GetLogger('comments');
	}

	/**
	 * @return int|null
	 */
	public function GetTotalComments(): ?int
	{
		return $this->total_comments;
	}

	/**
	 * {@inheritDoc}
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
	public function GetBreadcrumbs(int $object_id): array
	{
		$lmsg = Services::I()->{Lmsg::class};
		/**
		 * @var HttpUtil $http_util
		 */
		$http_util = Services::I()->{HttpUtil::class};

		return [
			[
				'name' => $lmsg('thankyou.app_name'),
				'url'  => $http_util->CreateInternalLink('/thankyou/thanks')
			],
			[
				'name' => $lmsg('thankyou.app_name'),
				'url'  => $http_util->CreateInternalLink('/thankyou/thanks/' . $object_id)
			]
		];
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
	 * @param int|null $total_comments
	 */
	public function SetTotalComments(?int $total_comments)
	{
		$this->total_comments = $total_comments;
	}

	/**
	 * {@inheritDoc}
	 */
	public function Load($id, $extra = [])
	{
		$this->thanks_item = new ThanksItem();
		$this->thanks_item->SetId((int) $id);
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
				$this->log->error("Invalid argument '" . (string) $perms . "' given for 2nd argument of UserHasPermission");

				return false;
				break;
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function Notify(Comment $comment, Notification $default_notification)
	{
		if (!isset($this->thanks_item))
		{
			$this->log->error("Failed to send Notifications for Thank You Comment, Thank You Item has not been loaded");

			return;
		}

		/**
		 * @var Api $api
		 */
		$api = Services::I()->{Api::class};

		try
		{
			$thank_you = $api->ThankYous()->GetThankYous($this->thanks_item->GetId(), false, true);
		} catch (ThankYouNotFound | ThankYouOClass $exception)
		{
			$this->log->error("Unexpected Exception thrown by Thank You API Endpoint 'GetThankYous'", [$exception]);

			return;
		}

		$thanked_users = $thank_you->GetUsers();

		if (isset($thanked_users))
		{
			$thanked_users_ids = [];
			foreach ($thanked_users as $user)
			{
				$thanked_users_ids[] = $user->GetId();
			}

			$default_notification->AddRecipients($thanked_users_ids);
		}

		$default_notification->Send();
	}
}
