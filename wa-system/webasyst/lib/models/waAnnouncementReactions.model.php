<?php
class waAnnouncementReactionsModel extends waModel
{
    protected $table = 'wa_announcement_reactions';

    public function addReaction($announcement_id, $emoji, $contact_id)
    {
        $this->multipleInsert([[
            'announcement_id' => $announcement_id,
            'contact_id' => $contact_id,
            'reaction' => $emoji,
            'create_datetime' => date('Y-m-d H:i:s'),
        ]], waModel::INSERT_IGNORE);
    }

    public function removeReaction($announcement_id, $emoji, $contact_id)
    {
        $this->deleteByField([
            'announcement_id' => $announcement_id,
            'contact_id' => $contact_id,
            'reaction' => $emoji,
        ]);
    }

    public function getReactionsByAnnouncement($ids)
    {
        if (!$ids) {
            return [];
        }
        $rows = $this->query("SELECT * FROM {$this->table} WHERE announcement_id IN (?)", [$ids]);

        $result = [];
        foreach ($rows as $row) {
            $result[$row['announcement_id']][$row['reaction']][] = (int)$row['contact_id'];
        }
        return $result;
    }
}
