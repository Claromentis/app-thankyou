<?php

namespace Claromentis\ThankYou\Comments;

class Factory
{
	/**
	 * @param int $thank_you_id
	 * @return CommentableThankYou
	 */
	public function Create(int $thank_you_id)
	{
		$comment = new CommentableThankYou();

		$comment->Load($thank_you_id);

		return $comment;
	}
}
