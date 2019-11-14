<?php

namespace Claromentis\ThankYou\UI;

use Claromentis\Core\Application;
use Claromentis\Core\Templater\Plugin\TemplaterComponentTmpl;
use Claromentis\ThankYou\ThankYous\Thankable;
use Psr\Log\LoggerInterface;

class ThankYouCreateTemplaterComponent extends TemplaterComponentTmpl
{
	private $log;

	public function __construct(LoggerInterface $logger)
	{
		$this->log = $logger;
	}

	public function Show($attributes, Application $app)
	{
		$thankables = $attributes['thankables'] ?? null;
		$form       = (bool) ($attributes['form'] ?? true);

		$args = ['thank_you_form.visible' => $form];

		$class                           = uniqid();
		$args['create_container.+class'] = $class;
		$args['class.json']              = $class;

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
							'id'          => $thankable->GetId(),
							'name'        => $thankable->GetName(),
							'object_type' => [
								'id'   => $thankable->GetOwnerClass(),
								'name' => $thankable->GetOwnerClassName() ?? ''
							]
						];
					} else
					{
						$this->log->error("Value in Templater Component 'thankyou.create' attribute 'thankables' is not a Thankable");
					}
				}

				$args['thank_you_create_button.data-preselected_thanked'] = json_encode($preselected_thankables);
			} else
			{
				$this->log->error("Value given to Templater Component 'thankyou.create' attribute 'thankables' was not an array");
			}
		}

		return $this->CallTemplater('thankyou/thankyou.create.html', $args);
	}
}
