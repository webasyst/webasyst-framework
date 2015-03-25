<?php

return array(
    new waContactNameField('name', 'Name', array(
        'max_length' => 150, 'storage' => 'info',
        'fconstructor' => 'hidden',
        'required' => true,
        'subfields_order' => array('firstname', 'middlename', 'lastname'),
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

    new waContactHiddenField('company_contact_id', '', array(
        'storage' => 'info', 'type' => 'Hidden'
    )),
    
    new waContactRadioSelectField('sex', 'Gender', array(
        'storage' => 'info',
        'fconstructor' => 'fixed',
        'translate_options' => true,
        'options' => array(
                'm' => 'Male',
                'f' => 'Female',
        ),
    )),

    new waContactStringField('jobtitle', 'Job title', array(
        'max_length' => 50, 'storage' => 'info',
        'fconstructor' => 'fixed',
    )),
    
    new waContactStringField('company', 'Company', array(
        'max_length' => 150, 'storage' => 'info'
    )),
    
    new waContactEmailField('email', 'Email', array(
        'multi' => true, 'storage' => 'email',
        'ext' => array(
            'work' => 'work',
            'personal' => 'personal',
        ),
        'top' => true
    )),
    new waContactBirthdayField('birthday', 'Birthday', array('storage' => 'info', 'prefix' => 'birth')),
    new waContactTextField('about', 'Description', array('storage' => 'info')),
    new waContactPhoneField('phone', 'Phone', array(
        'multi' => true,
        'ext' => array(
            'work' => 'work',
            'mobile' => 'mobile',
            'home' => 'home',
        ),
        'top' => true
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
        'top' => true
    )),

    new waContactStringField('socialnetwork', 'Social network', array(
        'multi' => true,
        'type' => 'SocialNetwork',
        'ext' => array(
            'facebook' => 'Facebook',
            'twitter' => 'Twitter',
            'linkedin' => 'LinkedIn',
            'vkontakte' => 'VKontakte',
        ),
        'formats' => array(
            'top' => new waContactSocialNetworkTopFormatter(),
            'js' => new waContactSocialNetworkJSFormatter()
        ),
        'domain' => array(
            'facebook' => 'facebook.com',
            'vkontakte' => 'vk.com',
            'twitter' => 'twitter.com',
            'linkedin' => null
        )
    )),
    
    new waContactAddressField('address', 'Address', array(
        'multi' => true,
        'ext' => array(
            'work' => 'work',
            'home' => 'home',
            'shipping' => 'shipping',
            'billing' => 'billing',
        )
    )),

    new waContactUrlField('url', 'Website', array(
        'multi' => true,
        'ext' => array(
            'work' => 'work',
            'personal' => 'personal',
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