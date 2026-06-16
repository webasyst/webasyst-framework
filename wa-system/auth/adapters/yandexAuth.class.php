<?php

/**
 * @see: Yandex Documentation
 *
 * http://api.yandex.ru/oauth/doc/dg/concepts/About.xml
 * http://api.yandex.ru/login/doc/dg/concepts/about.xml
 */

class yandexAuth extends waOAuth2Adapter implements waiAuthAdapterOmnipresent
{
    protected $check_state = true;

    public function getRedirectUri()
    {
        $url = $this->getCallbackUrl();
        return 'https://oauth.yandex.ru/authorize?response_type=code&client_id='.$this->app_id.'&redirect_uri='.urlencode($url);
    }

    public function getControlsConfig()
    {
        return array(
            'app_id'     => 'ClientID',
            'app_secret' => 'Client secret',
            'widget_enabled' => [
                'name' => _ws('Выводить JS-виджет авторизации на каждой странице сайта'),
                'type' => waHtmlControl::class,
                'control_type' => waHtmlControl::CHECKBOX,
            ],
            'redirect_uri' => _ws('Redirect URI')
                .'<br><span class="hint">'.
                sprintf(
                    _ws('Default value in case of the empty field: %s'),
                    parent::getCallbackUrl()
                ).'</span>',
            'origin' => _ws('Origin')
                .'<br><span class="hint">'.
                sprintf(
                    _ws('Значение параметра должно быть всегда заполнено и не должно содержать символ *.<br>')._ws('Default value in case of the empty field: %s'),
                    $this->getOrigin()
                ).'</span>',
        );
    }

    public function getAccessToken($code)
    {
        $url = "https://oauth.yandex.ru/token";
        $response = $this->post($url, array(
            "grant_type" => "authorization_code",
            "code" => $code,
            "client_id" => $this->app_id,
            "client_secret" => $this->app_secret,
        ));
        $params = json_decode($response, true);
        if ($params && isset($params['access_token']) && $params['access_token']) {
            return $params['access_token'];
        }
        return null;
    }

    public function getUserData($token)
    {
        $url = "https://login.yandex.ru/info?format=json&oauth_token=".$token;
        $response = $this->get($url);
        if ($response && $response = json_decode($response, true)) {
            $data = array(
                'source' => 'yandex',
                'source_id' => $response['id'],
                'url' => 'http://'.$response['display_name'].'.ya.ru',
                'name' => $response['real_name'],
            );
            if (isset($response['first_name'])) {
                $data['firstname'] = $response['first_name'];
            }
            if (isset($response['last_name'])) {
                $data['lastname'] = $response['last_name'];
            }
            if (!isset($data['firstname']) && !isset($data['lastname'])) {
                $name = explode(' ', $response['real_name'], 3);
                if (count($name) == 1) {
                    $data['firstname'] = $name[0];
                    $data['lastname'] = '';
                } else {
                    $data['firstname'] = $name[0];
                    $data['lastname'] = $name[1];
                }
            }
            if (isset($response['default_email'])) {
                $data['email'] = $response['default_email'];
            }
            return $data;
        }
        return array();
    }

    public function getName()
    {
        if (wa()->getLocale() == 'ru_RU') {
            return 'Яндекс';
        } else {
            return parent::getName();
        }
    }

    protected function getOrigin()
    {
        return rtrim(wa()->getRootUrl(true, true), '/');
    }

    protected function isWidgetEnabled()
    {
        return (bool)$this->getOption('widget_enabled');
    }

    public function renderOmnipresentWidget(): string
    {
        if (!$this->isWidgetEnabled()) return '';
        $client_id = $this->getOption('app_id');
        $redirect_uri = ifempty(ref($this->getOption('redirect_uri')), parent::getCallbackUrl());
        $origin = ifempty(ref($this->getOption('origin')), $this->getOrigin());

        return <<<HTML
<script src="https://yastatic.net/s3/passport-sdk/autofill/v1/sdk-suggest-with-polyfills-latest.js"></script>
<script>
    addEventListener("DOMContentLoaded", () => {
        if (!window.YaAuthSuggest) return;

        YaAuthSuggest.init(
            {
                client_id: "{$client_id}",
                response_type: "token",
                redirect_uri: "{$redirect_uri}"
            },
            "{$origin}"
        ).then(({ handler }) => handler());
    });
</script>
HTML;

    }
}
