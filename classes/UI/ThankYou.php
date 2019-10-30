<?php

namespace Claromentis\ThankYou\UI;

use Carbon\Carbon;
use Claromentis\Core\Application;
use Claromentis\Core\CDN\CDNSystemException;
use Claromentis\Core\Config\Config;
use Claromentis\Core\Date\DateFormatter;
use Claromentis\Core\Localization\Lmsg;
use Claromentis\Core\Security\SecurityContext;
use Claromentis\Core\Templater\Plugin\TemplaterComponentTmpl;
use Claromentis\Core\TextUtil\ClaText;
use Claromentis\ThankYou\Api;
use DateClaTimeZone;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use User;

class ThankYou extends TemplaterComponentTmpl
{
	private $logger;

	public function __construct(LoggerInterface $logger)
	{
		$this->logger = $logger;
	}

	/**
	 * #Attributes
	 * ##Required
	 * * thank_you:
	 *     * int = The ID of a Thank You.
	 *     * \Claromentis\ThankYou\ThankYous\ThankYou = The Thank You to display.
	 *
	 * ##Optional
	 * * admin_mode:
	 *     * 0 = Author and Thanked details will be hidden if the Viewer belongs to a different Extranet Area.(default)
	 *     * 1 = Editing and Deleting the Thank You ignores permissions.
	 *           Author and Thanked will display regardless of Viewers Extranet Area.
	 * * comments:
	 *     * 0 = Comments will not be displayed.(default)
	 *     * 1 = Comments will be displayed.
	 * * delete:
	 *     * 0 = Deleting the Thank You is disabled.(default)
	 *     * 1 = Deleting the Thank You is enabled (subject to permissions or admin_mode).
	 * * edit:
	 *     * 0 = Editing the Thank You is disabled.(default)
	 *     * 1 = Editing the Thank You is enabled (subject to permissions or admin_mode).
	 * * links:
	 *     * 0 = Thanked will never provide a link.(default)
	 *     * 1 = Thanked will provide a link if available.
	 * * thanked_images:
	 *     * 0 = Thanked will never display as an image.(default)
	 *     * 1 = Thanked will display as an image if available.
	 * * thank_link:
	 *     * 0 = The Thank will not provide a link to itself.(default)
	 *     * 1 = The Thank will provide a linke to iteslf.
	 *
	 * @param             $attributes
	 * @param Application $app
	 * @return string
	 * @throws InvalidArgumentException
	 */
	public function Show($attributes, Application $app): string
	{
		$api      = $app[Api::class];
		$cla_text = $app[ClaText::class];
		/**
		 * @var Config $config
		 */
		$config           = $app['thankyou.config'];
		$lmsg             = $app[Lmsg::class];
		$security_context = $app[SecurityContext::class];
		$time_zone        = DateClaTimeZone::GetCurrentTZ();
		$admin_mode       = (bool) ($attributes['admin_mode'] ?? null);
		$can_delete       = (bool) ($attributes['delete'] ?? null);
		$can_edit         = (bool) ($attributes['edit'] ?? null);
		$links_enabled    = (bool) ($attributes['links'] ?? null);
		$thanked_images   = (bool) ($attributes['thanked_images'] ?? null);

		$thank_you = $attributes['thank_you'] ?? null;
		if (!isset($thank_you))
		{
			throw new InvalidArgumentException("Failed to generate Thank You Templater Component, the attribute 'thank_you' must be supplied as either a Thank You ID, or a Thank You object.");
		}
		if (is_string($thank_you))
		{
			$thank_you = (int) $thank_you;
		}
		if (is_int($thank_you))
		{
			$thank_you = $api->ThankYous()->GetThankYous($thank_you, true);
		}
		if (!($thank_you instanceof \Claromentis\ThankYou\ThankYous\ThankYou))
		{
			throw new InvalidArgumentException("Failed to generate Thank You Templater Component, object of type \"\Claromentis\ThankYou\ThankYous\ThankYou\" must be given.");
		}

		$id                   = $thank_you->GetId();
		$can_edit_thank_you   = isset($id) && $can_edit && $api->ThankYous()->CanEditThankYou($thank_you, $security_context);
		$can_delete_thank_you = isset($id) && $can_delete && $api->ThankYous()->CanDeleteThankYou($thank_you, $security_context);
		$display_comments = ((bool) isset($id) && ($attributes['comments'] ?? null) && (bool) $config->Get('thank_you_comments'));
		$extranet_area_id     = $admin_mode ? null : (int) $security_context->GetExtranetAreaId();
		$thank_link   = ((bool) ($attributes['thank_link'] ?? null)) && isset($id);

		$author_hidden = false;
		if (!$admin_mode && $extranet_area_id !== (int) $thank_you->GetAuthor()->GetExAreaId())
		{
			$author_hidden = true;
		}
		$author_link = $author_hidden ? null : User::GetProfileUrl($thank_you->GetAuthor()->GetId(), false);//TODO: Replace with a non-static post People API update
		$author_name = $author_hidden ? $lmsg('common.perms.hidden_name') : $thank_you->GetAuthor()->GetFullname();

		try
		{
			$author_image_url = $author_hidden ? null : User::GetPhotoUrl($thank_you->GetAuthor()->GetId(), false);//TODO: Replace with a non-static post People API update
		} catch (CDNSystemException $cdn_system_exception)
		{
			$this->logger->error("Error thrown when getting User's Photo's URL in Thank Templater Component: " . $cdn_system_exception->getMessage());
			$author_image_url = null;
		}

		$date_created = clone $thank_you->GetDateCreated();
		$date_created->setTimezone($time_zone);

		$thanked_args = [];
		$thankeds     = $thank_you->GetThanked();
		if (isset($thankeds))
		{
			$total_thanked = count($thankeds);
			foreach ($thankeds as $offset => $thanked)
			{
				$thanked_ex_area_id = $thanked->GetExtranetAreaId();
				$thanked_hidden     = false;
				if (!$admin_mode && isset($thanked_ex_area_id) && $extranet_area_id !== $thanked_ex_area_id)
				{
					$thanked_hidden = true;
				}

				$image_url             = $thanked_hidden ? null : $thanked->GetImageUrl();
				$thanked_link          = $thanked_hidden ? null : $thanked->GetProfileUrl();
				$display_thanked_image = !$thanked_hidden && $thanked_images && isset($image_url);
				$thanked_tooltip       = $display_thanked_image ? $thanked->GetName() : '';
				$thanked_link_enabled  = !$thanked_hidden && $links_enabled && isset($thanked_link);
				$thanked_name          = $thanked_hidden ? $lmsg('common.perms.hidden_name') : $thanked->GetName();

				$thanked_args[] = [
					'thanked_name.body'         => $thanked_name,
					'thanked_name.visible'      => !$display_thanked_image,
					'thanked_link.visible'      => $thanked_link_enabled,
					'thanked_no_link.visible'   => !$thanked_link_enabled,
					'thanked_link.href'         => $thanked_link,
					'thanked_link.title'        => $thanked_tooltip,
					'profile_image.src'         => $image_url,
					'profile_image.visible'     => $display_thanked_image,
					'delimiter_visible.visible' => !($offset === $total_thanked - 1)
				];
			}
		}

		$thankable_object_types      = '';
		$first_thankable_object_type = true;
		foreach ($api->ThankYous()->GetThankableObjectTypes() as $object_type_id)
		{
			if ($first_thankable_object_type)
			{
				$thankable_object_types      = (string) $object_type_id;
				$first_thankable_object_type = false;
			} else
			{
				$thankable_object_types .= "," . $object_type_id;
			}
		}

		$args = [
			'thank_title.visible' => !$thank_link,
			'thank_title_link.visible' => $thank_link,
			'thank_title_link.href' => '/thankyou/thanks/' . $id,

			'thanked.datasrc' => $thanked_args,

			'author_name.body'  => $author_name,
			'author_link.href'  => $author_link,
			'profile_image.src' => $author_image_url,

			'description.body_html'   => $cla_text->ProcessPlain($thank_you->GetDescription()),
			'has_description.visible' => strlen($thank_you->GetDescription()) > 0,

			'comments.visible' => $display_comments,
			'thank_you_comment.object_id' => $id,

			'like_component.object_id' => $id,
			'like_component.visible'   => isset($id),

			'delete_thanks.visible'      => $can_delete_thank_you,
			'edit_thanks.visible'        => $can_edit_thank_you,
			'edit_thanks_link.data-id'   => $id,
			'delete_thanks_link.data-id' => $id,

			'date_created.body'  => Carbon::instance($date_created)->diffForHumans(),
			'date_created.title' => $date_created->getDate(DateFormatter::LONG_DATE),

			'thank_you_user.filter_perm_oclasses' => $thankable_object_types,
			'thank_you_user.placeholder'          => $lmsg('thankyou.thank.placeholder')
		];

		return $this->CallTemplater('thankyou/thank_you.html', $args);
	}
}
