<?php

namespace Claromentis\ThankYou;

/**
 *
 * @author Alexander Polyanskikh
 */
class Plugin implements \ClaPlugin, \TemplaterComponent
{
	public function Show($attr)
	{
		switch ($attr['page'])
		{
			case 'viewprofile.tab_nav':
				if (empty($attr['user_id']))
					return '';
				return '<li><a href="#thanks"><span class="cla-icon-thumbs-up"></span> Thanks</a></li>';
			case 'viewprofile.tab_content':
				if (empty($attr['user_id']))
					return '';
				$component = new UI\Wall();
				$component_data = $component->Show(array('user_id' => $attr['user_id']));
				return '<div id="thanks">' . $component_data . '</div>';
		}
		return '';
	}
}