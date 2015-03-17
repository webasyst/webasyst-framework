<?php

class mailerSendersGenerateDkimController extends waJsonController
{
    public function execute()
    {
        // Create the keypair
        $params = array(
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
            'private_key_bits' => 1024,
        );
        $res = openssl_pkey_new($params);

        // Get private key
        openssl_pkey_export($res, $dkim_pvt_key);

        // Get public key
        $pubkey = openssl_pkey_get_details($res);
        $dkim_pub_key = $pubkey['key'];

        $one_string_key = mailerHelper::getOneStringKey($dkim_pub_key);

        $email = trim(waRequest::post('email'));
        $e = explode('@', $email);

        $this->response = array(
            'dkim_pvt_key'   => $dkim_pvt_key,
            'dkim_pub_key'   => $dkim_pub_key,
            'one_string_key' => $one_string_key,
            'sender_domain'  => array_pop($e),
        );
    }
}
