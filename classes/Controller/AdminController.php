<?php
namespace Claromentis\ThankYou\Controller;

use Claromentis\Core\Application;
use Claromentis\Core\Http\FileResponse;
use Claromentis\Core\Http\TemplaterCallResponse;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * The admin panel controller.
 */
class AdminController
{
	/**
	 * Show the admin panel.
	 *
	 * @param Application $app
	 * @param Request $request
	 * @return TemplaterCallResponse
	 */
	public function ShowNotesPanel(Application $app, Request $request)
	{
		$arguments = [];

		return new TemplaterCallResponse('thankyou/admin/admin.html', $arguments, lmsg('thankyou.app_name'));
	}

	/**
	 * Export the thank you notes in the given date range as a CSV file.
	 *
	 * @param Application $app
	 * @param Request $request
	 * @return FileResponse
	 */
	public function ExportCsv(Application $app, Request $request)
	{
		// TODO: Implement

		//return new FileResponse();
	}
}