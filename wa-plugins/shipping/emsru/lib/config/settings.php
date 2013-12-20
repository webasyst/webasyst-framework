<?php
return array(
    'region'    => array(
        'title'        => 'Регион',
        'description'  => 'Выберите регион (область, край, округ, республику) пункта отправления, из которого осуществляется доставка.',
        'control_type' => waHtmlControl::SELECT,
    ),
    'city'      => array(
        'title'        => 'Город',
        'description'  => 'Введите название города пункта отправления. ВАЖНО: Убедитесь, что указанное вами название города присутствует в списке городов, доставка между которыми может быть автоматически рассчитана с помощью API службы «EMS Почта России»: <a href="http://emspost.ru/api/rest/?method=ems.get.locations&type=cities&plain=true" target="_blank">http://emspost.ru/api/rest/?method=ems.get.locations&type=cities&plain=true</a> (регистр при вводе названия города не имеет значения).',
        'control_type' => waHtmlControl::INPUT,
    ),
    'surcharge' => array(
        'value'        => 1,
        'title'        => 'Надбавка (%)',
        'description'  => 'Указанный процент от общей стоимости отправления будет прибавлен к стоимости доставки.',
        'control_type' => waHtmlControl::INPUT,
    ),
);
