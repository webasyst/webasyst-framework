<?php

$ccm = new waContactCategoryModel();

// clean up wa_contact_categories. Delete references to inexisted contacts
$sql = "DELETE ccs.* FROM `wa_contact_categories` ccs JOIN (
  SELECT ccs.contact_id FROM `wa_contact_categories` ccs
  LEFT JOIN `wa_contact` c ON c.id = ccs.contact_id
  WHERE c.id IS NULL
) t ON ccs.contact_id = t.contact_id";
$ccm->exec($sql);

// recalculate category counters
$ccm->recalcCounters();