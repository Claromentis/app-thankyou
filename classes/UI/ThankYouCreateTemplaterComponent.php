<?php

namespace Claromentis\ThankYou\UI;

use Claromentis\Core\Application;
use Claromentis\Core\Templater\Plugin\TemplaterComponentTmpl;
use Claromentis\ThankYou\Configuration;
use Claromentis\ThankYou\Thankable\Thankable;
use Psr\Log\LoggerInterface;

class ThankYouCreateTemplaterComponent extends TemplaterComponentTmpl
{
	/**
	 * @var Configuration\Api
	 */
	private $config_api;

	/**
	 * @var LoggerInterface
	 */
	private $logger;

	public function __construct(Configuration\Api $config_api, LoggerInterface $logger)
	{
		$this->config_api = $config_api;
		$this->logger     = $logger;
	}

	public function Show($attributes, Application $app)
	{
		$thankables = $attributes['thankables'] ?? null;
		$form       = (bool) ($attributes['form'] ?? true);

		$args = ['thank_you_form.visible' => $form];

		$class                                       = uniqid();
		$args['create_container.+class']             = $class;
		$args['class.json']                          = $class;
		$args['thank_you_form_tags_segment.visible'] = $this->config_api->IsTagsEnabled();

		if (isset($thankables))
		{
			if ($thankables instanceof Thankable)
			{
				$thankables = [$thankables];
			}

			if (is_array($thankables))
			{
				$preselected_thankables = [];
				foreach ($thankables as $thankable)
				{
					if ($thankable instanceof Thankable)
					{
						$preselected_thankables[] = [
							'id'          => $thankable->GetItemId(),
							'name'        => $thankable->GetName(),
							'object_type' => [
								'id'   => $thankable->GetOwnerClass(),
								'name' => $thankable->GetOwnerClassName() ?? ''
							]
						];
					} else
					{
						$this->logger->error("Value in Templater Component 'thankyou.create' attribute 'thankables' is not a Thankable");
					}
				}

				$args['thank_you_create_button.data-preselected_thanked'] = json_encode($preselected_thankables);
			} else
			{
				$this->logger->error("Value given to Templater Component 'thankyou.create' attribute 'thankables' was not an array");
			}
		}

		return $this->CallTemplater('thankyou/UI/thank_you_create_templater_component.html', $args);
	}
}
