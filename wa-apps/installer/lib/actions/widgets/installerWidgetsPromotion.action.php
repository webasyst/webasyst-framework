<?php
class installerWidgetsPromotionAction extends waViewAction
{
    public function execute()
    {
        $ui_version = waRequest::request('ui_version', null, 'string');
        $app_id = waRequest::request('app_id');
        $promotion_id = waRequest::request('promotion_id', null, 'string');
        if (!$app_id || !wa()->appExists($app_id)) {
            die('');
        }
        if ($ui_version === null) {
            $ui_version = wa($app_id)->whichUi();
        }

        $promotion = $this->getPromotion([
            'current_app_id' => $app_id,
            'promotion_id' => $promotion_id,
            'ui_version' => $ui_version,
        ]);
        if (!$promotion) {
            die('');
        }

        if (false && $ui_version == '2.0') {
            $this->setTemplate('WidgetsPromotionUI2.0.html');
        } else {
            $this->setTemplate('WidgetsPromotionUI1.3.html');
        }

        $this->view->assign([
            'app_id' => $app_id,
            'ui_version' => $ui_version,
            'promotion' => $promotion,
        ]);
    }

    protected function getPromotion(array $params = [])
    {
        $list = (new installerAnnouncementList())->withFilteredByApp($params['current_app_id']);
        $promotions = $list->getPromotionList(ifset($params, 'ui_version', '1.3'), ifset($params, 'promotion_id', null));
        $promotion = reset($promotions);
        if ($promotion) {
            $promotion['id'] = key($promotions);
        }
        return $promotion;
    }
}
