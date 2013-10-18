<?php

abstract class blogImportPluginWebasystTransport extends blogImportPluginTransport
{


    public function getPosts()
    {
        $sql = '`NID` AS `id` FROM `SC_news_table` ORDER BY `add_date`';
        if ($posts = $this->waQuery($sql, false)) {
            foreach ($posts as &$post) {
                $post = $post['id'];
            }
            unset($post);
        }
        return $posts;
    }


    public function importPost($post_id)
    {
        $this->log(__METHOD__."({$post_id})", self::LOG_DEBUG);
        $locale = 'ru';
        $sql = '`add_date` AS `datetime`, `title_%1$s` AS `title`,`textToPublication_%1$s` AS `text` FROM `SC_news_table` WHERE `NID`=%2$d';
        if ($data = $this->waQuery(sprintf($sql, $locale, $post_id))) {
            $post = array(
                'text'             => $data['text'],
                'title'            => $data['title'],
                'comments_allowed' => true,
                'datetime'         => date("Y-m-d H:i:s", strtotime($data['datetime'])),
                'url'              => $post_id,
                'status'           => blogPostModel::STATUS_PUBLISHED,
            );
            try {
                $post = $this->insertPost($post);
            } catch (waException $ex) {
                $this->log("Error while import post with id [{$post_id}]:\t".$ex->getMessage()."\nraw post:\t".var_export($post, true), self::LOG_WARNING);
            }
        }
        return empty($post['id']) ? null : $post['id'];
    }

    /**
     * @param string $sql
     * @param boolean $limit
     * @return array[]
     */
    abstract protected function waQuery($sql, $limit = true);
}