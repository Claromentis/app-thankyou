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
				$user_id = getvar('id');
				if (!$user_id)
					return '';
				$repository = new ThanksRepository();
				$count = $repository->GetCount($user_id);
				return '<li><a href="#thanks"><span class="cla-icon-thumbs-up"></span> Thanks ('.$count.')</a></li>';
			case 'viewprofile.tab_content':
				$user_id = getvar('id');
				if (!$user_id)
					return '';
				$component = new UI\Wall();
				$component_data = $component->Show(array('user_id' => $user_id));
				return '<div id="thanks">' . $component_data . '</div>';
		}
		return '';
	}
}