<?php

namespace Claromentis\ThankYou\UI;

use Carbon\Carbon;
use Claromentis\Core\Application;
use Claromentis\Core\CDN\CDNSystemException;
use Claromentis\Core\Date\DateFormatter;
use Claromentis\Core\Localization\Lmsg;
use Claromentis\Core\Security\SecurityContext;
use Claromentis\Core\Templater\Plugin\TemplaterComponentTmpl;
use Claromentis\Core\TextUtil\ClaText;
use Claromentis\ThankYou\Api;
use DateClaTimeZone;
use User;

/**
 * Component displays list of recent thanks and allows submitting a new one.
 *
 **/
//TODO Fix spacing around Say Thank You button
class ThankYousList extends TemplaterComponentTmpl
{
	/**
	 * @param array       $attributes :
	 *                                admin_mode:
	 *                                1 = Editing and Deleting Thank Yous ignores permissions. Thank Yous are not filtered by Thanked Extranet Area ID.
	 *                                create:
	 *                                0 = Creating Thank Yous is disabled.
	 *                                1 = Creating Thank Yous is enabled.
	 *                                array = Creating ThankYous is locked to the Thankable array given (Created with \Claromentis\ThankYou\View\ThanksListView::ConvertThankableToArray).
	 *                                delete:
	 *                                0 = Deleting Thank Yous is disabled.
	 *                                1 = Deleting Thank Yous is enabled (subject to permissions or admin_mode).
	 *                                edit:
	 *                                0 = Editing Thank Yous is disabled.
	 *                                1 = Editing Thank Yous is enabled (subject to permissions or admin_mode).
	 *                                thanked_images:
	 *                                0 = Thanked will never display as an image.
	 *                                1 = Thanked will display as an image if available.
	 *                                links:
	 *                                0 = Thanked will never provide a link.
	 *                                1 = Thanked will provide a link if available.
	 *                                limit:
	 *                                int = How many Thank Yous to display.
	 *                                offset:
	 *                                int = Offset of Thank Yous.
	 *                                user_id:
	 *                                int  = Only display Thank Yous associated with this User.
	 * @param Application $app
	 * @return string
	 */
	public function Show($attributes, Application $app)
	{
		$api              = $app[Api::class];
		$cla_text         = $app[ClaText::class];
		$lmsg             = $app[Lmsg::class];
		$security_context = $app[SecurityContext::class];

		$admin_mode       = (bool) ($attributes['admin_mode'] ?? null);

		$extranet_area_id = $admin_mode ? null : (int) $security_context->GetExtranetAreaId();
		$time_zone        = DateClaTimeZone::GetCurrentTZ();

		$can_create       = (bool) ($attributes['create'] ?? null);
		$can_delete       = (bool) ($attributes['delete'] ?? null);
		$can_edit         = (bool) ($attributes['edit'] ?? null);
		$create_thankable = (isset($attributes['create']) && is_array($attributes['create'])) ? $attributes['create'] : null;
		$thanked_images   = (bool) ($attributes['thanked_images'] ?? null);
		$links_enabled    = (bool) ($attributes['links'] ?? null);
		$limit            = (int) ($attributes['limit'] ?? 20);
		$offset           = (int) ($attributes['offset'] ?? null);
		$user_id          = (isset($attributes['user_id'])) ? (int) $attributes['user_id'] : null;

		if (isset($user_id))
		{
			$thank_yous = $api->ThankYous()->GetUsersRecentThankYous($user_id, $limit, $offset, true);
		} else
		{
			$thank_yous = $api->ThankYous()->GetRecentThankYous($limit, $offset, true, $extranet_area_id);
		}

		$args            = [];
		$view_thank_yous = [];
		foreach ($thank_yous as $thank_you)
		{
			$author_hidden = false;
			if (!$admin_mode && $extranet_area_id !== (int) $thank_you->GetAuthor()->GetExAreaId())
			{
				$author_hidden = true;
			}

			try
			{
				$author_image_url = $author_hidden ? null : User::GetPhotoUrl($thank_you->GetAuthor()->GetId(), false);//TODO: Replace with a non-static post People API update
			} catch (CDNSystemException $CDN_system_exception)
			{
				//TODO: Logging
				$author_image_url = null;
			}
			$author_link          = $author_hidden ? null : User::GetProfileUrl($thank_you->GetAuthor()->GetId(), false);//TODO: Replace with a non-static post People API update
			$author_name          = $author_hidden ? $lmsg('common.perms.hidden_name') : $thank_you->GetAuthor()->GetFullname();
			$id                   = $thank_you->GetId();
			$can_edit_thank_you   = isset($id) && $can_edit && $api->ThankYous()->CanEditThankYou($thank_you, $security_context);
			$can_delete_thank_you = isset($id) && $can_delete && $api->ThankYous()->CanDeleteThankYou($thank_you, $security_context);
			$date_created         = clone $thank_you->GetDateCreated();
			$date_created->setTimezone($time_zone);

			$thankeds     = $thank_you->GetThanked();
			$view_thanked = [];
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

					$view_thanked[] = [
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

			$view_thank_yous[] = [
				'thanked.datasrc' => $view_thanked,

				'author_name.body'  => $author_name,
				'author_link.href'  => $author_link,
				'profile_image.src' => $author_image_url,

				'description.body_html'   => $cla_text->ProcessPlain($thank_you->GetDescription()),
				'has_description.visible' => strlen($thank_you->GetDescription()) > 0,

				'like_component.object_id' => $thank_you->GetId(),
				'like_component.visible'   => isset($id),

				'delete_thanks.visible'      => $can_delete_thank_you,
				'edit_thanks.visible'        => $can_edit_thank_you,
				'edit_thanks_link.data-id'   => $thank_you->GetId(),
				'delete_thanks_link.data-id' => $thank_you->GetId(),

				'date_created.body'  => Carbon::instance($date_created)->diffForHumans(),
				'date_created.title' => $date_created->getDate(DateFormatter::LONG_DATE)
			];
		}

		$args['thank_yous.datasrc'] = $view_thank_yous;

		if (count($args['thank_yous.datasrc']) === 0)
		{
			$args['no_thanks.body'] = $lmsg('thankyou.thanks_list.no_thanks');
		}

		if ($can_create)
		{
			$args['create.visible'] = 1;
				if (isset($create_thankable))
				{
					$args['preselected_thankable.json'] = $create_thankable;
				}
		} else
		{
			$args['create.visible'] = 0;
		}

		$args['thank_you_user.placeholder'] = $lmsg('thankyou.thank.placeholder');

		foreach ($api->ThankYous()->GetThankableObjectTypes() as $object_type_id)
		{
			if (!isset($thankable_object_types))
			{
				$thankable_object_types = (string) $object_type_id;
			} else {
				$thankable_object_types .= "," . $object_type_id;
			}
		}
		$args['thank_you_user.filter_perm_oclasses'] = $thankable_object_types;

		return $this->CallTemplater('thankyou/thank_yous_list.html', $args);
	}
}
