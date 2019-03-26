<?php
namespace Claromentis\ThankYou\Controller;

use Claromentis\Core\Application;
use Claromentis\Core\Http\TemplaterCallResponse;
use Exception;
use Psr\Http\Message\ServerRequestInterface;

/**
 * The admin panel controller.
 */
class AdminNotificationsController
{
	/**
	 * Show the export admin panel.
	 *
	 * @param Application $app
	 *
	 * @return TemplaterCallResponse
	 * @throws Exception
	 */
	public function ShowNotificationsPanel(Application $app)
	{
		$config = $app['thankyou.config'];
		$notify_line_manager = $config->Get('notify_line_manager');

		$args = [
			'nav_notifications.+class' => 'active',
			'notify_line_manager.checked' => $notify_line_manager ? 'checked' : '',
		];

		return new TemplaterCallResponse('thankyou/admin/notifications.html', $args, lmsg('thankyou.app_name'));
	}

	/**
	 * Set notifications panel config.
	 *
	 * @param Application $app
	 * @param ServerRequestInterface $request
	 *
	 * @return TemplaterCallResponse
	 * @throws Exception
	 */
	public function SubmitNotificationsConfig(Application $app, ServerRequestInterface $request)
	{
		$notify_line_manager = isset($request->getParsedBody()['notify_line_manager']);

		$config = $app['thankyou.config'];
		$config->Set('notify_line_manager', $notify_line_manager);
		$config->Save();

		return $this->ShowNotificationsPanel($app);
	}
}