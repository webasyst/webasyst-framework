<?php

return array(
    new waContactNameField('name', 'Name', array(
        'max_length' => 150, 'storage' => 'info',
        'fconstructor' => 'hidden',
        'required' => true,
    )),
    new waContactStringField('title', 'Title', array(
        'max_length' => 50, 'storage' => 'info', 'type' => 'NameSubfield',
        'fconstructor' => 'fixed',
    )),
    new waContactStringField('firstname', 'First name', array(
        'max_length' => 50, 'storage' => 'info', 'type' => 'NameSubfield',
        'fconstructor' => 'fixed',
    )),
    new waContactStringField('middlename', 'Middle name', array(
        'max_length' => 50, 'storage' => 'info', 'type' => 'NameSubfield',
        'fconstructor' => 'fixed',
    )),
    new waContactStringField('lastname', 'Last name', array(
        'max_length' => 50, 'storage' => 'info', 'type' => 'NameSubfield',
        'fconstructor' => 'fixed',
    )),
    new waContactStringField('company', 'Company', array(
        'max_length' => 150, 'storage' => 'info'
    )),
    new waContactEmailField('email', 'Email', array(
        'multi' => true, 'storage' => 'email',
        'ext' => array(
            'work' => 'Work',
            'personal' => 'Personal',
        )
    )),
    new waContactDateField('birthday', 'Birthday', array('storage' => 'info')),
    new waContactTextField('about', 'Description', array('storage' => 'info')),
    new waContactPhoneField('phone', 'Phone', array(
        'multi' => true,
        'ext' => array(
            'work' => 'Work',
            'mobile' => 'Mobile',
            'home' => 'Home',
        )
    )),
    new waContactStringField('im', 'Instant messenger', array(
        'multi' => true,
        'type' => 'IM',
        'ext' => array(
            'icq' => 'ICQ',
            'skype' => 'Skype',
            'jabber' => 'Jabber',
            'yahoo' => 'Yahoo',
            'aim' => 'AIM',
            'msn' => 'MSN',
        ),
        'formats' => array(
                'top' => new waContactIMTopFormatter(),
                'js' => new waContactIMJSFormatter()
        ),
    )),

    new waContactAddressField('address', 'Address', array(
        'multi' => true,
        'ext' => array(
            'work' => 'Work',
            'home' => 'Home',
        )
    )),

    new waContactUrlField('url', 'Website', array(
        'multi' => true,
        'ext' => array(
            'work' => 'Work',
            'personal' => 'Personal',
        ),
    )),

    new waContactLocaleField('locale', 'Language', array(
        'storage' => 'info',
        'defaultOption' => 'Select language',
    )),
    new waContactTimezoneField('timezone', 'Time zone', array(
        'storage' => 'info',
        'defaultOption' => 'Select time zone',
    )),
    new waContactCategoriesField('categories', 'Categories', array(
        'hrefPrefix' => '#/contacts/category/',
        'fconstructor' => 'hidden',
        'hidden' => TRUE
    )),
);

// EOF