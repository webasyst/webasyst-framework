<?php

return array(
    'themes' => true,
    'vars' => array(
        'page.html' => array(
        	'$page.name' => _w('Page name'),
			'$page.title' => _w('Page title (&lt;title&gt;)'),
			'$page.content' => _w('Page content'),
			'$page.update_datetime' => _w('Page last update datetime'),
        ),
        '$wa' => array(
            '$wa->site->pages()' => _w('Returns the array of pages associated with the current “Site” app settlement. Each page is an array of ( <em>"id", "name", "title", "url", "create_datetime", "update_datetime", "content"[, "custom_1", "custom_2", …]</em> )'),
			//'$wa->site->pages(false)' => _w('Page list (only id, name, title, url, create_datetime, update_datetime)'),
			'$wa->site->page(<em>id</em>)' => _w('Returns page info (array) by <em>id</em> (int)'),
        ),
        'index.html' => array(
            '$content' => _w('Content'),
            '$meta_keywords' => _w('META Keywords'),
			'$meta_description' => _w('META Description'),
        ),
        'error.html' => array(
            '$error_code' => _w('Error code (e.g. 404)'),
			'$error_message' => _w('Error message'),
        )
    )
);