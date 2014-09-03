<?php

$model = new waModel();

// update name of wa_contact

$model->exec("UPDATE wa_contact SET name = TRIM(middlename) WHERE is_company = 0 AND TRIM(lastname) = '' AND TRIM(firstname) = '' AND TRIM(middlename) != ''");

$model->exec("UPDATE wa_contact SET name = TRIM(firstname) WHERE is_company = 0 AND TRIM(lastname) = '' AND TRIM(firstname) != '' AND TRIM(middlename) = ''");

$model->exec("UPDATE wa_contact SET name = CONCAT(TRIM(firstname), ' ', TRIM(middlename)) WHERE is_company = 0 AND TRIM(lastname) = '' AND TRIM(firstname) != '' AND TRIM(middlename) != ''");

$model->exec("UPDATE wa_contact SET name = TRIM(lastname) WHERE is_company = 0 AND TRIM(lastname) != '' AND TRIM(firstname) = '' AND TRIM(middlename) = ''");

$model->exec("UPDATE wa_contact SET name = CONCAT(TRIM(lastname), ' ', TRIM(middlename)) WHERE is_company = 0 AND TRIM(lastname) != '' AND TRIM(firstname) = '' AND TRIM(middlename) != ''");

$model->exec("UPDATE wa_contact SET name = CONCAT(TRIM(lastname), ' ', TRIM(firstname)) WHERE is_company = 0 AND TRIM(lastname) != '' AND TRIM(firstname) != '' AND TRIM(middlename) = ''");

$model->exec("UPDATE wa_contact SET name = CONCAT(TRIM(lastname), ' ', TRIM(firstname), ' ', TRIM(middlename)) WHERE is_company = 0 AND TRIM(lastname) != '' AND TRIM(firstname) != '' AND TRIM(middlename) != ''");

$model->exec("UPDATE wa_contact SET name = TRIM(company)
WHERE TRIM(company) != '' AND (is_company = 1 OR (is_company = 0 AND TRIM(lastname) = '' AND TRIM(firstname) = '' AND TRIM(middlename) = ''))");

$model->exec("UPDATE wa_contact c JOIN wa_contact_emails ce ON c.id = ce.contact_id AND ce.sort = 0 
	SET c.name = TRIM(ce.email) 
	WHERE TRIM(company) = '' AND 
		(is_company = 1 OR (is_company = 0 AND TRIM(lastname) = '' AND TRIM(firstname) = '' AND TRIM(middlename) = ''))");