<?php
namespace Claromentis\ThankYou;

use Claromentis\Core\Acl\PermOClass;
use Claromentis\Core\Application;
use Claromentis\Core\Component\TemplaterTrait;
use Claromentis\Core\ControllerCollection;
use Claromentis\Core\DAL\Interfaces\DbInterface;
use Claromentis\Core\DAL\QueryFactory;
use Claromentis\Core\Event\LazyResolver;
use Claromentis\Core\Http\ResponseFactory;
use Claromentis\Core\Localization\Lmsg;
use Claromentis\Core\REST\RestServiceInterface;
use Claromentis\Core\RouteProviderInterface;
use Claromentis\Core\Security\SecurityContext;
use Claromentis\Core\Services;
use Claromentis\Core\Templater\Plugin\TemplaterComponent;
use Claromentis\Core\TextUtil\ClaText;
use Claromentis\Core\Widget\Sugre\SugreUtility;
use Claromentis\People\Service\UserExtranetService;
use Claromentis\ThankYou\Configuration;
use Claromentis\ThankYou\Controllers\AdminController;
use Claromentis\ThankYou\Exception\ThankYouNotFoundException;
use Claromentis\ThankYou\Tags;
use Claromentis\ThankYou\ThankYous;
use Claromentis\ThankYou\Controllers\Rest\ThanksRestController;
use Claromentis\ThankYou\Controllers\Rest\ThanksRestV2;
use Claromentis\ThankYou\Controllers\StatisticsController;
use Claromentis\ThankYou\Controllers\ThankYouController;
use Claromentis\ThankYou\Exception\UnsupportedOwnerClassException;
use Claromentis\ThankYou\Subscriber\CommentsSubscriber;
use Claromentis\ThankYou\Tags\DataTables\TagDataTableSource;
use Claromentis\ThankYou\Tags\Format\TagFormatter;
use Claromentis\ThankYou\Tags\TagAcl;
use Claromentis\ThankYou\Tags\TagFactory;
use Claromentis\ThankYou\Tags\TagRepository;
use Claromentis\ThankYou\Tags\UI\TagTemplaterComponent;
use Claromentis\ThankYou\ThankYous\DataTables\Tag\TagsDataTableSource;
use Claromentis\ThankYou\ThankYous\DataTables\ThankYou\ThankYousDataTableSource;
use Claromentis\ThankYou\ThankYous\DataTables\User\UsersDataTableSource;
use Claromentis\ThankYou\ThankYous\Format\ThankYouFormatter;
use Claromentis\ThankYou\ThankYous\ThankYouAcl;
use Claromentis\ThankYou\ThankYous\ThankYouFactory;
use Claromentis\ThankYou\ThankYous\ThankYousRepository;
use Claromentis\ThankYou\ThankYous\ThankYouUtility;
use Claromentis\ThankYou\UI\ThankYouTemplaterComponent;
use Claromentis\ThankYou\UI\ThankYouCreateTemplaterComponent;
use Claromentis\ThankYou\UI\ThankYousListTemplaterComponent;
use Claromentis\ThankYou\UI\ThankYouTagStatsTemplaterComponent;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Psr\Log\LoggerInterface;
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
	use TemplaterTrait;

	const APPLICATION_NAME = 'thankyou';

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
		//Configuration
		$app[self::APPLICATION_NAME . '.config'] = function ($app) {
			return $app['config.factory'](self::APPLICATION_NAME);
		};

		$app[Configuration\Api::class] = function ($app) {
			return new Configuration\Api($app[Configuration\ConfigOptions::class], $app[self::APPLICATION_NAME . '.config']);
		};

		//Tags
		$app[TagAcl::class] = function ($app) {
			return new TagAcl($app['admin.panels_list']->GetOne(self::APPLICATION_NAME));
		};

		$app[TagRepository::class] = function ($app) {
			return new TagRepository($app[DbInterface::class], $app[QueryFactory::class], $app['logger_factory']->GetLogger('tags'), $app[TagFactory::class]);
		};

		$app[TagFormatter::class] = function ($app) {
			return new TagFormatter($app['rest.formatter']);
		};

		$app['templater.ui.thankyou.tag'] = function ($app) {
			return new TagTemplaterComponent($app[Tags\Api::class], $app['logger_factory']->GetLogger('tag'));
		};

		$app['templater.ui.thankyou.tag_stats'] = function ($app) {
			return new ThankYouTagStatsTemplaterComponent($app[ThankYous\Api::class], $app[Tags\Api::class]);
		};

		$app['tags.datatable.admin'] = TagDataTableSource::class;

		//Thankable
		$app[Thankable\Factory::class] = function ($app) {
			return new Thankable\Factory($app[Lmsg::class], $app[ThankYouUtility::class]);
		};

		//Thank Yous
		$app[LineManagerNotifier::class] = function ($app) {
			return new LineManagerNotifier($app['logger_factory']->GetLogger(self::APPLICATION_NAME));
		};

		// Localization domain
		$app['localization.domain.thankyou'] = function ($app) {
			return $app['localization.domain_from_files_factory'](self::APPLICATION_NAME);
		};

		$app['menu.applications'] = $app->extend('menu.applications', function ($menu_items, $app) {
			$menu_items[self::APPLICATION_NAME] = ['name' => ($app[Lmsg::class])('thankyou.app_name'), 'css_class' => 'glyphicons-donate', 'url' => '/thankyou/thanks'];

			return $menu_items;
		});

		// Admin panel
		$app['admin.panels'] = $app->extend('admin.panels', function ($panels, $app) {
			$panels[self::APPLICATION_NAME] = [
				'name'      => $app['lmsg']('thankyou.app_name'),
				'css_class' => 'glyphicons-donate',
				'url'       => '/thankyou/admin/'
			];

			return $panels;
		});

		$app['thankyou.rest_controller'] = function ($app) {
			return new ThanksRestController($app['thankyou.repository']);
		};

		// Repositories
		$app['thankyou.repository'] = function ($app) {
			return new ThanksRepository($app->db);
		};

		$app[ThankYousRepository::class] = function ($app) {
			return new ThankYousRepository(
				$app[ThankYouFactory::class],
				$app[ThankYouUtility::class],
				$app[DbInterface::class],
				$app['logger_factory']->GetLogger(self::APPLICATION_NAME),
				$app[QueryFactory::class],
				$app[Tags\Api::class],
				$app[Thankable\Factory::class]
			);
		};

		$app[ThankYouAcl::class] = function ($app) {
			return new ThankYouAcl($app['admin.panels_list']->GetOne(self::APPLICATION_NAME), $app[UserExtranetService::class]);
		};

		// Pages component
		$app['pages.component.thankyou'] = function ($app) {
			return new UI\PagesComponent($app[Lmsg::class], $app[Configuration\Api::class]);
		};

		$app['audit.application.thankyou'] = function ($app) {
			return new AuditConfig($app[Lmsg::class], $app[Tags\Api::class], $app[ThankYous\Api::class]);
		};

		$app['templater.ui.thankyou.list'] = function ($app) {
			return new ThankYousListTemplaterComponent($app[Api::class], $app[Lmsg::class]);
		};

		$app['templater.ui.thankyou.thank_you'] = function ($app) {
			return new ThankYouTemplaterComponent($app[Api::class], $app[ClaText::class], $app[Lmsg::class], $app['logger_factory']->GetLogger(self::APPLICATION_NAME));
		};

		$app['templater.ui.thankyou.create'] = function ($app) {
			return new ThankYouCreateTemplaterComponent($app[Configuration\Api::class], $app[Lmsg::class], $app['logger_factory']->GetLogger(self::APPLICATION_NAME));
		};

		$app['thankyou.datatable.thank_yous'] = function ($app) {
			return new ThankYousDataTableSource(
				$app[ThankYous\Api::class],
				$app[Configuration\Api::class],
				$app[SugreUtility::class],
				$app[Lmsg::class]
			);
		};

		$app['thankyou.datatable.users'] = function ($app) {
			return new UsersDataTableSource(
				$app[ThankYous\Api::class],
				$app[SugreUtility::class],
				$app[Lmsg::class]
			);
		};

		$app['thankyou.datatable.statistics.tags'] = function ($app) {
			return new TagsDataTableSource(
				$app[ThankYous\Api::class],
				$app[SugreUtility::class],
				$app[Tags\Api::class],
				$app[Lmsg::class]
			);
		};

		$app[ThanksRestV2::class] = function ($app) {
			return new ThanksRestV2(
				$app[Api::class],
				$app[ResponseFactory::class],
				$app['logger_factory']->GetLogger('tags'),
				$app['rest.formatter'],
				$app[Lmsg::class],
				$app[ThankYouFormatter::class],
				$app[TagFormatter::class]
			);
		};

		$app->extend('likes.audit.applications', function ($applications) {
			$applications[ThankYousRepository::AGGREGATION_ID] = [
				"like_text"           => "User liked a thank you note",
				"unlike_text"         => "User unliked a thank you note",
				"comment_like_text"   => "User liked comment (#%d) on a thank you note",
				"comment_unlike_text" => "User unliked comment (#%d) on a thank you note",

				"application" => "thankyou",
			];

			return $applications;
		});

		$app['audit.application.thankyou'] = AuditConfig::class;
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
				$routes->get('/thanks', ThankYouController::class . ':View');
				$routes->get('/thanks/{id}', ThankYouController::class . ':View');

				$routes->secure('html', 'admin', ['panel_code' => self::APPLICATION_NAME]);
				$routes->get('/admin', StatisticsController::class . ':Reports');
				$routes->match('/admin/configuration', AdminController::class . ':Configuration')->method('GET|POST');
				$routes->get('/admin/core_values', AdminController::class . ':CoreValues');
				$routes->get('/admin/statistics/{report_index}', StatisticsController::class . ':View');
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
			'/thankyou/v0' => '/thankyou/v2',
			'/thankyou/v1' => function (ControllerCollection $routes) {
				$routes->get('/thanks/{id}', 'thankyou.rest_controller:GetThanksItem')->assert('id', '\d+');
			},
			'/thankyou/v2' => function (ControllerCollection $routes) {
				$routes->get('/tags/total', ThanksRestV2::class . ':GetTotalTags');
				$routes->secure('rest', 'user');
				$routes->get('/thanks', ThanksRestV2::class . ':GetThankYous');
				$routes->get('/thanks/{id}', ThanksRestV2::class . ':GetThankYou')->assert('id', '\d+');
				$routes->get('/tags', ThanksRestV2::class . ':GetTags');
				$routes->get('/tags/{id}', ThanksRestV2::class . ':GetTag')->assert('id', '\d+');
				$routes->post('/thankyou', ThanksRestV2::class . ':CreateThankYou');
				$routes->post('/thankyou/{id}', ThanksRestV2::class . ':UpdateThankYou')->assert('id', '\d+');
				$routes->delete('/thankyou/{id}', ThanksRestV2::class . ':DeleteThankYou')->assert('id', '\d+');

				$routes->secure('rest', 'admin', ['panel_code' => self::APPLICATION_NAME]);
				$routes->post('/tags', ThanksRestV2::class . ':CreateTag');
				$routes->post('/tags/{id}', ThanksRestV2::class . ':UpdateTag')->assert('id', '\d+');
				$routes->delete('/tags/{id}', ThanksRestV2::class . ':DeleteTag')->assert('id', '\d+');
				$routes->post('/admin/config', ThanksRestV2::class . ':SetConfig');
			}
		];
	}

	/**
	 * Instant Message types
	 *
	 * @return array
	 */
	public function GetIMConfig()
	{
		return [
			self::APPLICATION_NAME,
			lmsg("thankyou.communication.imessages"),
			[
				ThankYous\Api::IM_TYPE_THANKYOU
			]
		];
	}

	/**
	 * This Method is required by ClaPlugins::IsObjectValid, in order for Sending Messages/Notifications to work.
	 * It will be deprecated as soon as possible and should be not be used elsewhere.
	 *
	 * @param int $aggregation_id
	 * @param int $object_id
	 * @return bool
	 */
	public function IsObjectValid(int $aggregation_id, int $object_id)
	{
		try
		{
			/**
			 * @var ThankYous\Api $thank_you_api
			 */
			$thank_you_api = Services::I()->{ThankYous\Api::class};
			$thank_you_api->GetThankYou($object_id);

			return true;
		} catch (ThankYouNotFoundException $exception)
		{
			return false;
		}
	}

	/**
	 * Register the module's event listeners.
	 *
	 * @param Container                $app
	 * @param EventDispatcherInterface $dispatcher
	 */
	public function subscribe(Container $app, EventDispatcherInterface $dispatcher)
	{
		(new LazyResolver($app, CommentsSubscriber::class, CommentsSubscriber::getSubscribedEvents()))->subscribe($dispatcher);
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
		if (empty($attr['user_id']) || !is_numeric($attr['user_id']))
		{
			return '';
		}

		$api     = $app[Api::class];
		$lmsg    = $app[Lmsg::class];
		$context = $app[SecurityContext::class];
		/**
		 * @var LoggerInterface $logger
		 */
		$logger = $app['logger_factory']->GetLogger(self::APPLICATION_NAME);

		$user_id = (int) $attr['user_id'];

		switch ($attr['page'])
		{
			case 'viewprofile.tab_nav':
				$count = $api->ThankYous()->GetUsersTotalThankYous($context, null, null, [$user_id])[$user_id];

				return '<li><a href="#thanks"><span class="glyphicons glyphicons-donate"></span> ' . $lmsg("thankyou.user_profile.tab_name") . ' (<b>' . $count . '</b>)</a></li>';
			case 'viewprofile.tab_content':
				$create = 0;
				try
				{
					$thankable = $api->ThankYous()->CreateThankableFromOClass(PermOClass::INDIVIDUAL, $user_id);
					if ($api->ThankYous()->CanSeeThankableName($context, $thankable))
					{
						$create = $thankable;
					}
				} catch (UnsupportedOwnerClassException $exception)
				{
					$logger->error("Failed to lock Thank You Creation to User Id '" . $user_id . "' on User's Profile", [$exception]);
				}

				$args                     = [];
				$args['ty_list.limit']    = 20;
				$args['ty_list.user_ids'] = [$user_id];
				$args['ty_list.comments'] = true;
				$args['ty_list.create']   = [$create];

				$thank_you_list = $this->CallTemplater('thankyou/UI/pages_component.html', $args);

				return '<div id="thanks">' . $thank_you_list . '</div>';
		}

		return '';
	}
}
