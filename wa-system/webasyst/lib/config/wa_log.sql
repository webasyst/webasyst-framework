INSERT IGNORE INTO wa_log (app_id, contact_id, datetime, action, subject_contact_id, params)
VALUES('webasyst', 0, NOW(), 'welcome', NULL, '');
