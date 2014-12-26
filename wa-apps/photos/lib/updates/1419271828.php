<?php
$model = new waModel();
try {
    $model->query("SELECT key_photo_id FROM photos_album WHERE 0");
} catch (waException $e) {
    $model->exec("ALTER TABLE `photos_album` ADD `key_photo_id` INT(11) NULL DEFAULT NULL");
}

// Set cover photo for all static albums
$sql = "SELECT a.id AS album_id,
            (SELECT ap.photo_id FROM photos_album_photos AS ap WHERE ap.album_id=a.id ORDER BY RAND() LIMIT 1)
                AS photo_id
        FROM photos_album AS a
        WHERE a.type=0
            AND key_photo_id IS NULL";
foreach($model->query($sql) as $row) {
    if ($row['photo_id']) {
        $sql = "UPDATE photos_album SET key_photo_id=? WHERE id=?";
        $model->query($sql, array($row['photo_id'], $row['album_id']));
    }
}

// Set cover photo for the first 50 dynamic albums
$sql = "SELECT id FROM photos_album WHERE type=1 AND key_photo_id IS NULL LIMIT 50";
foreach($model->query($sql) as $album) {
    $collection = new photosCollection('album/'.$album['id']);
    $collection->orderBy('RAND(), p.id');
    $photos = $collection->getPhotos("id", 0, 1, false);
    if ($photos) {
        $photo = reset($photos);
        $sql = "UPDATE photos_album SET key_photo_id=? WHERE id=?";
        $model->query($sql, array($photo['id'], $album['id']));
    }
}

