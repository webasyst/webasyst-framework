<?php

class photosPublicgalleryVoteModel extends waModel
{
    protected $table = 'photos_publicgallery_vote';
    
    public function filterNotVotedPhotoIds($photo_id)
    {
        $sql = "SELECT p.id AS photo_id, v.id FROM `photos_photo` p 
                LEFT JOIN `photos_publicgallery_vote` v ON p.id = v.photo_id AND 
                    v.contact_id = ".wa()->getUser()->getId()."
            WHERE p.id IN (".implode(',', array_map('intval', (array) $photo_id)).") 
                AND v.id IS NULL";
        return array_keys($this->query($sql)->fetchAll('photo_id'));
    }
    
    public function filterVotedPhotoIds($photo_id)
    {
        $sql = "SELECT p.id AS photo_id, v.id FROM `photos_photo` p 
                JOIN `photos_publicgallery_vote` v ON p.id = v.photo_id AND 
                    v.contact_id = ".wa()->getUser()->getId()."
            WHERE p.id IN (".implode(',', array_map('intval', (array) $photo_id)).")";
        return array_keys($this->query($sql)->fetchAll('photo_id'));
    }
    
    public function vote($photo_id, $rate) {
        if (!wa()->getUser()->getId()) {
            return false;
        }
        if ($rate < 0 || $rate > 5) {
            return false;
        }
        
        $not_voted_photo_id = $this->filterNotVotedPhotoIds($photo_id);

        if (!$not_voted_photo_id) {
            return false;
        }
        
        $data = array(
            'contact_id' => wa()->getUser()->getId(),
            'rate' => $rate,
            'datetime' => date('Y-m-d H:i:s'),
            'ip' => waRequest::getIp(true)
        );
        foreach ($not_voted_photo_id as $id) {
            $data['photo_id'] = $id;
            $this->insert($data);
        }
        
        $this->correctAggregatedRates($not_voted_photo_id);
        
        return true;
    }
    
    public function clearVote($photo_id)
    {
        if (!wa()->getUser()->getId()) {
            return false;
        }
        $voted_photo_id = $this->filterVotedPhotoIds($photo_id);
        if (!$voted_photo_id) {
            return false;
        }
        
        $this->deleteByField(array(
            'photo_id' => $voted_photo_id, 
            'contact_id' => wa()->getUser()->getId())
        );
        
        $this->correctAggregatedRates($voted_photo_id);
        
    }
    
    public function correctAggregatedRates($photo_id)
    {
        $photo_id = (array) $photo_id;
        if ($photo_id) {
            // update calculated values in photos_photo: rate, votes_count
            $sql = "UPDATE `photos_photo` p JOIN
                (SELECT IF(COUNT(v.id) = 0, 0, SUM(v.rate)/COUNT(v.id)) rate, COUNT(v.id) votes_count, p.id 
                    FROM `photos_photo` p LEFT JOIN `photos_publicgallery_vote` v ON p.id = v.photo_id
                    WHERE p.id IN(".implode(',', $photo_id).") 
                    GROUP BY p.id
                ) v ON p.id = v.id
                SET p.rate = v.rate, p.votes_count = v.votes_count";
            $this->exec($sql);
        }
    }
    
    public function getDistribution($photo_id)
    {
        $photo_id = (int) $photo_id;
        $sql = "SELECT rate, COUNT(contact_id) votes_count FROM `{$this->table}` WHERE photo_id = {$photo_id} GROUP BY rate";
        $fetched = $this->query($sql)->fetchAll('rate');
        $votes_count = 0;
        foreach ($fetched as $item) {
            $votes_count += $item['votes_count'];
        }
        $data = array();
        for ($i = 5; $i > 0; $i--) {
            if (isset($fetched[$i])) {
                $data[$i] = $fetched[$i];
                $data[$i]['votes_count_percents'] = round($data[$i]['votes_count'] / $votes_count, 2) * 100;
            } else {
                $data[$i] = array(
                    'rate' => $i,
                    'votes_count' => 0,
                    'votes_count_percents' => 0
                );
            }
        }
        return $data;
    }
    
    public function getVotedUsers($photo_id)
    {
        $photo_id = (int) $photo_id;
        $sql = "SELECT v.rate, v.datetime, c.name, c.photo, c.id 
                FROM `{$this->table}` v 
                JOIN `wa_contact` c ON v.contact_id = c.id
                WHERE v.photo_id = {$photo_id} ORDER BY v.datetime DESC";
        $users = $this->query($sql)->fetchAll('id');
        $contacts_url = wa()->getAppUrl('contacts');
        
        foreach ($users as &$u) {
            $u['photo'] = waContact::getPhotoUrl($u['id'], $u['photo'], 20, 20);
            $u['url'] = $contacts_url.'#/contact/'.$u['id'].'/';
        }
        unset($u);
        
        return $users;
    }
}