<?php
namespace Claromentis\ThankYou\Controller;

use Claromentis\Core\Application;
use Claromentis\Core\Http\gpc;
use Claromentis\Core\Http\TemplaterCallResponse;
use Claromentis\ThankYou\ThanksRepository;
use Claromentis\ThankYou\View\ThanksListView;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Controller for messages list in admin area
 */
class AdminMessagesController
{
	const PAGE_SIZE = 20;

	/**
	 * Show the messages admin panel.
	 *
	 * @param Application $app
	 * @param ServerRequestInterface $request
	 *
	 * @return TemplaterCallResponse
	 */
	public function Show(Application $app, ServerRequestInterface $request)
	{
		$args = [
			'nav_messages.+class' => 'active',
		];

		/** @var ThanksRepository $repository */
		$repository = $app['thankyou.repository'];

		$st = (int)gpc::get($request, 'st');
		$thanks = $repository->GetRecent(self::PAGE_SIZE, $st);

		require_once('paging.php');
		$args['paging.body_html'] = get_navigation(gpc::getRequestPath($request), $repository->GetCount(), $st, '', self::PAGE_SIZE);

		/**
		 * @var ThanksListView $view
		 */
		$view = $app[ThanksListView::class];
		$args['items.datasrc'] = $view->Show($thanks, ['admin' => true], $app->security);

		$args['no_thanks.body'] = lmsg('thankyou.component.no_thanks_all');

		return new TemplaterCallResponse('thankyou/admin/admin.html', $args, lmsg('thankyou.app_name'));
	}

}
