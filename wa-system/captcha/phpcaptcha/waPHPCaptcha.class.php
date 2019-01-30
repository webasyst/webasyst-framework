<?php

/*
 * This file is part of Webasyst framework.
 *
 * Licensed under the terms of the GNU Lesser General Public License (LGPL).
 * http://www.webasyst.com/framework/license/
 *
 * @link http://www.webasyst.com/
 * @author Webasyst LLC
 * @copyright 2011-2012 Webasyst LLC
 * @package wa-system
 * @subpackage captcha
 */
class waPHPCaptcha extends waAbstractCaptcha
{
    protected $options = array(
        'chars' => 'abdefhknrqstxyz23456789',
        'fonts' => array(
            "DroidSans.ttf"
        ),
        'width' => 120,
        'height' => 40,
        'font_size' => 25, // in pt
        'background' => array()
    );

    protected $namespace;

    public function __construct(array $options = array())
    {
        parent::__construct($options);
        if (isset($options['namespace']) && is_scalar($options['namespace'])) {
            $this->namespace = $options['namespace'];
        }
        unset($this->options['invisible']);
    }

    public function getHtml($error = null, $absolute = false, $refresh = null)
    {
        $wrapper_class = ifempty($this->options, 'wrapper_class', 'wa-captcha');

        $app_id = $this->getAppId();
        $root_url = wa()->getRootUrl($absolute, true);
        if ($app_id == 'webasyst') {
            $app_id = '';
            $root_url = rtrim($root_url, '/');
        }
        $captcha_url = $root_url . $app_id . '/captcha.php?rid=' .uniqid(time());

        $input_name = $this->namespace ? "{$this->namespace}[captcha]" : 'captcha';
        $refresh = ($refresh === null) ? _ws("Refresh CAPTCHA") : $refresh;
        $error_class = $error ? ' wa-error': '';

        $view = wa('webasyst')->getView();
        $view->assign(array(
            'wrapper_class' => $wrapper_class,
            'captcha_url'   => $captcha_url,
            'input_name'    => $input_name,
            'refresh'       => $refresh,
            'error_class'   => $error_class,
        ));

        $captcha_version = ifempty($this->options, 'version', null);
        $template_dir_path = wa()->getConfig()->getRootPath() .'/wa-system/captcha/phpcaptcha/templates/';
        $template = $template_dir_path .'captcha.html';

        if ($captcha_version) {
            $captcha_template = $template_dir_path. 'captcha'.$captcha_version.'.html';
            if (file_exists($captcha_template)) {
                $template = $captcha_template;
            }
        }
        
        return $view->fetch($template);
    }

    /**
     * @param string $code
     * @param string $error
     * @return bool
     */
    public function isValid($code = null, &$error = '')
    {
        if ($code == null) {
            if ($this->namespace) {
                $post = wa()->getRequest()->post($this->namespace);
                $code = ifset($post['captcha']);
            } else {
                $code = wa()->getRequest()->post('captcha');
            }
        }
        $code = strtolower(trim($code));
        $captcha = wa()->getStorage()->get('captcha');
        $app_id = $this->getAppId();
        if (isset($captcha[$app_id]) && $captcha[$app_id] === $code) {
            unset($captcha[$app_id]);
            wa()->getStorage()->set('captcha', $captcha);
            return true;
        } else {
            $error = _ws('Invalid captcha');
            return false;
        }
    }

    private function getAppId()
    {
        $app_id = isset($this->options['app_id']) ? $this->options['app_id'] : wa()->getApp();
        if (!$app_id) {
            $app_id = 'webasyst';
        }
        return $app_id;
    }

    public function display()
    {
        $code = $this->generateCode();
        wa()->getStorage()->set(array('captcha', $this->getAppId()), $code);
        $this->responseImage($code);
    }

    protected function generateCode()
    {
        $chars = $this->options['chars']; // Chars
        $length = rand(4, 5); // Length of the captcha
        $num_chars = strlen($chars);
        $str = '';
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, rand(1, $num_chars) - 1, 1);
        }

        $array_mix = preg_split('//', $str, -1, PREG_SPLIT_NO_EMPTY);
        srand ((float)microtime()*1000000);
        shuffle ($array_mix);
        return implode("", $array_mix);
    }

    protected function responseImage($code)
    {
        if (!function_exists('php_sapi_name') || php_sapi_name() !== 'cli') {
            // set headers, unless we're in CLI (e.g. unit tests)
            header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
            header("Last-Modified: " . gmdate("D, d M Y H:i:s", 10000) . " GMT");
            header("Cache-Control: no-store, no-cache, must-revalidate");
            header("Cache-Control: post-check=0, pre-check=0", false);
            header("Pragma: no-cache");
            header("Content-Type:image/png");
        }

        $data_path = dirname(__FILE__).'/data/';

        $font_size = $this->options['font_size'];
        $font = $this->options['fonts'][rand(0, sizeof($this->options['fonts']) - 1)];
        if ($this->options['background']) {
            $img_background = $this->options['background'][rand(0, sizeof($this->options['background']) - 1)];
            $im = imagecreatefrompng ($data_path . $img_background);
        } else {
            $w = $this->options['width'];
            $h = $this->options['height'];;
            $im = imagecreatetruecolor($w, $h);
            for ($i = 0; $i < $w; $i++) {
                for ($j = 0; $j < $h; $j++) {
                    $color = imagecolorallocate($im, rand(100, 255), rand(100, 255), rand(100, 255));
                    imagesetpixel($im, $i, $j, $color);
                }
            }
        }
        $linenum = rand(2, 3);

        for ($i=0; $i < $linenum; $i++)
        {
            $color = imagecolorallocate($im, rand(0, 150), rand(0, 100), rand(0, 150));
            imageline($im, rand(0, 20), rand(1, 50), rand(150, 180), rand(1, 50), $color);
        }

        $ix = floor($this->options['font_size'] * 15 / 25);
        $min_y = round($this->options['height'] * 0.75);
        $max_y = round($this->options['height'] * 0.88);
        $x = rand(0, $ix);
        for($i = 0; $i < strlen($code); $i++) {
            $x += $ix;
            $letter = substr($code, $i, 1);
            if (rand(0, 10) < 5) {
                $letter = strtoupper($letter);
            }
            $color = imagecolorallocate($im, rand(0, 150), rand(0, 150), rand(0, 150));
            imagettftext($im, $font_size, rand(-10, 10), $x, rand($min_y, $max_y), $color, $data_path.$font, $letter);
        }

        for ($i=0; $i<$linenum; $i++)
        {
            $color = imagecolorallocate($im, rand(0, 255), rand(0, 200), rand(0, 255));
            imageline($im, rand(0, 20), rand(1, $this->options['height']), rand(150, $this->options['width']), rand(1, $this->options['height']), $color);
        }

        imagepng($im);
        imagedestroy($im);
    }
}
