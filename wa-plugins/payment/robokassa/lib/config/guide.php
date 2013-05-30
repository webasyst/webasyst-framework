<?php
return array(
    array(
        'value'       => '%RELAY_URL%?transaction_result=result',
        'title'       => 'Result URL',
        'description' => 'Адрес отправки оповещения о платеже. <strong>Указанный в этом поле адрес скопируйте и сохраните в соответствующем поле внутри вашего аккаунта ROBOkassa.</strong>',
        //$this->getDirectTransactionResultURL('success', array(__FILE__))).'">'
        ),
    array(
        'value'       => '%RELAY_URL%?transaction_result=success&app_id=%APP_ID%',
        'title'       => 'Success URL',
        'description' => 'Адрес страницы с уведомлением об успешно проведенном платеже. <strong>Указанный в этом поле адрес скопируйте и сохраните в соответствующем поле внутри вашего аккаунта ROBOkassa.</strong>',
        // value="'.xHtmlSpecialChars($this->getTransactionResultURL('success', array(__FILE__))).'">'
        ),
    array(
        'value'       => '%RELAY_URL%?transaction_result=failure&app_id=%APP_ID%',
        'title'       => 'Fail URL',
        'description' => 'Адрес страницы с уведомлением о неуспешном платеже. <strong>Указанный в этом поле адрес скопируйте и сохраните в соответствующем поле внутри вашего аккаунта ROBOkassa.</strong>',
        //"'.xHtmlSpecialChars($this->getTransactionResultURL('failure', array(__FILE__))).'">'
        ),

);
