<?php

/*
 * This file is part of Webasyst framework.
 *
 * Licensed under the terms of the GNU Lesser General Public License (LGPL).
 * http://www.webasyst.com/framework/license/
 *
 * @link http://www.webasyst.com/
 * @author Webasyst LLC
 * @copyright 2011 Webasyst LLC
 * @package wa-system
 * @subpackage database
 */
class waNestedSetModel extends waModel
{

    protected $left = 'left';
    protected $right = 'right';
    protected $depth = 'depth';
    protected $parent = 'parent';
    protected $root = null;

    public function getTableLeft()
    {
        return $this->left;
    }

    public function getTableRight()
    {
        return $this->right;
    }

    public function getTableDepth()
    {
        return $this->depth;
    }

    public function getTableParent()
    {
        return $this->parent;
    }
    
    public function getRoot()
    {
        return $this->root;
    }
    
    /**
     *
     * Query for getting descendants
     *
     * @param int|null $parent_id
     * @param boolean $include_parent
     * @return waDbQuery
     */
    public function descendants($parent_id, $include_parent = false)
    {
        $query = new waDbQuery($this);

        if ($parent_id) {
            $parent_id = (int)$parent_id;
            $parent = $this->getById($parent_id);
            if (!$parent) {
                return $query->where('id = '.$parent_id);
            }
        }
        $op = !$include_parent ? array('>', '<') : array('>=', '<=');
        if ($parent) {
            $where = "
                `{$this->left}`  {$op[0]} {$parent[$this->left]} AND
                `{$this->right}` {$op[1]} {$parent[$this->right]}
            ";
            if ($this->root) {
                $where .= " AND `{$this->root}` = {$parent[$this->root]}";
            }
            $query->where($where);
        }
        return $query;
    }
    
    /**
     * Insert new item on some level (parent_id) before some item (before_id)
     * @param array $data
     * @param int $parent_id If null than rool level
     * @param int|null $before_id If null than place at the end of level
     * @return boolean
     */
    public function add($data, $parent_id = null, $before_id = null)
    {
        $id = $this->_add($data, $parent_id);
        if (!$id) {
            return false;
        }
        if ($before_id && !$this->moveUp($id, $before_id)) {
            return false;
        }
        return $id;
    }
    
    public function move($id, $parent_id = null, $before_id = null)
    {
        if (!$this->_move($id, $parent_id)) {
            return false;
        }
        if ($before_id) {
            return $this->moveUp($id, $before_id);
        }
        return true;
    }

    /**
     * Insert new item on some level (parent_id)
     * @param array $data
     * @param int $parent_id If null than root level
     * @return id|boolean
     */
    protected function _add($data, $parent_id = null)
    {
        if (($parent_id === null) && !empty($data[$this->parent])) {
            $parent_id = $data[$this->parent];
        }
        $parent_id = (int) $parent_id;
        
        if ($parent_id) {
            // get parent's right value
            $result = $this->getById($parent_id);
            if (!$result) {
                return false;
            }
            $right = $result[$this->right];

            // move next elements' right to make room
            $this->exec("UPDATE `{$this->table}`
                         SET `{$this->right}` = `{$this->right}` + 2
                         WHERE `{$this->right}` >= i:right".($this->root ? " AND {$this->root} = i:root" : ''),
                         array('right' => $right, 'root' => $this->root ? $result[$this->root] : null));
            // move next elements' left
            $this->exec("UPDATE `{$this->table}`
                         SET `{$this->left}` = `{$this->left}` + 2
                         WHERE `{$this->left}` > i:left".($this->root ? " AND {$this->root} = i:root" : ''),
                         array('left' => $right, 'root' => $this->root ? $result[$this->root] : null));
            $data = array_merge($data, array(
                $this->left  => $right,
                $this->right => $right + 1,
                $this->depth => $result[$this->depth] + 1,
                $this->parent => $result[$this->id],
            ));
            if ($this->root && !isset($data[$this->root])) {
                $data[$this->root] = $result[$this->root];
            }
            return $this->insert($data);
            
        } else {
            $sql = "SELECT MAX(`{$this->right}`) as max FROM `{$this->table}`";
            if ($this->root) {
                $sql .= " WHERE ".$this->root." = ".(int)$data[$this->root];
            }
            $result = $this->query($sql)->fetchField('max');
            if (false === $result) {
                $result = 0;
            }
            $data = array_merge($data, array(
                $this->left  => $result + 1,
                $this->right => $result + 2,
                $this->depth => 0,
                $this->parent => 0,
            ));
            return $this->insert($data);
        }
    }
    
    /**
     * 
     * @param int $id
     * @param $parent_id
     */
    private function _move($id, $parent_id)
    {
        $element = $this->getById($id);
        $parent = $this->getById($parent_id);
        $left = $element[$this->left];
        $right = $element[$this->right];

        if ($this->root) {
            $root_where = " AND ".$this->root." = ".(int)$element[$this->root];
        } else {
            $root_where = '';
        }

        if ($parent && $parent[$this->left] > $left && $parent[$this->right] < $right) {
            return false;
        }

        $this->updateById($id, array(
            $this->parent  => $parent ? $parent[$this->id] : 0
        ));
        
        $this->exec("
        UPDATE `{$this->table}`
        SET `{$this->depth}` = `{$this->depth}` + i:parent_depth - i:depth + 1
        WHERE
        `{$this->left}` BETWEEN i:left AND i:right".$root_where,
                array(
                    'left' => $left, 
                    'right' => $right, 
                    'parent_depth' => $parent ? $parent[$this->depth] : -1, 
                    'depth' => $element[$this->depth]
                )
        );

        $params = array(
            'left'  => $left,
            'right' => $right,
            'width' => $right - $left + 1
        );

        if (!$parent) { // move element to root level
            $sql = "SELECT MAX($this->right) max FROM {$this->table}";
            if ($this->root) {
                $sql .= " WHERE ".$this->root." = ".(int)$element[$this->root];
            }
            $params['step'] = $this->query($sql)->fetchField('max') - $right;

            $this->exec("
                UPDATE `{$this->table}`
                SET `{$this->left}` = `{$this->left}` + IF(`{$this->left}` BETWEEN i:left AND i:right, i:step, -i:width)
                WHERE `{$this->left}` >= i:left".$root_where, $params);
            $this->exec("
                UPDATE `{$this->table}`
                SET `{$this->right}` = `{$this->right}` + IF(`{$this->right}` BETWEEN i:left AND i:right, i:step, -i:width)
                WHERE `{$this->right}` >= i:left".$root_where, $params);

            return true;
        }

        $parent_left = $parent[$this->left];
        $parent_right = $parent[$this->right];
        $params['parent_left'] = $parent_left;
        $params['parent_right'] = $parent_right;

        // right
        if ($right > $parent_right) {
            $params['step'] = $parent_right - $left;
            $this->exec("
                UPDATE `{$this->table}`
                SET `{$this->left}` = `{$this->left}` + IF(`{$this->left}` BETWEEN i:left AND i:right, i:step, i:width)
                WHERE
                `{$this->left}` >= i:parent_right AND `{$this->left}` <= i:right
            ".$root_where, $params);
            $this->exec("
                UPDATE `{$this->table}`
                SET `{$this->right}` = `{$this->right}` + IF(`{$this->right}` BETWEEN i:left AND i:right, i:step, i:width)
                WHERE
                `{$this->right}` >= i:parent_right AND `{$this->right}` <= i:right
            ".$root_where, $params);
        } // left
        else {
            $params['step'] = $parent_right - $right - 1;
            $this->exec("
                UPDATE `{$this->table}`
                SET `{$this->left}` = `{$this->left}` + IF(`{$this->left}` BETWEEN i:left AND i:right, i:step, -i:width)
                WHERE
                `{$this->left}` >= i:left AND `{$this->left}` < i:parent_right
            ".$root_where, $params);
            $this->exec("
                UPDATE `{$this->table}`
                SET `{$this->right}` = `{$this->right}` + IF(`{$this->right}` BETWEEN i:left AND i:right, i:step, -i:width)
                WHERE
                `{$this->right}` >= i:left AND `{$this->right}` < i:parent_right
            ".$root_where, $params);
        }

        return true;
    }
    
    protected function moveUp($id, $before_id)
    {
            $element = $this->getById($id);
            if (!$element) {
                return false;
            }

            if ($this->root) {
                $root_where = " AND ".$this->root." = ".(int)$element[$this->root];
            } else {
                $root_where = '';
            }
            
            $before = $this->getById($before_id);
            if (empty($before)) {
                return false;
            }
            
            // not in one level (hasn't one parent)
            if ($element[$this->parent] != $before[$this->parent]) {
                return false;
            }
            
            // already moved (element before needed item)
            if ($element[$this->right] == $before[$this->left] + 1) {
                return false;
            }
            
            $width = $element[$this->right] - $element[$this->left] + 1;
            
            $params = array(
                'left'      => $element[$this->left],
                'right'     => $element[$this->right],
                'width'     => $element[$this->left] > $before[$this->left] ? $width : -$width,
                'step'      => $before[$this->left] - $element[$this->left],
                'from_left' => $before[$this->left]
            );
            
            $this->exec("
                UPDATE `{$this->table}`
                SET `{$this->left}` = `{$this->left}` + IF(`{$this->left}` BETWEEN i:left AND i:right, i:step, i:width)
                WHERE `{$this->left}` >= i:from_left AND `{$this->left}` < i:right
            ".$root_where, $params);
            $this->exec("
                UPDATE `{$this->table}`
                SET `{$this->right}` = `{$this->right}` + IF(`{$this->right}` BETWEEN i:left AND i:right, i:step, i:width)
                WHERE `{$this->right}` > i:from_left AND `{$this->right}` <= i:right
            ".$root_where, $params);
                
            return true;
    }


    /**
     * get subtree
     *
     * @param $id int
     * @param $depth int related depth default is unlimited
     */
    public function getTree($id, $depth = null, $where = array())
    {
        $where = (array)$where;
        if (($id = max(0, (int) $id))) {
            $result = $this->getById($id);

            $left  = (int) $result[$this->left];
            $right = (int) $result[$this->right];
        } else {
            $left = $right = 0;
        }
        $sql = "SELECT * FROM `{$this->table}`";
        if($id) {
            $where[] = "`{$this->left}` >= i:left";
            $where[] = "`{$this->right}` <= i:right";
        }
        if ($depth !== null) {
            $depth = max(0, intval($depth));
            if ($id) {
                $depth += (int) $result[$this->depth];
            }
            $where[] = "`{$this->depth}` <= i:depth";
        }
        if($where) {
            $sql .= " WHERE (".implode(') AND (', $where).')';
        }
        $sql .= " ORDER BY `{$this->left}`";

        $tree = $this->query($sql,
        array('left' => $left, 'right' => $right,'depth'=>$depth)
        )->fetchAll($this->id);

        return $tree;
    }

    /**
     * get parent for node
     *
     * @param $id
     */
    public function getParent($id)
    {
        return $this->getByField($this->parent, $id);
    }

    /**
     * get parents's path for node
     *
     * @param $id
     * @param $depth
     */
    public function getPath($id, $depth = null)
    {
        $id = (int) $id;
        $element = $this->getById($id);
        $left = (int) $element[$this->left];

        $limit = '';
        if ($depth) {
            $limit = 'LIMIT '.$depth;
        }

        $result = $this->query("SELECT * FROM `{$this->table}`
                                WHERE `{$this->left}` < i:left AND `{$this->right}` > i:left".
                                ($this->root ? " AND {$this->root} = i:root" : '')."
                                ORDER BY `{$this->depth}` DESC
                                $limit",array('left' => $left, 'root' => $this->root ? $element[$this->root] : null))->fetchAll($this->id);
        return $result;
    }

    /**
     * Delete category with taking into account plenty of aspects
     * @param int
     */
    public function delete($id)
    {
        $id = (int)$id;
        $item = $this->getById($id);
        if (!$item) {
            return false;
        }

        if ($this->root) {
            $root_where = " AND ".$this->root." = ".(int)$item[$this->root];
        } else {
            $root_where = '';
        }

        $left = (int)$item[$this->left];
        $right = (int)$item[$this->right];
        $parent_id = (int)$item[$this->parent];

        $this->deleteById($id);

        // update all descendants (all keys -1, level up)
        $this->exec("UPDATE `{$this->table}`
            SET
              `{$this->right}`  = `{$this->right}` - 1,
              `{$this->left}`   = `{$this->left}`  - 1,
              `{$this->depth}`  = `{$this->depth}` - 1
            WHERE `{$this->left}` > $left AND `{$this->right}` < $right".$root_where);

        // update childrens (change parent)
        $this->exec("UPDATE `{$this->table}`
            SET
              `{$this->parent}` = {$parent_id}
            WHERE `{$this->parent}` = $id".$root_where);

        // update left branch (exclude descendants) (all keys -2)
        $this->exec("UPDATE `{$this->table}`
            SET
              `{$this->right}` = `{$this->right}` - 2,
              `{$this->left}`  = `{$this->left}`  - 2
            WHERE `{$this->left}` > $left AND `{$this->right}` > $right".$root_where);

        // update parent branch (right keys - 2)
        $this->query("UPDATE `{$this->table}`
            SET
              `{$this->right}` = `{$this->right}` - 2
            WHERE `{$this->right}` > $right AND `{$this->left}` < $left".$root_where);

        return true;
    }
    
    /**
     * Repair "broken" nested-set tree. "Broken" is when keys combination is illegal and(or) full_urls is incorrect
     */
    public function repair()
    {
        if ($this->root) {
            $sql = "SELECT `{$this->root}` FROM `{$this->table}`";
            foreach ($this->query($sql)->fetchAll($this->root) as $item) {
                $root_id = $item[$this->root];
                $this->_repair($root_id);
            }
        } else {
            $this->_repair();
        }
    }
    
    private function _repair($root_id = null)
    {
        $tree = array(0 => array('children' => array(), 'url' => ''));
        $parent_ids = array(0);
        $result = true;
        $access_table = array(0 => & $tree[0]);
        while ($parent_ids) {
            $result = $this->query("SELECT * FROM {$this->table}
                WHERE parent_id IN (".implode(',', $parent_ids).")".
                            ($root_id ? " AND {$this->root} = $root_id" : "")."
                ORDER BY `{$this->left}`");
            $parent_ids = array();
            foreach ($result as $item) {
                $parent_id = $item[$this->parent];
                $item['children'] = array();
                $access_table[$parent_id]['children'][$item['id']] = $item;
                $access_table[$item['id']] = &$access_table[$parent_id]['children'][$item['id']];
                $parent_ids[] = $item['id'];
            }
        }
        $this->repairSubtree($access_table[0]);

        foreach ($access_table as $item) {
            if (isset($item['id'])) {
                $id = $item['id'];
                unset($item['children']);
                unset($item['id']);
                $this->updateById($id, $item);
            }
        }
    }

    protected function repairSubtree(&$subtree, $depth = -1, $key = 0)
    {
        $subtree[$this->left] = $key;
        $subtree[$this->depth] = $depth;
        if (!empty($subtree['children'])) {
            foreach ($subtree['children'] as & $node) {
                $key = $this->repairSubtree($node, $depth + 1, $key + 1);
            }
        }
        $subtree[$this->right] = $key + 1;
        return $key + 1;
    }
}