<?php

class waAgreementLogModel extends waModel
{
    protected $table = 'wa_agreement_log';

    public function getLastModified()
    {
        return $this->query("SELECT MAX(create_datetime) FROM `{$this->table}`")->fetchField();
    }

    public function getLogResultSet($include_document_text = false, $app_id = null, $context = null, $domain = null, $document_name = null, $start_date = null)
    {
        $where = ['1=1'];
        $conditions = [];

        if (!empty($document_name)) {
            $where[] = 'document_name=:document_name';
            $conditions['document_name'] = $document_name;
        }

        if (!empty($start_date)) {
            $where[] = 'create_datetime>=:start_date';
            $conditions['start_date'] = $start_date;
        }

        if (!empty($app_id)) {
            $where[] = 'app_id=:app_id';
            $conditions['app_id'] = $app_id;
        }

        if (!empty($context)) {
            $where[] = 'context=:context';
            $conditions['context'] = $context;
        }

        if (!empty($domain)) {
            $where[] = 'domain=:domain';
            $conditions['domain'] = $domain;
        }

        $where = implode(' AND ', $where);

        $select = $include_document_text ? ', d.document_text' : '';
        $join = $include_document_text ? 'LEFT JOIN wa_agreement_document d ON l.document_id=d.id' : '';
        
        $sql = "SELECT l.*, c.name contact_name, e.email contact_email, p.value contact_phone{$select}
            FROM `{$this->table}` l 
            LEFT JOIN wa_contact c ON l.contact_id=c.id 
            LEFT JOIN wa_contact_emails e ON c.id=e.contact_id AND e.sort=0
            LEFT JOIN wa_contact_data p ON c.id=p.contact_id AND p.field='phone' AND p.sort=0
            {$join}
            WHERE {$where} 
            ORDER BY create_datetime ASC";
        return $this->query($sql, $conditions);
    }
}