<?php
class blogViewAction extends waViewAction
{
	public function display($clear_assign = true)
	{
		$this->view->getHelper()->globals($this->getRequest()->param());
		return parent::display($clear_assign);
	}
}