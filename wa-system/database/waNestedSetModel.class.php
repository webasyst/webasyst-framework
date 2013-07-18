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

    /**
     * Add node to tree
     *
     * @param array $data
     * @param int|null $parent
     * @throws waDbException
     * @return bool|int|resource
     */
    public function add($data, $parent = null)
    {
        if (($parent === null) && !empty($data[$this->parent])) {
            $parent = $data[$this->parent];
        }
        $parent = (int) $parent;
        if ($parent) {
            // get parent's right value
            $result = $this->getById($parent);
            if (!$result) {
                throw new waDbException("Parent does not exist");
            }
            $right = $result[$this->right];

            // move next elements' right to make room
            $this->exec("UPDATE `{$this->table}`
                            SET `{$this->right}` = `{$this->right}` + 2
                            WHERE `{$this->right}` >= i:right", array('right' => $right));
            // move next elements' left
            $this->exec("UPDATE `{$this->table}`
                            SET `{$this->left}` = `{$this->left}` + 2
                            WHERE `{$this->left}` > i:left", array('left' => $right));
            $data = array_merge($data, array(
            $this->left  => $right,
            $this->right => $right + 1,
            $this->depth => $result[$this->depth] + 1,
            $this->parent => $result[$this->id],
            ));
            return $this->insert($data);
        }
        else {
            $result = $this->query("SELECT MAX(`{$this->right}`) as max FROM `{$this->table}`")->fetchField('max');
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
     * get subtree
     *
     * @param $id int
     * @param $depth int related depth default is unlimited
     */
    public function getTree($id, $depth = null, $where = array())
    {
        $where = (array)$where;
        if(($id = max(0, (int) $id))) {
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
                                  WHERE `{$this->left}` < i:left AND `{$this->right}` > i:left
                                  ORDER BY `{$this->depth}` DESC
                                  $limit",array('left' => $left))->fetchAll($this->id);
                                  return $result;
    }

    /**
     *
     * @param $id
     * @param $parent
     */
    public function move($id, $parent)
    {
        $parent = $this->getById($parent);
        $element = $this->getById($id);

        if (empty($element) || empty($parent)) {
            return false;
        }

        if ($element[$this->left] > $parent[$this->left] &&
        $element[$this->right] < $parent[$this->right]) {
            // already into
            return false;
        }

        $left = (int) $element[$this->left];
        $right = (int) $element[$this->right];
        $parent_left = (int) $parent[$this->left];
        $parent_right = (int) $parent[$this->right];

        $this->updateById($id, array($this->parent => $parent[$this->id]));
        $this->exec("
                UPDATE `{$this->table}`
                    SET `{$this->depth}` = `{$this->depth}` + i:parent_depth - i:depth + 1
                    WHERE
                        `{$this->left}` BETWEEN i:left AND i:right
            ", array('left' => $left, 'right' => $right, 'parent_depth' => $parent[$this->depth], 'depth' => $element[$this->depth]));

        $params = array(
            'left' => $left,
            'right' => $right,
            'parent_left' => $parent_left,
            'parent_right' => $parent_right,
            'width' => $right - $left + 1,
            'step' => $parent_right - $left,
        );

        // right
        if ($left > $parent_left) {
            $this->exec("
                UPDATE `{$this->table}`
                    SET `{$this->left}` = `{$this->left}` + IF(`{$this->left}` BETWEEN i:left AND i:right, i:step, i:width)
                    WHERE
                        `{$this->left}` >= i:parent_right AND `{$this->left}` <= i:right
            ", $params);
            $this->exec("
                UPDATE `{$this->table}`
                    SET `{$this->right}` = `{$this->right}` + IF(`{$this->right}` BETWEEN i:left AND i:right, i:step, i:width)
                    WHERE
                        `{$this->right}` >= i:parent_right AND `{$this->right}` <= i:right
            ", $params);
        }
        // left
        else {
            $this->exec("
                UPDATE `{$this->table}`
                    SET `{$this->left}` = `{$this->left}` + IF(`{$this->left}` BETWEEN i:left AND i:right, i:step, -i:width)
                    WHERE
                        `{$this->left}` >= i:left AND `{$this->left}` < i:parent_right
            ", $params);
            $this->exec("
                UPDATE `{$this->table}`
                    SET `{$this->right}` = `{$this->right}` + IF(`{$this->right}` BETWEEN i:left AND i:right, i:step, -i:width)
                    WHERE
                        `{$this->right}` >= i:left AND `{$this->right}` < i:parent_right
            ", $params);
        }
        return true;
    }

    /**
     * delete subtree
     * @param $id
     */
    public function delete($id)
    {
        $id = (int) $id;
        $result = $this->getById($id);
        if (!$result) {
            return false;
        }
        $left  = (int) $result[$this->left];
        $right = (int) $result[$this->right];
        // delete subtree
        $this->exec("DELETE FROM `{$this->table}` WHERE `{$this->left}` BETWEEN i:left AND i:right", array('left' => $left, 'right' => $right));
        $width = $right - $left + 1;
        // update right
        $this->exec("UPDATE `{$this->table}`
                        SET `{$this->right}` = `{$this->right}` - i:width
                        WHERE `{$this->right}` > i:right", array('right' => $right, 'width' => $width));
        // update left
        $this->exec("UPDATE `{$this->table}`
                        SET `{$this->left}` = `{$this->left}` - i:width
                        WHERE `{$this->left}` > i:left", array('left' => $right, 'width' => $width));
        return $this;
    }
}