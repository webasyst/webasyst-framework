<?php

return array(
    'params' => array(
        'url_type' => array(
            'name' => _w('URLs'),
            'type' => 'radio_select',
            'items' => array(
                0 => array(
                    'name' => _w('Without /album/'),
                    'description' => _w("<br>Photo URLs: with prefix 'photo' <strong>/photo/photo_url/</strong>, e.g. /photo/DSC_2051/<br>Album URLs: without prefix 'album' <strong>/&lt;full_path_to_album&gt;/</strong>, e.g. /travel/france/2010/"),
                ),
                1 => array(
                    'name' => _w('With /album/'),
                    'description' => _w("<br>Photo URLs: without prefix 'photo' <strong>/photo_url/</strong>, e.g. /DSC_2051/<br>Album URLs: with prefix 'album' <strong>/album/&lt;full_path_to_album&gt;/</strong>, e.g. /album/travel/france/2010/")
                ),
            )
        ),
        'title' => array(
            'name' => _w('Homepage title'),
            'type' => 'radio_text',
            'description' => '',
            'items' => array(
                array(
                    'name' => wa()->accountName(),
                    'description' => _ws('Company name')
                ),
                array(
                    'name' => _w('As specified'),
                ),
            ),
        )
    ),
    'vars' => array(
        '$wa' => array(
            '$wa->photos->tags()' => _w('Returns entire tag list as an array with the following structure: (<em>"id"</em>, <em>"name"</em>, <em>"count"</em>, <em>"size"</em>, <em>"opacity"</em>)'),
            '$wa->photos->photo(<em>photo_id</em>[,<em>size</em>])' => _w('Returns photo by id (<em>photo_id</em>) as an array with the following structure: (<em>"id"</em>, <em>"name"</em>, <em>"description"</em>, <em>"ext"</em>, <em>"size"</em>, <em>"type"</em>, <em>"rate"</em>, <em>"width"</em>, <em>"height"</em>, <em>"contact_id"</em>, <em>"upload_datetime"</em>, <em>"edit_datetime"</em>, <em>"status"</em>, <em>"hash"</em>, <em>"url"</em>, <em>"parent_id"</em>, <em>"stack_count"</em>, <em>"sort"</em>, <em>"thumb_%size%"</em>). Optional <em>size</em> parameter can be used to fetch particular thumbnail size: should be provided in pixels, or as one of the predefined values: <em>"big"</em> for 970, <em>"middle"</em> for 750, <em>"thumbs"</em> for 200x0, <em>"crop"</em> for 96x96'),
            '$wa->photos->photos(<em>search_conditions</em>[,<em>size</em>[, <em>limit</em>]])' => _w('Returns photo list array by search criteria, e.g. <em>"tag/vacations"</em>, <em>"album/12"</em>, <em>"id/1,5,7"</em>. <em>size</em> parameter indicates thumbnail size. <em>limit</em> parameter is MySQL-like: can be either a number (max number of photos to be returned) or a pair of offset, limit (start from and the max number of records to be returned)'),
        ),
        'album.html' => array(
            '$album.id' => _w('Photo album id. Other elements of <em>$album</em> available in this template are listed below'),
            '$album.parent_id' => '',
            '$album.type' => '',
            '$album.name' => '',
            '$album.note' => '',
            '$album.description' => '',
            '$album.url' => '',
            '$album.full_url' => '',
            '$album.status' => '',
            '$album.conditions' => '',
            '$album.create_datetime' => '',
            '$album.contact_id' => '',
            '$album.thumb' => '',
            '$album.sort' => '',
            '$album.CUSTOM_PARAM' => _w('The way to use album custom parameters which can be set for every individual album in its settings (<em>key=value</em> format)'),
            '$breadcrumbs' => array(
                '$name' => '',
                '$full_url' => ''
            )
        ),
        'index.html' => array(
            '$content' => _w('Core content loaded according to the requested resource: an album, a photo, a page, etc.'),
        ),
        'photo.html' => array(
            '$photo.id' => _w('Photo id. Other elements of <em>$photo</em> available in this template are listed below'),
            '$photo.name' => '',
            '$photo.description' => '',
            '$photo.ext' => '',
            '$photo.size' => '',
            '$photo.type' => '',
            '$photo.rate' => '',
            '$photo.width' => '',
            '$photo.height' => '',
            '$photo.contact_id' => '',
            '$photo.upload_datetime' => '',
            '$photo.edit_datetime' => '',
            '$photo.status' => '',
            '$photo.url' => '',
            '$photo.parent_id' => '',
            '$photo.stack_count' => '',
            '$photo.sort' => '',
            '$photo.photo_url' => '',
            '$album' => _w('Conditional! Available only if current context of photo is album. Below are describe keys of this param'),
            '$album.id' => '',
            '$album.parent_id' => '',
            '$album.type' => '',
            '$album.name' => '',
            '$album.note' => '',
            '$album.description' => '',
            '$album.url' => '',
            '$album.full_url' => '',
            '$album.status' => '',
            '$album.conditions' => '',
            '$album.create_datetime' => '',
            '$album.contact_id' => '',
            '$album.thumb' => '',
            '$album.sort' => '',
            '$album.CUSTOM_PARAM' => _w('The way to use album custom parameters which can be set for every individual album in its settings (<em>key=value</em> format)'),
            '$next_photo_url' => _w('URL of next photo in the photo stream'),
            '$photo_stream' => _w('Rendered photo stream navigation widget'),
            '$albums' => _w("Rendered HTML block of links to albums which current photo belongs to"),
            '$tags' => _w('Rendered HTML block of current photo tags'),
            '$exif' => _w('Rendered HTML block of current photo EXIF data'),
            '$author' => _w("Rendered HTML block of current photo author info"),
        ),
        'search.html' => array(
            '$title' => ''
        ),
        'view-plain.html' => array(
            '$photos' => array(
                '$id' => '',
                '...' => _w('Available vars are listed in the cheat sheet for photo.html template')
            )
        ),
        'view-thumbs.html' => array(
            '$photos' => array(
                '$id' => '',
                '...' => _w('Available vars are listed in the cheat sheet for photo.html template')
            )
        ),
    )
);