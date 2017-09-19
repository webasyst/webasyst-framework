<?php

class wapatternSMS extends waSMSAdapter
{
    private $url = "https://localshost/sms/send";

    /**
     * @return array
     */
    public function getControls()
    {
        return array(
            'param_1' => array(
                'title'       => 'Param name',
                'description' => 'Param description',
            ),
        );
    }

    /**
     * @param string $to
     * @param string $text
     * @param string $from
     * @return mixed
     */
    public function send($to, $text, $from = null)
    {
        $post = array(
            "param_1" => $this->getOption('param_1'),
            "to"      => $to,
            "text"    => $text,
        );
        if ($from && preg_match('/^[a-z0-9_\.\-]+$/i', $from) && !preg_match('/^[0-9]+$/', $from)) {
            $post['from'] = $from;
        }

        $net = new waNet();
        try {
            $result = $net->query($this->url, $post, waNet::METHOD_POST);
            $this->log($to, $text, $result);
        } catch (waException $ex) {
            $data = sprintf("ERROR: %s\nraw response:\n%s", $ex->getMessage(), $net->getResponse(true));
            $this->log($to, $text, $data);
            $result = false;
        }

        return $result;
    }
}
