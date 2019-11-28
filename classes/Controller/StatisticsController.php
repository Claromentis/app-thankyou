<?php

namespace Claromentis\ThankYou\Controller;

use Claromentis\Core\Config\Config;
use Claromentis\Core\Http\ResponseFactory;
use Claromentis\Core\Http\TemplaterCallResponse;
use Claromentis\Core\Localization\Lmsg;
use Claromentis\ThankYou\Api\Tag;
use Psr\Http\Message\ServerRequestInterface;

class StatisticsController
{
	private $config;

	private $lmsg;

	private $response;

	private $tag_api;

	public function __construct(ResponseFactory $response_factory, Lmsg $lmsg, Config $config, Tag $tag_api)
	{
		$this->config   = $config;
		$this->lmsg     = $lmsg;
		$this->response = $response_factory;
		$this->tag_api  = $tag_api;
	}

	public function Reports(ServerRequestInterface $request)
	{
		$url = $request->getRequestTarget();

		$args = ['nav_statistics.+class' => 'active'];

		foreach ($this->GetReports() as $report_index => $report)
		{
			$args['reports.datasrc'][] = ['report.body' => $report['name'], 'reportlink.href' => $url . '/' . $report_index];
		}

		return new TemplaterCallResponse('thankyou/admin/statistics/reports.html', $args, ($this->lmsg)('thankyou.app_name'));
	}

	public function View(string $report_index, ServerRequestInterface $request)
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
			$args['dt_form.args']['tags_filter_container.visible'] = 0;
		}

		$report            = $this->GetReports()[$report_index] ?? null;
		$datatable_service = $report['datatable_service'] ?? null;
		$report_name       = $report['name'] ?? '';

		if (!isset($datatable_service))
		{
			return $this->response->GetRedirectResponse(substr($request->getRequestTarget(), 0, -strlen($report_index)));
		}

		$args['thankyou_reports_datatable.service'] = $datatable_service;
		$args['current_page_title.body']            = $report_name;

		return new TemplaterCallResponse('thankyou/admin/statistics/report.html', $args, ($this->lmsg)('thankyou.app_name'));
	}

	/**
	 * @return array
	 */
	private function GetReports(): array
	{
		$core_values_enabled = (bool) $this->config->Get('thankyou_core_values_enabled');

		$reports = [
			'thankyous' => ['name' => ($this->lmsg)('thankyou.common.thank_yous'), 'datatable_service' => 'thankyou.datatable.thank_yous'],
			'users'     => ['name' => ($this->lmsg)('common.users'), 'datatable_service' => 'thankyou.datatable.users']
		];

		if ($core_values_enabled)
		{
			$reports ['tags'] = ['name' => ($this->lmsg)('thankyou.common.tags'), 'datatable_service' => 'thankyou.datatable.statistics.tags'];
		}

		return $reports;
	}
}
