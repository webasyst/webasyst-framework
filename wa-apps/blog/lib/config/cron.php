<?php
/**
 * Cron cli tasks description
 */
return array(
	//run every 30 minutes
	'scheduled_posts'=>'php '.wa()->getConfig()->getPath('root').DIRECTORY_SEPARATOR.'cli.php blog cronSchedule',
);