<?php

class waCaptcha extends waAbstractCaptcha
{
    protected $options = array(
        'chars' => 'abdefhknrqstxyz23456789',
        'fonts' => array(
            "DroidSans.ttf"
        ),
        'font_size' => 25, // in pt
        'background' => array()
    );

    public function getHtml()
    {
        $captcha_url = wa()->getRootUrl(false, true).$this->getAppId().'/captcha.php?rid='.uniqid(time());
        $refresh = _ws("Refresh CAPTCHA");
        return <<<HTML
<div class="wa-captcha">
    <p>
        <img class="wa-captcha-img" src="{$captcha_url}" alt="CAPTCHA" title="{$refresh}">
        <strong>&rarr;</strong>
        <input type="text" name="captcha" class="wa-captcha-input" autocomplete="off">
    </p>
    <p>
        <a class="wa-captcha-refresh">{$refresh}</a>
    </p>
    <script type="text/javascript">
    $(function() {
        $('div.wa-captcha .wa-captcha-refresh, div.wa-captcha .wa-captcha-img').click(function(){
            var div = $(this).parents('div.wa-captcha');
            var captcha = div.find('.wa-captcha-img');
            if(captcha.length) {
                captcha.attr('src', captcha.attr('src').replace(/\?.*$/,'?rid='+Math.random()));
                captcha.one('load', function() {
                    div.find('.wa-captcha-input').focus();
                });
            };
        });
    });
    </script>
</div>
HTML;
    }

    public function isValid($code = null)
    {
        if ($code === null) {
            $code = waRequest::post('captcha');
        }
        $code = strtolower(trim($code));
        $captcha = wa()->getStorage()->get('captcha');
        $app_id = $this->getAppId();
        if (isset($captcha[$app_id]) && $captcha[$app_id] === $code) {
            unset($captcha[$app_id]);
            wa()->getStorage()->set('captcha', $captcha);
            return true;
        } else {
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
        // set headers
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s", 10000) . " GMT");
        header("Cache-Control: no-store, no-cache, must-revalidate");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
        header("Content-Type:image/png");

        $data_path = dirname(__FILE__).'/data/';

        $font_size = $this->options['font_size'];
        $font = $this->options['fonts'][rand(0, sizeof($this->options['fonts']) - 1)];
        if ($this->options['background']) {
            $img_background = $this->options['background'][rand(0, sizeof($this->options['background']) - 1)];
            $im = imagecreatefrompng ($data_path . $img_background);
        } else {
            $w = 120;
            $h = 40;
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

        $color = imagecolorallocate($im, rand(0, 150), rand(0, 150), rand(0, 150));
        $x = rand(0, 15);
        for($i = 0; $i < strlen($code); $i++) {
            $x += 15;
            $letter = substr($code, $i, 1);
            if (rand(0, 10) < 5) {
                $letter = strtoupper($letter);
            }
            imagettftext ($im, $font_size, rand(2, 4), $x, rand(30, 35), $color, $data_path.$font, $letter);
        }

        for ($i=0; $i<$linenum; $i++)
        {
            $color = imagecolorallocate($im, rand(0, 255), rand(0, 200), rand(0, 255));
            imageline($im, rand(0, 20), rand(1, 50), rand(150, 180), rand(1, 50), $color);
        }

        imagepng($im);
        imagedestroy($im);
    }
}
