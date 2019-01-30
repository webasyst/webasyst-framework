<?php

$m = new waModel();
try {
    $m->exec("CREATE UNIQUE INDEX `photo_album` ON `photos_album_photos` (`photo_id`, `album_id`)");
} catch (waDbException $e) {

}
try {
    $m->exec("CREATE UNIQUE INDEX `tag_photo` ON `photos_photo_tags` (`tag_id`, `photo_id`)");
} catch (waDbException $e) {

}
