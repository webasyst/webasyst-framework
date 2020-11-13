<?php
class waAppTokensModel extends waModel
{
    protected $table = 'wa_app_tokens';
    protected $id = 'token';

    public function add($data)
    {
        if (empty($data['create_datetime'])) {
            $data['create_datetime'] = date('Y-m-d H:i:s');
        }
        if (empty($data['token'])) {
            $data['token'] = self::generateToken();
        }

        $empty_row = $this->getEmptyRow();
        $this->insert(array_intersect_key($data, $empty_row));
        return $data + $empty_row;
    }

    public function purge()
    {
        $this->exec("DELETE FROM {$this->table} WHERE expire_datetime < ?", date('Y-m-d H:i:s'));
    }

    public static function generateToken()
    {
        $alphabet = 'abcdefghijklmnopqrstuvwxyz';
        $alphabet .= strtoupper($alphabet);
        $alphabet .= '0123456789(!-_~*)';
        $max = strlen($alphabet) - 1;
        $result = '';
        for($i = 0; $i < 32; $i++) {
            $result .= $alphabet[mt_rand(0, $max)];
        }
        return $result;
    }

    public static function getLink($token)
    {
        if (is_array($token)) {
            $token = $token['token'];
        }

        // some of symbols - '(', ')', '~' should be encoded
        // see https://tools.ietf.org/html/rfc3986#section-2

        $token = urlencode($token);

        $root_url = wa()->getRootUrl(true);
        $root_url = waIdna::dec($root_url);
        return $root_url.'link.php/'.$token.'/';
    }
}
