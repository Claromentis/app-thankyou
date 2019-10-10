<?php

namespace Claromentis\ThankYou\Subscriber;

use Claromentis\Comments\CommentableFilterEvent;
use Claromentis\Core\Aggregation\AggregationFilterEvent;
use Claromentis\Core\Localization\Lmsg;
use Claromentis\ThankYou\Comments\CommentableThankYou;
use Claromentis\ThankYou\ThanksItem;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CommentsSubscriber implements EventSubscriberInterface
{
	/**
	 * @var Lmsg
	 */
	private $lmsg;

	public function __construct(Lmsg $lmsg)
	{
		$this->lmsg = $lmsg;
	}

	/**
	 * {@inheritDoc}
	 */
	public static function getSubscribedEvents()
	{
		return [
			'core.aggregations_filter' => ['RegisterAggregation'],
			'core.commentable_filter'  => ['RegisterCommentableObjects']
		];
	}

	/**
	 * Register Thank You's Aggregation.
	 *
	 * @param AggregationFilterEvent $event
	 */
	public function RegisterAggregation(AggregationFilterEvent $event)
	{
		$event->GetConfig()->AddAggregation(
			ThanksItem::AGGREGATION,
			'thanks',
			($this->lmsg)('thankyou.common.thank_you_message'),
			($this->lmsg)('thankyou.common.thank_you_messages')
		);
	}

	/**
	 * Register commentable object type CommentableThankYou
	 *
	 * @param CommentableFilterEvent $event
	 */
	public function RegisterCommentableObjects(CommentableFilterEvent $event)
	{
		$factory = $event->GetFactory();
		$factory->AddCommentableInterface(ThanksItem::AGGREGATION, CommentableThankYou::class);
	}
}
