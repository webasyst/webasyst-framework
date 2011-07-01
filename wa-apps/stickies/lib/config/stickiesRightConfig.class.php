<?php

class stickiesRightConfig extends waRightConfig
{
	public function init()
	{
		$this->addItem('add_sheet', _w('Allow add new board'), 'checkbox');
		// Add sheets
		$sheet_model = new stickiesSheetModel();
		$sheets = $sheet_model->get(true);
		$items = array();
		foreach ($sheets as $sheet) {
			$items[$sheet['id']] = (!empty($sheet['name'])) ? $sheet['name'] : "<"._w('no name').">";
		}
		$this->addItem('sheet', _w('Available boards'), 'list', array('items' => $items, 'position' => 'right'));
	}
}
