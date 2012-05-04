<?php
class blogTagPluginModel extends waModel
{
    protected $table = 'blog_tag';

    public function getAllTags($options = array())
    {
        $sql = <<<SQL
SELECT tag.id as id, tag.name as name, COUNT(tag.id) as count
FROM {$this->table} AS tag
LEFT JOIN blog_post_tag ON (blog_post_tag.tag_id = tag.id)
GROUP BY tag.id
SQL;
        $tags = $this->query($sql)->fetchAll('id');
        if ($tags && $options) {
            $default_options = array(
                'max_size'=>10,
                'min_size'=>6,
                'max_opacity'=>100,
                'min_opacity'=>100,

            );
            $options = array_merge($default_options,$options);

            $qty = array();
            foreach ($tags as $id => $tag) {
                $qty[$id] = $tag['count'];
            }
            $max_qty = max(array_values($qty));
            $min_qty = min(array_values($qty));
            $diff = $max_qty - $min_qty;
            $diff = (0 == $diff) ? 1 : $diff;
            $step_size = ($options['max_size'] - $options['min_size'])/($diff);
            $step_opacity = ($options['max_opacity'] - $options['min_opacity'])/($diff);

            foreach ($tags as &$tag) {
                $tag['size'] = ceil($options['min_size'] + (($tag['count'] - $min_qty) * $step_size));
                $tag['opacity'] = number_format((ceil($options['min_opacity'] + (($tag['count'] - $min_qty) * $step_opacity)))/100, 2, '.', '');
            }
            unset($tag);
        }
        return $tags;
    }


    public function search($string, $limit = 5)
    {
        return $this->select('name')->where("name LIKE '%" . $this->escape($string, 'like') . "%'")->limit($limit)->fetchAll('name', true);
    }


    public function getByPost($post_id)
    {
        $sql = <<<SQL
SELECT tag.id as id, tag.name as name
FROM {$this->table} AS tag
LEFT JOIN blog_post_tag ON blog_post_tag.tag_id = tag.id
WHERE blog_post_tag.post_id = :id
SQL;
        return $this->query($sql, array('id'=>$post_id))->fetchAll('id');
    }


    public function getTagByPost($post_ids)
    {
        return $this->getByField($field);
    }


    public function addTag($post_id, $tags)
    {
        if ($tags) {
            $tags_escape = $this->escape($tags);
            $tags_escape = array_map(wa_lambda('$tag', 'return "\'{$tag}\'";'), $tags_escape);
            $tag_installed = $this->select('id, name')->where('name IN ('.implode(",", $tags_escape).')')->fetchAll('id', true);

            $tag_add = array_diff($tags, $tag_installed);

            if ( !empty($tag_add) ) {
                foreach ($tag_add as $tag) {
                    $tag_id = $this->insert(array( 'name'=>$tag ));
                    $tag_installed[$tag_id] = $tag;
                }
            }
            $tag_installed = array_keys($tag_installed);
        }
        else {
            $tag_installed = array();
        }

        $tags_ids = $this->query("SELECT tag_id FROM `blog_post_tag` WHERE `post_id` = i:post_id", array('post_id'=>$post_id))->fetchAll('tag_id');
        $tags_ids = array_keys($tags_ids);

        // delete
        $ids_delete = array_diff($tags_ids, $tag_installed);
        // add
        $ids_add = array_diff($tag_installed, $tags_ids);

        if ( !empty($ids_add) ) {
            $ids = array();
            foreach ($ids_add as $key => $id) {
                $ids[$key] = $post_id.','.$id;
            }
            $data = '('.implode( '),(', $ids).')';
            $this->exec("INSERT INTO `blog_post_tag` (`post_id`, `tag_id`) VALUES {$data}");
        }

        if ( !empty($ids_delete) ) {
            $ids = array();
            foreach ($ids_delete as $key => $id) {
                $ids[$key] = '( post_id='.$post_id.' AND '.'tag_id='.$id.')';
            }
            $data = implode( 'OR', $ids);
            $this->exec("DELETE FROM `blog_post_tag` WHERE {$data}");

            $tag_count = $this->query("
				SELECT tag_id, COUNT(tag_id) as count
				FROM blog_post_tag
				WHERE tag_id IN (".implode(',', $ids_delete).")")->fetchAll('tag_id');

            $empty_tag = array_diff($ids_delete, array_keys($tag_count));
            if ( !empty($empty_tag) ) {
                $this->deleteByField('id', $empty_tag);
            }
        }
    }
}