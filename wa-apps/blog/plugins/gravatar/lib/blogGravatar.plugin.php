<?php

class blogGravatarPlugin extends blogPlugin
{
    public function commentsPrepare(&$comments)
    {
        $default = $this->getSettingValue('default');
        if ($default == 'custom') {
            $default = wa()->getConfig()->getHostUrl() .waContact::getPhotoUrl(0, false,20);
        }

        $scheme = parse_url(wa()->getRootUrl(true),PHP_URL_SCHEME);
        foreach ($comments as &$comment) {
            if (isset($comment['user']) && !$comment['contact_id'] && (!$comment['auth_provider'] || ($comment['auth_provider'] == blogCommentModel::AUTH_GUEST)) && $comment['email']) {
                $md5 = md5(strtolower($comment['email']));
                $comment['user']['photo_url'] = "http://www.gravatar.com/avatar/{$md5}?size=20&amp;default={$default}";
                foreach ($comment['user'] as $field => &$data) {
                    if (preg_match('/^photo_url_([\d]+)$/',$field, $matches)) {
                        $data = "{$scheme}://www.gravatar.com/avatar/{$md5}?size={$matches[1]}&amp;default={$default}";
                    }
                }
                unset($data);
            }
            unset($comment);
        }
    }
}
