<?php

namespace Claromentis\ThankYou\Controllers;

use Claromentis\Core\Http\ResponseFactory;
use Claromentis\Core\Http\TemplaterCallResponse;
use Claromentis\Core\Localization\Lmsg;
use Claromentis\ThankYou\Configuration;
use Claromentis\ThankYou\Tags;
use Psr\Http\Message\ServerRequestInterface;

class StatisticsController
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
	 * @var ResponseFactory
	 */
	private $response;

	/**
	 * @var Tags\Api
	 */
	private $tag_api;

	public function __construct(ResponseFactory $response_factory, Lmsg $lmsg, Tags\Api $tag_api, Configuration\Api $config_api)
	{
		$this->config_api = $config_api;
		$this->lmsg       = $lmsg;
		$this->response   = $response_factory;
		$this->tag_api    = $tag_api;
	}

	public function Reports(ServerRequestInterface $request)
	{
		$url = $request->getUri()->getPath();

		$args = ['nav_statistics.+class' => 'active'];

		foreach ($this->GetReports() as $report_index => $report)
		{
			$args['reports.datasrc'][] = ['report.body' => $report['name'], 'reportlink.href' => $url . '/statistics/' . $report_index];
		}

		return new TemplaterCallResponse('thankyou/admin/statistics/reports.html', $args, ($this->lmsg)('thankyou.app_name'));
	}

	public function View(string $report_index, ServerRequestInterface $request)
	{
		$args = ['nav_statistics.+class' => 'active'];

		if ($this->config_api->IsTagsEnabled())
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
		$reports = [
			'thankyous' => ['name' => ($this->lmsg)('thankyou.common.thank_yous'), 'datatable_service' => 'thankyou.datatable.thank_yous'],
			'users'     => ['name' => ($this->lmsg)('common.users'), 'datatable_service' => 'thankyou.datatable.users']
		];

		if ($this->config_api->IsTagsEnabled())
		{
			$reports ['tags'] = ['name' => ($this->lmsg)('thankyou.common.tags'), 'datatable_service' => 'thankyou.datatable.statistics.tags'];
		}

		return $reports;
	}
}
