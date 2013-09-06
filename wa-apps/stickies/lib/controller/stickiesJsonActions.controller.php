<?php
/**
 *
 * @author WebAsyst Team
 *
 */

class stickiesJsonActionsController extends waJsonActions
{
	protected $app_id;
	public function preExecute()
	{
		parent::preExecute();
		$user = $this->getUser();
		$this->app_id = waSystem::getInstance()->getApp();
		if (!$user->isAdmin($this->app_id) && !$user->getRights($this->app_id)) {
			throw new waException(null, 403);
		}
	}
}
