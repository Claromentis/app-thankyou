<?php

namespace Claromentis\ThankYou\Tags\UI;

use Claromentis\Core\Application;
use Claromentis\Core\Templater\Plugin\TemplaterComponentTmpl;
use Claromentis\ThankYou\Tags\Tag;
use Claromentis\ThankYou\Tags\Exceptions\TagNotFound;
use Psr\Log\LoggerInterface;

class TagTemplaterComponent extends TemplaterComponentTmpl
{
	private $api;

	private $log;

	public function __construct(\Claromentis\ThankYou\Api\Tag $tag, LoggerInterface $logger)
	{
		$this->api = $tag;
		$this->log = $logger;
	}

	public function Show($attributes, Application $app)
	{
		$tag = $attributes['tag'] ?? null;

		$args = [];

		if (!isset($tag))
		{
			$this->log->warning("Call to Tag Templater Component with a Tag defined");

			return $this->CallTemplater('thankyou/tag_template.html', ['tag.visible' => 0]);
		}

		if (is_numeric($tag))
		{
			$id = (int) $tag;

			try
			{
				$tag = $this->api->GetTag($id);
			} catch (TagNotFound $exception)
			{
				$this->log->warning("Call to Tag Templater Component for Tag with ID '" . $id . "' which could not be found", [$exception]);

				return $this->CallTemplater('thankyou/tag_template.html', ['tag.visible' => 0]);
			}
		}

		if (!($tag instanceof Tag))
		{
			$this->log->warning("Call to Tag Template Component for Tag with non Tag object provided");

			return $this->CallTemplater('thankyou/tag_template.html', ['tag.visible' => 0]);
		}

		$args['tag.body'] = $tag->GetName();

		$bg_colour = $tag->GetBackgroundColour();

		if (isset($bg_colour))
		{
			$args['tag.color'] = $bg_colour;
		}

		return $this->CallTemplater('thankyou/tag_template.html', $args);
	}
}
