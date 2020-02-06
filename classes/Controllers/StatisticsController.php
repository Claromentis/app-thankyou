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
			if ($report['is_enabled'])
			{
				$args['reports.datasrc'][] = ['report.body' => $report['name'], 'reportlink.href' => $url . '/statistics/' . $report_index];
			}
		}

		return new TemplaterCallResponse('thankyou/admin/statistics/reports.html', $args, ($this->lmsg)('thankyou.app_name'));
	}

	public function View(string $report_index, ServerRequestInterface $request)
	{
		$args = ['nav_statistics.+class' => 'active'];

		if ($this->config_api->IsTagsEnabled())
		{
			$tag_options = [['tag_option.body' => ($this->lmsg)('thankyou.tag.all'), 'tag_option.value' => null, 'tag_option.selected' => "selected"]];

			$tags = $this->tag_api->GetTags(null, null, null, null, [['column' => 'name']]);

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

		if (!$report) // requested report doesn't exist at all
		{
			return $this->response->GetRedirectResponse(substr($request->getRequestTarget(), 0, -strlen($report_index)));
		}

		if (!$report['is_enabled']) // requested report exists but is not enabled
		{
			$args['current_page_title.body']    = ($this->lmsg)('thankyou.admin.reports.disabled.heading');
			$args['report_disabled_error.body'] = ($this->lmsg)('thankyou.admin.reports.disabled.generic');
			if ($this->lmsg->lmsg_key_exist('thankyou.admin.reports.disabled.' . $report_index))
			{
				$args['report_disabled_error.body'] = ($this->lmsg)('thankyou.admin.reports.disabled.' . $report_index);
			}
			return new TemplaterCallResponse('thankyou/admin/statistics/report_disabled.html', $args, ($this->lmsg)('thankyou.app_name'));
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
		return [
			'thankyous' => [
				'name'              => ($this->lmsg)('thankyou.common.thank_yous'),
				'datatable_service' => 'thankyou.datatable.thank_yous',
				'is_enabled'        => true
			],
			'users'     => [
				'name'              => ($this->lmsg)('common.users'),
				'datatable_service' => 'thankyou.datatable.users',
				'is_enabled'        => true
			],
			'tags'      => [
				'name'              => ($this->lmsg)('thankyou.common.tags'),
				'datatable_service' => 'thankyou.datatable.statistics.tags',
				'is_enabled'        => $this->config_api->IsTagsEnabled()
			]
		];
	}
}
