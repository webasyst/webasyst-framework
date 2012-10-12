<?php

class blogRightConfig extends waRightConfig
{
    const RIGHT_NONE		 = 0;
    const RIGHT_READ		 = 1;
    const RIGHT_READ_WRITE	 = 2;
    const RIGHT_FULL		 = 3;

    const RIGHT_ADD_BLOG	 = 'add_blog';

    const RIGHT_PAGES	 = 'pages';
    const RIGHT_DESIGN	 = 'design';

    public function init()
    {
        $this->addItem(self::RIGHT_ADD_BLOG, _w('Can create new blogs'), 'checkbox');
        $this->addItem(self::RIGHT_PAGES, _ws('Can edit pages'), 'checkbox');
        $this->addItem(self::RIGHT_DESIGN, _ws('Can edit design'), 'checkbox');

        $blog_model = new blogBlogModel();
        $blogs = $blog_model->getAll();

        $items = array();
        foreach ($blogs as $blog) {
            $items[$blog['id']] = $blog['name'];
        }
        $options = array(
        self::RIGHT_NONE		=> _w('No access'),
        self::RIGHT_READ		=> _w('Read only'),
        self::RIGHT_READ_WRITE	=> _w('Read and publish new posts'),
        self::RIGHT_FULL		=> _w('Full access'),
        );
        $control = array(
			'items'		 => $items,
			'position'	 => 'right',
			'options'	 => $options,
        );
        $this->addItem('blog', _w('Blog'), 'selectlist',$control);
        /**
         * @event rights.config
         * @param waRightConfig $this Rights setup object
         * @return void
         */
        wa()->event('rights.config', $this);
    }
}
