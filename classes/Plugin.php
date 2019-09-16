<?php
namespace Claromentis\ThankYou;

use Claromentis\Core\Admin\PanelsList;
use Claromentis\Core\Aggregation\AggregationFilterEvent;
use Claromentis\Core\Application;
use Claromentis\Core\ControllerCollection;
use Claromentis\Core\REST\RestServiceInterface;
use Claromentis\Core\RouteProviderInterface;
use Claromentis\Core\Templater\Plugin\TemplaterComponent;
use Claromentis\ThankYou\Controller\AdminExportController;
use Claromentis\ThankYou\Controller\AdminMessagesController;
use Claromentis\ThankYou\Controller\AdminNotificationsController;
use Claromentis\ThankYou\Controller\Rest\ThanksRestController;
use Claromentis\ThankYou\Controller\Rest\ThanksRestV2;
use Claromentis\ThankYou\Controller\ThanksController;
use Claromentis\ThankYou\UI\Say;
use Claromentis\ThankYou\View\ThanksListView;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Api\EventListenerProviderInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 *
 * @author Alexander Polyanskikh
 */
class Plugin implements
	TemplaterComponent,
	ServiceProviderInterface,
	RouteProviderInterface,
	RestServiceInterface,
	EventListenerProviderInterface
{
	/**
	 * Registers services on the given container.
	 *
	 * This method should only be used to configure services and parameters.
	 * It should not get services.
	 *
	 * @param Container $app A container instance
	 */
	public function register(Container $app)
	{
		// Localization domain
		$app['localization.domain.thankyou'] = function ($app) {
			return $app['localization.domain_from_files_factory']('thankyou');
		};

		// Admin panel
		$app['admin.panels'] = $app->extend('admin.panels', function ($panels, $app) {
			$panels['thankyou'] = [
				'name'      => $app['lmsg']('thankyou.app_name'),
				'css_class' => 'glyphicons-donate',
				'url'       => '/thankyou/admin/'];

			return $panels;
		});

		// Templater component class key
		$app['templater.ui.thankyou'] = function () {
			return new Say();
		};

		// Controllers
		$app['thankyou.admin_messages_controller'] = function () {
			return new AdminMessagesController();
		};

		$app['thankyou.admin_export_controller'] = function () {
			return new AdminExportController();
		};

		$app['thankyou.admin_notifications_controller'] = function () {
			return new AdminNotificationsController();
		};

		$app['thankyou.rest_controller'] = function ($app) {
			return new ThanksRestController($app['thankyou.repository']);
		};

		// Notification
		/*$app['thankyou.line_manager_notifier'] = function () {
			return new LineManagerNotifier();
		};*/

		// Repositories
		$app['thankyou.repository'] = function ($app) {
			return new ThanksRepository($app->db);
		};

		$app['thankyou.thanks_list_view'] = function ($app) {
			/**
			 * @var PanelsList $panels ;
			 */
			$panels = $app['admin.panels_list'];

			return new ThanksListView($panels->GetOne('thankyou'));
		};

		// Pages component
		$app['pages.component.thankyou'] = function () {
			return new UI\PagesComponent();
		};

		$app['thankyou.config'] = function ($app) {
			return $app['config.factory']('thankyou');
		};

		$app[ThanksController::class] = function ($app) {
			return new ThanksController($app['lmsg'], $app[UseCase\ThankYou::class], $app['thankyou.config'], $app['admin.panels_list']->GetOne('thankyou'));
		};
	}

	/**
	 * Returns routes for the application.
	 *
	 * This method should return an array in form of array($prefix => $closure), where $prefix is a string starting
	 * from slash, without trailing slash and $closure is a function that takes an instance of
	 * \Claromentis\Core\ControllerCollection and registers all route handlers into it.
	 *
	 * Each route handler should be defined as a string - controller class and method name, such as
	 *   $routes->get('/home', "MyApp\MyAppMainControler::OnHome")
	 * or as a service name and method name:
	 *   $routes->get('/home', "myapp.controller.home:OnHome")  // note, only one colon
	 *
	 * Make sure to secure all routes either by defining default security such as $routes->secure('html', 'user');
	 * or for each route $routes->get(....)->secure('html', 'user');
	 * Note, default security works only for routes defined _after_ it's set, so put it to the top of the closure
	 *
	 * Example:
	 *  return array(
	 *     '/main' => function (\Claromentis\Core\ControllerCollection $routes)
	 *     {
	 *          $routes->secure('html', 'user'); // default security rule
	 *          $routes->get('/', '\Claromentis\Main\Controller\HomePageController::Show');
	 *          $routes->get('/whats_new', '\Claromentis\Main\Controller\WhatsNewController::Show')->secure('ajax');
	 *          $routes->get('/{item_id}', '\Claromentis\Main\Controller\ItemController::Show')->assert('item_id', '\d+');
	 *          $routes->get('/admin', ItemController::class.'::Show')->secure('html', 'admin', ['panel_code' => 'main'])
	 *     }
	 *  );
	 *
	 * @param Application $app An Application instance
	 *
	 * @return array
	 */
	public function GetRoutes(Application $app)
	{
		return [
			'/thankyou' => function (ControllerCollection $routes) use ($app) {
				$routes->secure('html', 'user');
				$routes->post('/thanks/create', ThanksController::class . ':CreateOrUpdate');
				$routes->post('/thanks/delete', ThanksController::class . ':Delete');

				$routes->secure('html', 'admin', ['panel_code' => 'thankyou']);
				$routes->get('/admin', 'thankyou.admin_messages_controller:Show');
				$routes->get('/admin/export', 'thankyou.admin_export_controller:ShowExportPanel');
				$routes->post('/admin/export', 'thankyou.admin_export_controller:ExportCsv');
				$routes->get('/admin/notifications', 'thankyou.admin_notifications_controller:ShowNotificationsPanel');
				$routes->post('/admin/notifications', 'thankyou.admin_notifications_controller:SubmitNotificationsConfig');
			}
		];
	}

	/**
	 * Returns REST routes for the application.
	 *
	 * @param Application $app An Application instance
	 * @return array
	 */
	public function GetRestRoutes(Application $app)
	{
		return [
			'/thankyou/v0' => '/thankyou/v1',
			'/thankyou/v1' => function (ControllerCollection $routes) {
				$routes->get('/thanks/{id}', 'thankyou.rest_controller:GetThanksItem')->assert('id', '\d+');
			}
		];
	}

	/**
	 * Register the module's aggregation via an aggregation filter event.
	 *
	 * @param AggregationFilterEvent $event
	 */
	public function RegisterAggregation(AggregationFilterEvent $event)
	{
		$event->GetConfig()->AddAggregation(
			ThanksItem::AGGREGATION,
			'thanks',
			lmsg('thankyou.common.thank_you_message'),
			lmsg('thankyou.common.thank_you_messages')
		);
	}

	/**
	 * Instant Message types
	 *
	 * @return array
	 */
	public function GetIMConfig()
	{
		return [
			"thankyou",
			lmsg("thankyou.communication.imessages"),
			[
				Constants::IM_TYPE_THANKYOU
			]
		];
	}

	/**
	 * Register the module's event listeners.
	 *
	 * @param Container                $app
	 * @param EventDispatcherInterface $dispatcher
	 */
	public function subscribe(Container $app, EventDispatcherInterface $dispatcher)
	{
		$dispatcher->addListener('core.aggregations_filter', [$this, 'RegisterAggregation']);
	}

	/**
	 * User profile hooks.
	 *
	 * Adds the thank you tab.
	 *
	 * @param array       $attr
	 * @param Application $app
	 * @return string
	 */
	public function Show($attr, Application $app)
	{
		switch ($attr['page'])
		{
			case 'viewprofile.tab_nav':
				if (empty($attr['user_id']) || !is_numeric($attr['user_id']))
					return '';
				/** @var ThanksRepository $repository */
				$repository = $app['thankyou.repository'];
				$count      = $repository->GetCountForUser($attr['user_id']);

				return '<li><a href="#thanks"><span class="glyphicons glyphicons-donate"></span> ' . $app['lmsg']("thankyou.user_profile.tab_name") . ' (<b>' . $count . '</b>)</a></li>';
			case 'viewprofile.tab_content':
				if (empty($attr['user_id']) || !is_numeric($attr['user_id']))
					return '';
				$component      = new UI\Wall();
				$component_data = $component->Show(['user_id' => $attr['user_id'], 'limit' => 20], $app);

				return '<div id="thanks">' . $component_data . '</div>';
		}

		return '';
	}
}
