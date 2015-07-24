<?php
/**
 *
 * @author Webasyst
 *
 */
class stickiesStickyAddMethod extends stickiesAPIMethod
{
    protected $method = 'POST';
    public function execute()
    {
        $sheet_id = $this->get('sheet_id', true);
        $this->checkRights($sheet_id);
        $data = array(
            'position_top' => max(0, waRequest::post('position_top', 2000 + rand(0, 1000), 'int')),
            'position_left' => max(0, waRequest::post('position_left', 2000 + rand(0, 1000), 'int')),
            'color' => waRequest::post('color', 'default'),
            'size_height' => max(150, waRequest::post('size_height', 150)),
            'size_width' => max(150, waRequest::post('size_width', 150)),
            'content' => $this->post('content', true),
            'font_size' => waRequest::post('font_size', 16)
        );

        $sticky_model = new stickiesStickyModel();
        $sticky_id = $sticky_model->create($sheet_id, $data);

        if ($sticky_id) {
            $_GET['id'] = $sticky_id;
            $method = new stickiesSheetGetInfoMethod();
            $this->response = $method->getResponse(true);
        } else {
            throw new waAPIException('server_error', 500);
        }
    }
}
