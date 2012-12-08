<?php

class blogCategory
{
    static $categories = null;
    static function getAll()
    {
        if (self::$categories === null) {
            $category_model = new blogCategoryModel();
            self::$categories = $category_model->get( array() );
        }
        return self::$categories;
    }
}