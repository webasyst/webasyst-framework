<?php
//
// This file contains default application settings.
//
// DO NOT MODIFY THIS FILE. Installer will overwrite your modifications when you update this app.
// Instead, create a custom config here:
//
// wa-config/apps/mailer/config.php
//
// Values in that config overwrite values from here.
//
return array(
    // Number of messages to process from return-path
    // when called from CLI (e.g. by a cron), and from background JS task
    'returnpath_check_limit_cli' => 2000,
    'returnpath_check_limit_web' => 50,

    // Options for sending speeds selector.
    // Number per hour => array(
    //  'name' => human-readable string (will be wrapped in _w() call for localization)
    //  'disabled' => boolean (for non-selectable options, e.g. a heading)
    // )
    'sending_speeds' => array(
        '' => array( 'name' => 'as fast as possible' ),
        '3600' => array( 'name' => '60 messages per minute' ),
        '1200' => array( 'name' => '20 messages per minute' ),
        '600' => array( 'name' => '10 messages per minute' ),
        '300' => array( 'name' => '5 messages per minute' ),
        '60' => array( 'name' => '1 message per minute' ),
    ),

    //
    // Bounce types.
    // When processing bounce messages from return-paths, Mailer will try to locate
    // these strings in bounce text one by one. The first match will be saved as bounce reason.
    //
    // Some bounce reasons are considered fatal errors: addresses that return such errors are marked as unavailable
    // in wa_contact_emails.status, and no attempts is made to send to such addresses in future campaings.
    // Errors are fatal by default.
    //
    // Human-readable name => array(
    //   'regex' => preg_match() pattern to match bounce text against (defaults to '~%human-readable-name%~i')
    //   'fatal' => bool, defaults to true
    // )
    //
    'bounce_types' => array(

        //
        // Fatal
        //
        'Address incorrect or does not exist' => array(
            'fatal' => true,
            'regex' => "~   User\snot\sfound
                        |   invalid\smailbox
                        |   Unrouteable\saddress
                        |   Address\srejected
                        |   Mailbox\sunavailable
                        |   User\sunknown
                        |   No\ssuch\suser
                        |   The\semail\saccount\sthat\syou\stried\sto\sreach\sdoes\snot\sexist
                        |   Recipient\srejected
                        |   Invalid\srecipient
                        |   No\smailbox\shere\sby\sthat\sname
                        |   Unknown\suser
                        |   Bad\sdestination\smailbox
                        |   Recipient\sunknown
                        |   Unknown\srecipient
                        |   No\ssuch\saddress
                        |   All\srelevant\sMX\srecords\spoint\sto\snon-existent\shosts
                        |   Unresolvable\saddress
                        |   account\shas\sbeen\sdisabled\sor\sdiscontinued
                        |   This\suser\sdoesn't\shave.{1,64}account
                        |   RESOLVER\.ADR\.RecipNotFound
                        |   Mailbox.{0,96}does\snot\sexist
                        |   ukr\.net>\snot\sused
                        |   Mailbox\snot\savailable
                        |   550\sHost\sunknown
                        |   Account\sblocked\sdue\sto\sinactivity
                        |   In\smy\smailserver\snot\sstored\sthis\suser
                        |   Not\sour\sCustomer
                        |   Mailbox\sis\sfrozen
                        |   Recipient\sverify\sfailed
                        |   No\ssuch\se-mail\sin\ssystem
                        ~xi",
        ),

        //
        // Non-fatal
        //
        'Your message qualified as spam and declined' => array(
            'regex' => "~   550.{1,200}spam
                        |   spam.{1,200}550
                        |   email\sabuse\sreport\sfor\san\semail\smessage
                        ~xi",
            'fatal' => false,
        ),

        'Mailbox quota exceeded' => array(
            'fatal' => false,
            'regex' => "~   Mailbox\sis\sfull
                        |   Mailbox\squota\sexceeded
                        ~xi",
        ),
        'Retry timeout exceeded' => array(
            'fatal' => false,
        ),
        'Relay access denied' => array(
            'fatal' => false,
            'regex' => "~   Relay\saccess\sdenied
                        |   Relay\snot\spermitted
                        ~xi",
        ),
    ),
);

