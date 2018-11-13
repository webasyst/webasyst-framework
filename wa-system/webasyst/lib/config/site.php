<?php

// Here will be vars for each Email template
$email_template_vars = array();

// Enumerate names of all Email templates
$email_template_names = array(
    'successful_signup',
    'confirm_signup',
    'recovery_password',
    'password',
    'onetime_password'
);

foreach ($email_template_names as $template_name) {

    // template vars
    $vars = waVerificationChannelEmail::getTemplateVars($template_name, true);

    // each var name need to prefix with $
    // for it separate keys and values, prefix each key with $ and than combine arrays back
    $var_names = array_keys($vars);
    $var_values = array_values($vars);
    $var_names = array_map(wa_lambda('$name', 'return \'$\' . $name;'), $var_names);
    $vars = array_combine($var_names, $var_values);

    // vars for each email template put into own section
    $email_template_vars[ 'email_template_' . $template_name ] = $vars;
}

// If you need to add new vars not related with Email templates merge they with $email_template_vars
return array(
    'vars' => $email_template_vars
);
