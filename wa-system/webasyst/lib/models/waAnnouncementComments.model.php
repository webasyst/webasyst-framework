<?php
class waAnnouncementCommentsModel extends waModel
{
    protected $table = 'wa_announcement_comments';

    public function getByAnnouncement($id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE announcement_id=? ORDER BY id DESC LIMIT 500";
        return array_reverse($this->query($sql, [$id])->fetchAll('id'), true);
    }

    public function countByAnnouncement($ids=null)
    {
        $where_sql = '';
        if ($ids !== null) {
            if (!$ids) {
                return [];
            }
            $where_sql = ' AND announcement_id IN (?)';
        }
        $sql = "SELECT announcement_id, COUNT(*) FROM {$this->table} WHERE 1=1 $where_sql GROUP BY announcement_id";
        return $this->query($sql, [$ids])->fetchAll('announcement_id', true);
    }
}
