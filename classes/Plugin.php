<?php
namespace Claromentis\ThankYou;

use Claromentis\Core\Aggregation\AggregationFilterEvent;
use Claromentis\Core\Application;
use Claromentis\Core\ControllerCollection;
use Claromentis\Core\REST\RestServiceInterface;
use Claromentis\Core\Templater\Plugin\TemplaterComponent;
use Claromentis\ThankYou\Controller\Rest\ThanksController;
use Claromentis\ThankYou\View\ThanksListView;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Api\BootableProviderInterface;
use Silex\Api\EventListenerProviderInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 *
 * @author Alexander Polyanskikh
 */
class Plugin implements
	TemplaterComponent,
	ServiceProviderInterface,
	RestServiceInterface,
	BootableProviderInterface,
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
		$app['thankyou.thanks_rest_controller'] = function ($app) {
			return new ThanksController($app['thankyou.repository']);
		};

		$app['thankyou.repository'] = function ($app) {
			return new ThanksRepository($app->db);
		};

		$app['thankyou.thanks_list_view'] = function () {
			return new ThanksListView();
		};

		// pages component
		$app['pages.component.thankyou'] = function () {
			return new UI\PagesComponent();
		};

		$app['localization.domain.thankyou'] = function ($app) {
			return $app['localization.domain_from_files_factory']('thankyou');
		};
	}

	/**
	 * Bootstraps the application.
	 *
	 * This method is called after all services are registered
	 * and should be used for "dynamic" configuration (whenever
	 * a service must be requested).
	 *
	 * @param \Silex\Application $app
	 */
	public function boot(\Silex\Application $app)
	{
		/**
		 * @var Application $app
		 */
		$app->registerRestService($this);
	}

	/**
	 * Register the module's aggregation via an aggregation filter event.
	 *
	 * @param AggregationFilterEvent $event
	 */
	public function RegisterAggregation(AggregationFilterEvent $event)
	{
		$event->GetConfig()->AddAggregation(
			ThanksItem::OBJECT_TYPE,
			'thanks',
			__('Thank you note'),
			__('Thank you notes')
		);
	}

	/**
	 * Register the module's event listeners.
	 *
	 * @param Container $app
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
	 * @param string $attr
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
				$count = $repository->GetCount($attr['user_id']);
				return '<li><a href="#thanks"><span class="cla-icon-thumbs-up"></span> '.lmsg("thankyou.user_profile.tab_name").' (<b>'.$count.'</b>)</a></li>';
			case 'viewprofile.tab_content':
				if (empty($attr['user_id']) || !is_numeric($attr['user_id']))
					return '';
				$component = new UI\Wall();
				$component_data = $component->Show(array('user_id' => $attr['user_id']), $app);
				return '<div id="thanks">' . $component_data . '</div>';
		}
		return '';
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
				$routes->get('/thanks/{id}', 'thankyou.thanks_rest_controller:GetThanksItem')->assert('id', '\d+');
			}
		];
	}
}
