<?php

/**
 * Class webasystGetInfoMethod
 *
 * Response
 *  {
 *      "name": <string>,
 *      "backend_url": <string>, - URL to the Webasyst backend, for example - https://vasya.com/webasyst/
 *      "logo": {
 *          "mode": 'gradient' | 'image',
 *          "text": {
 *              "value": <string>
 *              "default_value": <string>
 *              "color": <string>
 *              "default_color:" <string>
 *          },
 *          "two_lines": <bool>
 *          "gradient: {
 *              "from": <string> - hex code of color (without #)
 *              "to": <string> - hex code of color (without #)
 *              "angle": <int>
 *          },
 *          "image": {
 *              "thumbs": { ... },
 *              "original": { ... },
 *          },
 *          "gradients": [ ... ]
 *      }
 *  }
 */
class webasystGetInfoMethod extends waAPIMethod
{
    public function execute()
    {
        $logo = (new webasystLogoSettings([ 'absolute_urls' => true ]))->get();
        unset($logo['gradients']);

        $this->response = [
            'name' => wa()->accountName(),
            'backend_url' => wa()->getRootUrl(true) . wa()->getConfig()->getBackendUrl(false) . '/',
            'logo' => $logo,
        ];
    }
}
