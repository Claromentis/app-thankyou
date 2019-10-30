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
use Claromentis\ThankYou\Exception\ThankYouInvalidThankable;
use Claromentis\ThankYou\Exception\ThankYouNotFound;
use Claromentis\ThankYou\Exception\ThankYouRuntimeException;
use Claromentis\ThankYou\ThanksItem;
use InvalidArgumentException;
use LogicException;

class CommentableThankYou implements CommentableInterface, CommentLocationInterface
{
	/**
	 * @var ThanksItem|null $thanks_item
	 */
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
	 *
	 * @throws ThankYouRuntimeException
	 * @throws LogicException
	 */
	public function Notify(Comment $comment, Notification $default_notification)
	{
		if (!isset($this->thanks_item))
		{
			throw new ThankYouRuntimeException("Failed to send Notifications for Thank You Comment, Thank You Item has not been loaded");
		}

		/**
		 * @var Api $api
		 */
		$api = Services::I()->{Api::class};

		try
		{
			$thank_you = $api->ThankYous()->GetThankYous($this->thanks_item->GetId(), false, true);
		} catch (ThankYouInvalidThankable | ThankYouNotFound $exception)
		{
			throw new LogicException("Unexpected Exception thrown by Thank You API Endpoint 'GetThankYous'", null, $exception);
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
}
