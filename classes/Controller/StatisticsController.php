<?php

namespace Claromentis\ThankYou\Controller;

use Claromentis\Core\Config\Config;
use Claromentis\Core\Http\TemplaterCallResponse;
use Claromentis\Core\Localization\Lmsg;
use Claromentis\ThankYou\Api\Tag;

class StatisticsController
{
	private $config;

	private $lmsg;

	private $tag_api;

	public function __construct(Lmsg $lmsg, Config $config, Tag $tag_api)
	{
		$this->config  = $config;
		$this->lmsg    = $lmsg;
		$this->tag_api = $tag_api;
	}

	public function Statistics()
	{
		$core_values_enabled = (bool) $this->config->Get('thankyou_core_values_enabled');

		$args = ['nav_statistics.+class' => 'active'];

		if ($core_values_enabled)
		{
			$tag_options = [['tag_option.body' => ($this->lmsg)('thankyou.tag.all'), 'tag_option.value' => null, 'tag_option.selected' => "selected"]];

			$tags = $this->tag_api->GetTags(null, null, null, [['column' => 'name']]);

			foreach ($tags as $tag)
			{
				$tag_options[] = ['tag_option.value' => $tag->GetId(), 'tag_option.body' => $tag->GetName()];
			}
			$args['dt_form.args']['tags.datasrc'] = $tag_options;
		} else
		{
			$args['dt_form.args']['tags.visible'] = 0;
		}

		return new TemplaterCallResponse('thankyou/admin/statistics/statistics.html', $args, ($this->lmsg)('thankyou.app_name'));
	}
}
