<?php

class waAgreementDocumentModel extends waModel
{
    protected $table = 'wa_agreement_document';

    public function getDocumentId($document_name, $document_text, $locale, $app_id, $context, $domain) {
        $sql = "SELECT * FROM `{$this->table}` WHERE `document_name` = :document_name AND `locale` = :locale AND `app_id` = :app_id AND `context` = :context AND `domain` = :domain ORDER BY `id` DESC LIMIT 1";
        
        $record = $this->query($sql, [
            'document_name' => $document_name, 
            'locale' => $locale, 
            'app_id' => $app_id, 
            'context' => $context, 
            'domain' => $domain,
        ])->fetchAssoc();

        if (!empty($record) && $record['document_text'] == $document_text) {
            return $record['id'];
        }

        $id = $this->insert([
            'document_name' => $document_name,
            'document_text' => $document_text,
            'locale' => $locale, 
            'app_id' => $app_id, 
            'context' => $context, 
            'domain' => $domain,
        ]);

        return $id;
    }
}