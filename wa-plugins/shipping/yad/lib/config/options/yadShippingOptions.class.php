<?php

class yadShippingOptions
{
    public static function integrationOptions($adapter)
    {
        return array(
            array(
                'value'       => waShipping::STATE_DRAFT,
                'title'       => 'Создавать и обновлять черновики заказов',
                'description' => '<br>Для каждого заказа в приложении (например, для всех новых заказов в Shop-Script) создаётся черновик заказа в кабинете «Яндекс.Доставки». Этот черновик автоматически обновляется после редактирования заказа в приложении.<br>',
                'disabled'    => !$adapter->getAppProperties(waShipping::STATE_DRAFT),
            ),
            array(
                'value'       => waShipping::STATE_READY,
                'title'       => 'Создавать отгрузки (кроме доставки курьером)',
                'description' => '<br>После окончательного формирования заказа в приложении (например, после выполнения действия «Отправлен» в Shop-Script) черновик в кабинете «Яндекс.Доставки» превращается в сформированный заказ, ожидающий отгрузку.<br>
Заявками на отгрузку и печатью ярлыков управляйте в кабинете «Яндекс.Доставки». Например, в одной заявке можно объединить несколько заказов.<br>
<strong>Не используется, если покупатель выбрал доставку курьером</strong>.<br>',
                'disabled'    => !$adapter->getAppProperties(waShipping::STATE_READY),
            ),
            array(
                'value'       => waShipping::STATE_CANCELED,
                'title'       => 'Отменять отгрузки (кроме доставки курьером)',
                'description' => '<br>Когда вы отменяете заказ в приложении (например, с помощью действия «Удалить» в Shop-Script), отменяется заказ в кабинете «Яндекс.Доставки»:<br>
&nbsp;&nbsp;&nbsp;&nbsp;- Заказ, ожидавший отгрузку, переносится в список «Отмены» в кабинете «Яндекс.Доставки»;<br>
&nbsp;&nbsp;&nbsp;&nbsp;- Черновик не удаляется и остается без изменений, его можно вручную отредактировать или отправить в архив.<br>
<strong>Не используется, если покупатель выбрал доставку курьером</strong>.',
                'disabled'    => !$adapter->getAppProperties(waShipping::STATE_CANCELED),
            ),
        );
    }

    public static function taxesOptions($adapter)
    {
        return array(
            array(
                'value' => 'skip',
                'title' => 'Не передавать ставки НДС',

            ),
            array(
                'value'    => 'no',
                'title'    => 'НДС не облагается',
                'disabled' => !$adapter->getAppProperties('taxes'),
            ),
            array(
                'value'    => 'map',
                'title'    => 'Передавать ставки НДС по каждой позиции',
                'disabled' => !$adapter->getAppProperties('taxes'),
            ),
        );
    }
}
