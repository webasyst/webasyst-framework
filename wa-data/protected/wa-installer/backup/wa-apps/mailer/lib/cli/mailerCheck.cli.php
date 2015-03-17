<?php

/**
 * /path/to/php /path/to/wa/cli.php mailer check
 *
 * This controller should be called by CRON every once in a while
 * to gather bounces from return-path mailboxes.
 */
class mailerCheckCli extends waCliController
{
    public function execute()
    {
        wao(new mailerChecker(array(
            'limit' => wa('mailer')->getConfig()->getOption('returnpath_check_limit_cli'),
        )))->check();
    }
}

