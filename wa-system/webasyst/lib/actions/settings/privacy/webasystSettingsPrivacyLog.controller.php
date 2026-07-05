<?php

class webasystSettingsPrivacyLogController extends waController
{
    public function execute()
    {
        $include_document_text = waRequest::get('include_document_text', false, waRequest::TYPE_INT);
        $agreement_log_model = new waAgreementLogModel();
        $last_modified = $agreement_log_model->getLastModified() ?: date('Y-m-d');
        $log_result_set = $agreement_log_model->getLogResultSet($include_document_text);

        $response = wa()->getResponse();
        $response->setStatus(200);
        //$response->addHeader("Content-Length", mb_strlen($result));
        $response->addHeader("Cache-Control", "no-cache, must-revalidate");
        $response->addHeader("Content-type", "text/csv; charset=utf-8");
        $response->addHeader("Content-Disposition", "attachment; filename=\"agreement_log.csv\"");
        $response->addHeader("Last-Modified", strtotime($last_modified));
        $response->sendHeaders();

        $out = fopen('php://output', 'w');
        $fields = [
            'create_datetime', 
            'app_id', 
            'contact_id', 
            'contact_name',
            'contact_email',
            'contact_phone',
            'ip', 
            'user_agent', 
            'context', 
            'document_name', 
            'accept_method', 
            'domain', 
            'form_url',
        ];
        
        if ($include_document_text) {
            array_splice($fields, 10, 0, ['document_text']);
        }

        $delimiter = waRequest::get('delimiter', ',');
        $enclosure = waRequest::get('enclosure', '"');
        fputcsv($out, $fields, $delimiter, $enclosure, '');

        foreach ($log_result_set as $row) {
            $data = [];
            foreach ($fields as $field) {
                $data[] = $row[$field];
            }
            fputcsv($out, $data, $delimiter, $enclosure, '');
        }

        fclose($out);
    }
}