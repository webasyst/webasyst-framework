<?php

class photosSettingsAction extends waViewAction
{
    public function execute()
    {
        $settings = $this->getConfig()->getOption(null);
        if (waRequest::getMethod() == 'post') {
            $this->save($settings);
            $this->view->assign('saved', 1);
        }
        $settings['sizes'] = array(
            'system' => $this->formatSizes($this->getConfig()->getSizes('system')),
            'custom' => $this->formatSizes($settings['sizes'])
        );
        $settings += array(
            'sharpen' => null,
            'max_size' => 970,
            'enable_2x' => null,
            'save_quality' => null,
            'save_original' => null,
            'save_quality_2x' => null,
            'thumbs_on_demand' => null,
        );
        $this->view->assign('settings', $settings);
        $this->view->assign('sidebar_width', $this->getConfig()->getSidebarWidth());

    }

    protected function formatSizes($sizes)
    {
        $result = array();
        foreach ($sizes as $size) {
            $size_info = photosPhoto::parseSize((string)$size);
            $type = $size_info['type'];
            $width = $size_info['width'];
            $height = $size_info['height'];
            if ($type == 'max' || $type == 'crop' || $type == 'width') {
                $result[] = array($type => $width);
            } else if ($type == 'height') {
                $result[] = array($type => $height);
            } elseif ($type == 'rectangle') {
                $result[] = array('rectangle' => array($width, $height));
            }
        }
        return $result;
    }

    protected function checkSize($size, $settings)
    {
        $size = (int)$size;
        if ($size <= 0) {
            return false;
        }
        if ($settings['thumbs_on_demand'] && $size > $settings['max_size']) {
            $size = $settings['max_size'];
        }
        return $size;
    }

    /**
     * @param array $settings
     */
    protected function save(&$settings)
    {
        $settings['sharpen'] = waRequest::post('sharpen') ? 1 : 0;
        $settings['save_original'] = waRequest::post('save_original') ? 1 : 0;
        $settings['thumbs_on_demand'] = waRequest::post('thumbs_on_demand') ? 1 : 0;
        if ($settings['thumbs_on_demand']) {
            $settings['max_size'] = waRequest::post('max_size', 1000, 'int');
            $big_size = $this->getConfig()->getSize('big');
            if ($settings['max_size'] < $big_size) {
                $settings['max_size'] = $big_size;
            }
        } elseif (isset($settings['max_size'])) {
            unset($settings['max_size']);
        }
        // delete sizes
        if ($delete = waRequest::post('delete', array(), waRequest::TYPE_ARRAY_INT)) {
            foreach ($delete as $k) {
                if (isset($settings['sizes'][$k])) {
                    unset($settings['sizes'][$k]);
                }
            }
        }
        // sizes
        if ($types = waRequest::post('size_type', array())) {
            $sizes = waRequest::post('size', array());
            $width = waRequest::post('width', array());
            $height = waRequest::post('height', array());
            foreach ($types as $k => $type) {
                if ($type == 'rectangle') {
                    $w = $this->checkSize($width[$k], $settings);
                    $h = $this->checkSize($height[$k], $settings);
                    if ($w && $h) {
                        $settings['sizes'][] = $w.'x'.$h;
                    }
                } else {
                    $size = $this->checkSize($sizes[$k], $settings);
                    if (!$size) {
                        continue;
                    }
                    switch ($type) {
                        case 'crop':
                            $settings['sizes'][] = $size.'x'.$size;
                            break;
                        case 'height':
                            $settings['sizes'][] = '0x'.$size;
                            break;
                        case 'width':
                            $settings['sizes'][] = $size.'x0';
                            break;
                        case 'max':
                            $settings['sizes'][] = $size;
                            break;
                    }
                }
            }
        }
        $settings['sizes'] = array_values($settings['sizes']);
        $config_file = $this->getConfig()->getConfigPath('config.php');

        $settings['enable_2x'] = waRequest::post('enable_2x') ? 1 : 0;
        foreach (array('save_quality', 'save_quality_2x') as $k) {
            $settings[$k] = waRequest::post($k, '', waRequest::TYPE_STRING_TRIM);

            if ($settings[$k] == '') {
                $settings[$k] = ($k == 'save_quality_2x') ? 70 : 90;
            } else {
                $settings[$k] = (float) $settings[$k];
                if ($settings[$k] < 0) {
                    $settings[$k] = 0;
                }
                if ($settings[$k] > 100) {
                    $settings[$k] = 100;
                }
                $settings[$k] = str_replace(',', '.', $settings[$k]);
            }
        }
        waUtils::varExportToFile($settings, $config_file);
    }

}