<?php
return array (
  'states' => 
  array (
    'new' => 
    array (
      'name' => 'Новый',
      'options' => 
      array (
        'style' => 
        array (
          'color' => '#009900',
          'font-weight' => 'bold',
        ),
        'icon' => 'icon16 ss new',
      ),
      'available_actions' => 
      array (
        0 => 'process',
        1 => 'pay',
        2 => 'ship',
        3 => 'delete',
        4 => 'complete',
        5 => 'comment',
        6 => 'change_status',
      ),
      'classname' => 'shopWorkflowState',
    ),
    'processing' => 
    array (
      'name' => 'В обработке',
      'options' => 
      array (
        'style' => 
        array (
          'color' => '#008800',
          'font-style' => 'italic',
        ),
        'icon' => 'new',
      ),
      'available_actions' => 
      array (
        0 => 'pay',
        1 => 'ship',
        2 => 'edit',
        3 => 'delete',
        4 => 'complete',
        5 => 'comment',
      ),
      'classname' => 'shopWorkflowState',
    ),
    'shipment' => 
    array (
      'name' => 'На отправку',
      'options' => 
      array (
        'style' => 
        array (
          'color' => '#FF0000',
        ),
        'icon' => 'icon16 ss flag-red',
      ),
      'available_actions' => 
      array (
      ),
      'classname' => 'shopWorkflowState',
    ),
    'paid' => 
    array (
      'name' => 'Деньги списаны с карты клиента',
      'options' => 
      array (
        'style' => 
        array (
          'color' => '#0b630f',
          'font-weight' => 'bold',
          'font-style' => 'italic',
        ),
        'icon' => 'icon16 ss flag-green',
      ),
      'available_actions' => 
      array (
        0 => 'ship',
        1 => 'refund',
        2 => 'complete',
        3 => 'comment',
      ),
      'classname' => 'shopWorkflowState',
    ),
    'shipped' => 
    array (
      'name' => 'Отправлен',
      'options' => 
      array (
        'style' => 
        array (
          'color' => '#0000ff',
          'font-style' => 'italic',
        ),
        'icon' => 'icon16 ss sent',
      ),
      'available_actions' => 
      array (
        0 => 'edit',
        1 => 'delete',
        2 => 'complete',
        3 => 'comment',
      ),
    ),
    'sent_pre' => 
    array (
      'name' => 'Отправлен по предоплате',
      'options' => 
      array (
        'style' => 
        array (
          'color' => '#FF0099',
        ),
        'icon' => 'icon16 ss sent',
      ),
      'available_actions' => 
      array (
      ),
      'classname' => 'shopWorkflowState',
    ),
    'delivery_cour' => 
    array (
      'name' => 'Вручение курьерской службой',
      'options' => 
      array (
        'style' => 
        array (
          'color' => '#993300',
        ),
        'icon' => 'icon16 ss sent',
      ),
      'available_actions' => 
      array (
      ),
      'classname' => 'shopWorkflowState',
    ),
    'cour' => 
    array (
      'name' => 'Курьерская служба',
      'options' => 
      array (
        'style' => 
        array (
          'color' => '#3366ff',
        ),
        'icon' => 'icon16 ss sent',
      ),
      'available_actions' => 
      array (
      ),
      'classname' => 'shopWorkflowState',
    ),
    'delivery_addr' => 
    array (
      'name' => 'Вручение адресату',
      'options' => 
      array (
        'style' => 
        array (
          'color' => '#00CCCC',
        ),
        'icon' => 'icon16 ss sent',
      ),
      'available_actions' => 
      array (
      ),
      'classname' => 'shopWorkflowState',
    ),
    'sent_cour' => 
    array (
      'name' => 'Передано курьеру',
      'options' => 
      array (
        'style' => 
        array (
          'color' => '#669966',
        ),
        'icon' => 'icon16 ss sent',
      ),
      'available_actions' => 
      array (
      ),
      'classname' => 'shopWorkflowState',
    ),
    'search' => 
    array (
      'name' => 'В розыске',
      'options' => 
      array (
        'style' => 
        array (
          'color' => '#0000cc',
        ),
        'icon' => 'icon16 ss flag-blue',
      ),
      'available_actions' => 
      array (
      ),
      'classname' => 'shopWorkflowState',
    ),
    'tracing' => 
    array (
      'name' => 'Розыск перевода',
      'options' => 
      array (
        'style' => 
        array (
          'color' => '#33CC66',
        ),
        'icon' => 'icon16 ss flag-green',
      ),
      'available_actions' => 
      array (
      ),
      'classname' => 'shopWorkflowState',
    ),
    'postponed' => 
    array (
      'name' => 'Отложен',
      'options' => 
      array (
        'style' => 
        array (
          'color' => '#0099FF',
        ),
        'icon' => 'icon16 ss flag-blue',
      ),
      'available_actions' => 
      array (
      ),
      'classname' => 'shopWorkflowState',
    ),
    'post_office' => 
    array (
      'name' => 'На почтовом отделение клиента',
      'options' => 
      array (
        'style' => 
        array (
          'color' => '#000000',
        ),
        'icon' => 'icon16 ss flag-checkers',
      ),
      'available_actions' => 
      array (
      ),
      'classname' => 'shopWorkflowState',
    ),
    'completed' => 
    array (
      'name' => 'Доставлен и оплачен',
      'options' => 
      array (
        'style' => 
        array (
          'color' => '#e68b2c',
        ),
        'icon' => 'icon16 ss completed',
      ),
      'available_actions' => 
      array (
        0 => 'refund',
        1 => 'edit',
        2 => 'comment',
      ),
      'classname' => 'shopWorkflowState',
    ),
    'back_sam' => 
    array (
      'name' => 'Возвращается в Самару',
      'options' => 
      array (
        'style' => 
        array (
          'color' => '#000066',
        ),
        'icon' => 'icon16 ss refunded',
      ),
      'available_actions' => 
      array (
      ),
      'classname' => 'shopWorkflowState',
    ),
    'refunded' => 
    array (
      'name' => 'Деньги возвращены',
      'options' => 
      array (
        'icon' => 'icon16 ss refunded',
        'style' => 
        array (
          'color' => '#cc0000',
        ),
      ),
      'available_actions' => 
      array (
      ),
      'classname' => 'shopWorkflowState',
    ),
    'deleted' => 
    array (
      'name' => 'Отменен',
      'options' => 
      array (
        'style' => 
        array (
          'color' => '#aaaaaa',
        ),
        'icon' => 'icon16 ss trash',
      ),
      'available_actions' => 
      array (
        0 => 'restore',
      ),
      'classname' => 'shopWorkflowState',
    ),
  ),
  'actions' => 
  array (
    'create' => 
    array (
      'classname' => 'shopWorkflowCreateAction',
      'name' => 'Создать',
      'options' => 
      array (
        'log_record' => 'Заказ оформлен',
      ),
      'state' => 'new',
    ),
    'process' => 
    array (
      'classname' => 'shopWorkflowProcessAction',
      'name' => 'В обработку',
      'options' => 
      array (
        'log_record' => 'Заказ подтвержден и принят в обработку',
        'button_class' => 'green',
      ),
      'state' => 'processing',
    ),
    'pay' => 
    array (
      'classname' => 'shopWorkflowPayAction',
      'name' => 'Оплачен',
      'options' => 
      array (
        'log_record' => 'Заказ оплачен',
        'button_class' => 'yellow',
      ),
      'state' => 'paid',
    ),
    'ship' => 
    array (
      'classname' => 'shopWorkflowShipAction',
      'name' => 'Отправлен',
      'options' => 
      array (
        'log_record' => 'Заказ отправлен',
        'button_class' => 'blue',
      ),
      'state' => 'shipped',
    ),
    'refund' => 
    array (
      'classname' => 'shopWorkflowRefundAction',
      'name' => 'Возврат',
      'options' => 
      array (
        'log_record' => 'Возврат',
        'button_class' => 'red',
      ),
      'state' => 'refunded',
    ),
    'edit' => 
    array (
      'classname' => 'shopWorkflowEditAction',
      'name' => 'Редактировать заказ',
      'options' => 
      array (
        'position' => 'top',
        'icon' => 'edit',
        'log_record' => 'Заказ отредактирован',
      ),
    ),
    'delete' => 
    array (
      'classname' => 'shopWorkflowDeleteAction',
      'name' => 'Отменить',
      'options' => 
      array (
        'icon' => 'delete',
        'log_record' => 'Заказ удален',
      ),
      'state' => 'deleted',
    ),
    'restore' => 
    array (
      'classname' => 'shopWorkflowRestoreAction',
      'name' => 'Восстановить',
      'options' => 
      array (
        'icon' => 'restore',
        'log_record' => 'Заказ восстановлен',
        'button_class' => 'green',
      ),
    ),
    'complete' => 
    array (
      'classname' => 'shopWorkflowCompleteAction',
      'name' => 'Выполнен',
      'options' => 
      array (
        'log_record' => 'Заказ выполнен',
        'button_class' => 'purple',
      ),
      'state' => 'completed',
    ),
    'comment' => 
    array (
      'classname' => 'shopWorkflowCommentAction',
      'name' => 'Добавить комментарий',
      'options' => 
      array (
        'position' => 'bottom',
        'icon' => 'add',
        'button_class' => 'inline-link',
        'log_record' => 'Добавлен комментарий к заказу',
      ),
    ),
    'callback' => 
    array (
      'classname' => 'shopWorkflowCallbackAction',
      'name' => 'Ответ платежной системы (callback)',
      'options' => 
      array (
        'log_record' => 'Ответ платежной системы (callback)',
      ),
    ),
    'action' => 
    array (
      'classname' => 'shopWorkflowAction',
      'name' => 'Действие',
    ),
  ),
);
