<?php

class photosAlbumParamsModel extends waModel
{
    protected $table = 'photos_album_params';

    /**
     * Get custom params of album
     * @param int|array $album_id
     * @return array 
     *  if album_id is int, than array key=>value format
     *  if album_id is array, than array of arrays in album_id => array of key=>value format
     */
    public function get($album_id)
    {
        $params = array();
        $album_params = $this->getByField('album_id', array_map('intval', (array) $album_id), true);
        if (is_numeric($album_id)) {
            foreach ($album_params as $p) {
                $params[$p['name']] = $p['value'];
            }
        } else {
            foreach ($album_params as $p) {
                if (!isset($params[$p['album_id']])) {
                    $params[$p['album_id']] = array();
                }
                $params[$p['album_id']][$p['name']] = $p['value'];
            }            
        }
        return $params;
    }


    /**
     * Set custom params to album
     *
     * @param int $album_id
     * @param array|null $params key=>value format of array or null (to delete all params assigned to album)
     */
    public function set($album_id, $params = array())
    {
        if ($album_id) {

            // remove if params is null
            if (is_null($params)) {
                $this->deleteByField(array(
                    'album_id' => $album_id
                ));
                return;
            }

            // candidate to delete
            $delete_params = $this->get($album_id);

            // accumulate params to add (new params) and update old params
            $add_params = array();
            foreach ($params as $name => $value) {
                if (isset($delete_params[$name])) {
                    // update old param
                    $this->updateByField(array(
                    			'album_id' => $album_id,
                    			'name' => $name
                		    ), array(
                		    	'value' => $value
                            )
                    );
                    // remove from candidate to delete
                    unset($delete_params[$name]);
                } else {
                    // param to add
                    $add_params[] = array(
                        'album_id' => $album_id,
                        'name' => $name,
                        'value' => $value
                    );
                }
            }

            // delete
            foreach ($delete_params as $name => $value) {
                $this->deleteByField(array(
                    'album_id' => $album_id,
                    'name' => $name
                ));
            }

            // add new params
            if ($add_params) {
                $this->multiInsert($add_params);
            }
        }
    }

}