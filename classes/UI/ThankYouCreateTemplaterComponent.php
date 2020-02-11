<?php

namespace Claromentis\ThankYou\UI;

use Claromentis\Core\Application;
use Claromentis\Core\Localization\Lmsg;
use Claromentis\Core\Templater\Plugin\TemplaterComponentTmpl;
use Claromentis\ThankYou\Configuration;
use Claromentis\ThankYou\Thanked\Thanked;
use Claromentis\ThankYou\ThankYous\Validator;
use Psr\Log\LoggerInterface;

class ThankYouCreateTemplaterComponent extends TemplaterComponentTmpl
{
	/**
	 * @var Configuration\Api
	 */
	private $config_api;

	/**
	 * @var Lmsg
	 */
	private $lmsg;

	/**
	 * @var LoggerInterface
	 */
	private $logger;

	public function __construct(Configuration\Api $config_api, Lmsg $lmsg, LoggerInterface $logger)
	{
		$this->config_api = $config_api;
		$this->lmsg       = $lmsg;
		$this->logger     = $logger;
	}

	public function Show($attributes, Application $app)
	{
		$thanked = $attributes['thankeds'] ?? null;
		$form    = (bool) ($attributes['form'] ?? true);

		$args = ['thank_you_form.visible' => $form];

		$class                                        = uniqid();
		$args['create_container.+class']              = $class;
		$args['class.json']                           = $class;
		$args['thank_you_form_tags_segment.visible']  = $this->config_api->IsTagsEnabled();
		$args['thankyou_form_tags_mandatory.visible'] = $this->config_api->IsTagsMandatory();
		$args['thank_you_user.placeholder']           = ($this->lmsg)('thankyou.thank.placeholder');
		$args['thank_you_description.placeholder']    = ($this->lmsg)('thankyou.common.add_description');
		$args['description_max_length.json']          = Validator::DESCRIPTION_MAX_CHARACTERS;

		if (isset($thanked))
		{
			if ($thanked instanceof Thanked)
			{
				$thanked = [$thanked];
			}

			if (is_array($thanked))
			{
				$preselected_thanked = [];
				foreach ($thanked as $a_thanked)
				{
					if ($a_thanked instanceof Thanked)
					{
						$preselected_thanked[] = [
							'id'          => $a_thanked->GetItemId(),
							'name'        => $a_thanked->GetName(),
							'object_type' => [
								'id'   => $a_thanked->GetOwnerClass(),
								'name' => $a_thanked->GetOwnerClassName() ?? ''
							]
						];
					} else
					{
						$this->logger->error("Value in Templater Component 'thankyou.create' attribute 'thankeds' is not a Thanked");
					}
				}

				$args['thank_you_create_button.data-preselected_thanked'] = json_encode($preselected_thanked);
			} else
			{
				$this->logger->error("Value given to Templater Component 'thankyou.create' attribute 'thankeds' was not an array");
			}
		}

		return $this->CallTemplater('thankyou/UI/thank_you_create_templater_component.html', $args);
	}
}
