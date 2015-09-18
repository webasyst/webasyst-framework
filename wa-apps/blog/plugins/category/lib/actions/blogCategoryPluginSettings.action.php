<?php

class blogCategoryPluginSettingsAction extends blogPluginsSettingsViewAction
{
    public function execute()
    {
        $this->plugin_id = 'category';
        parent::execute();
        if($data = waRequest::post($this->plugin_id)) {
            $order = 0;
            $model = new blogCategoryModel();
            foreach($data as $id => &$row) {
                $id = intval($id);
                if (!empty($row['name'])) {
                    $row['sort'] = $order++;
                    if ($id > 0) {
                        if(!empty($row['delete'])) {
                            $model->deleteById($id);
                        } else {
                            $model->updateById($id, $row);
                            $row['id'] = $id;
                        }
                    } elseif ($id < 0) {
                        $row['id'] = $model->insert($row);
                    }
                }
            }
            unset($row);
        }
        $categories = blogCategory::getAll();
        $icons = $this->getConfig()->getIcons();
        if(!$categories) {
            $categories[0] = array('url'=>'','name'=>'','icon'=>current($icons),'id'=>0,'qty'=>0, 'sort'=>0);
        }
        $this->view->assign('categories', $categories);
        $this->view->assign('icons', $icons);
    }
}

