<?php

class waContactPasswordField extends waContactField
{
    public function getHTML($params = array(), $attrs = '')
    {
        if (isset($params['namespace']) && $params['namespace'] == 'profile') {
            $params['value'] = '';
        }
        $value = isset($params['value']) ? $params['value'] : '';

        $disabled = '';
        if (wa()->getEnv() === 'frontend' && isset($params['my_profile']) && $params['my_profile'] == '1') {
            $disabled = 'disabled="disabled"';
        }

        $errors_html = '';
        if (isset($params['validation_errors']) && !empty($params['validation_errors'])) {
            $attrs = preg_replace('~class="~', 'class="error ', $attrs);
            if (false === strpos($attrs, 'class="error')) {
                $attrs .= ' class="error"';
            }
            if (!is_array($params['validation_errors'])) {
                $params['validation_errors'] = array((string)$params['validation_errors']);
            }
            foreach ($params['validation_errors'] as $error_msg) {
                if (is_array($error_msg)) {
                    $error_msg = implode("<br>\n", $error_msg);
                }
                $errors_html .= "\n" . '<em class="errormsg">' . htmlspecialchars($error_msg) . '</em>';
            }
        }

        $password_html = '<input '.$attrs.' '.$disabled.' type="password" name="'.$this->getHTMLName($params).'" value="'.htmlspecialchars($value).'">';

        if (isset($params['add_password_confirm']) && $params['add_password_confirm'] == true) {
            unset($params['add_password_confirm']);

            $password_confirm_id = 'password_confirm';
            $password_confirm = new waContactPasswordField($password_confirm_id, _ws('Confirm password'));
            $params['id'] = $password_confirm_id;
            $password_confirm_html = $password_confirm->getHTML($params, $attrs);

            $password_html = <<<HTML
<p>
    <span class="field"><span>{$this->getName()}</span>{$password_html}</span>
    <span class="field"><span>{$password_confirm->getName()}</span>{$password_confirm_html}</span>
    {$errors_html}
</p>
HTML;
        }

        return $password_html;
    }
}