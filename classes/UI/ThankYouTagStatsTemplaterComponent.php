<?php

namespace Claromentis\ThankYou\UI;

use Claromentis\Core\Application;
use Claromentis\Core\Security\SecurityContext;
use Claromentis\Core\Templater\Plugin\TemplaterComponentTmpl;
use Claromentis\ThankYou\Api;
use Claromentis\ThankYou\ThankYous\ThankYousRepository;
use Psr\Log\LoggerInterface;

/**
 * Class ThankYouTagStatsTemplaterComponent
 * Templater Component class_key="thankyou.tag_stats"
 */
class ThankYouTagStatsTemplaterComponent extends TemplaterComponentTmpl
{
	private $tag_api;

	private $thank_you_api;

	private $log;

	public function __construct(Api\ThankYous $thank_you, Api\Tag $tag, LoggerInterface $logger)
	{
		$this->tag_api       = $tag;
		$this->thank_you_api = $thank_you;
		$this->log           = $logger;
	}

	public function Show($attributes, Application $app)
	{
		/**
		 * @var SecurityContext $context
		 */
		$context = $app[SecurityContext::class];

		$args = ['tags.datasrc' => []];

		$order = ['column' => 'COUNT(' . ThankYousRepository::THANK_YOU_TAGS_TABLE . '.item_id)', 'desc' => true];

		$tags_thankyou_total_uses = $this->thank_you_api->GetTagsTotalThankYouUses($context, [$order]);

		$total_tags_uses = array_sum($tags_thankyou_total_uses);

		$tags = $this->tag_api->GetTagsById(array_keys($tags_thankyou_total_uses));

		foreach ($tags_thankyou_total_uses as $tag_id => $tag_thankyou_total_uses)
		{
			$args['tags.datasrc'][] = [
				'tag_name.body'                        => $tags[$tag_id]->GetName(),
				'tag_progress_bar.minimum'             => 0,
				'tag_progress_bar.maximum'             => $total_tags_uses,
				'tag_progress_bar.current'             => $tag_thankyou_total_uses,
				'tag_progress_bar.barcolour'           => $tags[$tag_id]->GetBackgroundColour(),
				'tag_progress_bar.data-original-title' => floor(100 * $tag_thankyou_total_uses / $total_tags_uses) . '%'
			];
		}

		return $this->CallTemplater('thankyou/UI/ThankYouTagStatsTemplaterComponent.html', $args);
	}
}
