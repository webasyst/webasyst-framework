<?php

class photosImportWebasystremoteTransport extends photosImportWebasystTransport
{
    public function initOptions()
    {
        $this->options['url'] = array(
            'title' => _wp('Data access URL'),
            'description' =>_wp('For WebAsyst <strong>PHP software</strong> installed on your server:<br /> 1. Download data access script <a href="http://www.webasyst.com/wa-data/public/site/downloads/old-webasyst-export-php.zip">export.php</a>, and upload it to your Webasyst published/ folder via FTP.<br /> 2. Enter the complete URL to this file, which should look like this: <strong>http://YOUR_WEBASYST_ROOT_URL/published/export.php</strong><br /><br /> For <strong>hosted accounts</strong>: get your Secure Data Access URL in your ACCOUNT.webasyst.net backend’s “Account (link in the top right corner) &gt; System Settings” page.'),
            'value'=>'http://',
            'settings_html_function'=>waHtmlControl::INPUT,
        );
        $this->options['login'] = array(
            'title' => _wp('Login'),
            'value'=>'',
            'description' =>_wp('WebAsyst user login'),
            'settings_html_function'=>waHtmlControl::INPUT,
        );
        $this->options['password'] = array(
            'title' => _wp('Password'),
            'value'=>'',
            'description' =>_wp('WebAsyst user password'),
            'settings_html_function'=>waHtmlControl::PASSWORD,
        );
    }

    public function init()
    {

    }

    public function __wakeup()
    {
        $this->dest = new waModel();
    }

    protected function query($sql, $one = true)
    {
        $sql = trim(preg_replace("/^select/is", '', $sql));
        $url = $this->getURL("sql=".($one ? 1 : 0).base64_encode($sql));
        $this->log('URL:'.$url);
        $result = $this->loadURL($url);
        if ($result === false) {
            throw new waException(_wp('Invalid URL, login or password'));
        }
        $result = json_decode($result, true);
        if ($result === null) {
            throw new waException(_wp('Invalid URL, login or password'));
        }
        return $result;
    }


    protected function moveFile($row, $new_path)
    {
        $old_path = 'files/'.$row['PF_ID'].'/'.$row['PL_DISKFILENAME'];
        $this->log($old_path);
        $content = $this->getFile($old_path);
        $this->log($new_path);
        file_put_contents($new_path, $content);
    }

    protected function getFile($path)
    {
        $url = $this->getURL("file=PD".base64_encode($path));
        $this->log('URL:'.$url);
        return $this->loadURL($url);
    }

    protected function loadURL($url)
    {
        return @file_get_contents($url);
    }

    protected function getURL($params)
    {
        $url = $this->options['url']['value'];
        if (strpos($url, '?') === false) {
            $url .= '?';
        } else {
            $url .= '&';
        }
        $url .= 'auth='.base64_encode($this->options['login']['value'].':'.$this->options['password']['value']);
        $url .= '&'.$params;
        return $url;
    }



}