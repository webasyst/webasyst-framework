<?php

class photosPublicgalleryPlugin extends photosPlugin
{    
    public function saveSettings($settings = array())
    {
        if (empty($settings['min_size']) || !is_numeric($settings['min_size'])) {
            $settings['min_size'] = '';
        }
        if (empty($settings['max_size']) || !is_numeric($settings['max_size'])) {
            $settings['max_size'] = '';
        }
        if (is_numeric($settings['min_size']) && is_numeric($settings['max_size']) && 
                $settings['min_size'] > $settings['max_size']) 
        {
            list($settings['max_size'], $settings['min_size']) = array($settings['min_size'], $settings['max_size']);
        }
        parent::saveSettings($settings);
    }
    
    public function frontendSidebar()
    {
        return array('menu' => '<a href="'.wa()->getRouteUrl('photos/frontend/myphotos').'" id="photos-my-photos">'._wp('My uploads').'</a>');
    }
    
    public function frontendPhoto($photo)
    {
        if (!isset($photo['id'])) {
            return;
        }
        
        $photo_model = new photosPhotoModel();
        $photo = $photo_model->getById($photo['id']);
        if (!$photo) {
            return;
        }
        
        $votes_count_text = '';
        if ($photo['votes_count'] > 0) {
            $votes_count_text = _wp('%d vote', '%d votes', $photo['votes_count']);
        }
        $frontend_vote_url = wa()->getRouteUrl('photos/frontend/vote');
    
        $vote_model = new photosPublicgalleryVoteModel();
        $vote_item = $vote_model->getByField(array('photo_id' => $photo['id'], 'contact_id' => wa()->getUser()->getId()));
        $your_rate = 0;
        if ($vote_item) {
            $your_rate = $vote_item['rate'];
        }
        
        $sidebar = '<p><span href="javascript:void(0);" id="photo-rate" class="p-rate-photo" title="'._wp('Rate').'" data-rate="'.$photo['rate'].'">'.
                    photosPhoto::getRatingHtml($photo['rate'], 16, true)
                .'</span>'.
                '<span class="hint" id="photo-rate-votes-count" data-you-voted="'.(int)($your_rate > 0).'">'.
                    $votes_count_text
                .'</span></p>';
        $sidebar .= '<p><span class="p-rate" data-vote-url="'.$frontend_vote_url.'">'.
                _wp('Rate this photo:').'<br>'.
                '<a href="javascript:void(0);" id="your-rate" class="p-rate-photo" data-rate="'.$your_rate.'">'.
                    photosPhoto::getRatingHtml($your_rate, 16, true)
                .'</a>'.
                '<em class="error" id="photo-rate-error" style="display:none;"></em>'.
                '<a class="inline-link p-rate-clear small" href="javascript:void(0);" style="display:none;" id="clear-photo-rate"><b><i>'._wp('cancel my vote').'</b></i></a>'.
            '</span></p>';
        $sidebar .= '<script>$(function() { $.photos.publicgalleryInitRateWidget(); });</script>';
        
        $left = '';
        if ($photo['moderation'] == 0) {
            $left .= "<p class='p-awaiting-moderation'>"._wp("Pending moderation")."</p>";
        } else if ($photo['moderation'] == -1) {
            $left .= "<p class='p-declined'>"._wp("Photo has been declined by the administrator")."</p>";
        }
        
        return array('sidebar' => $sidebar, 'top_left' => $left);
    }
    
    public function backendAssets() {
        $this->addJs('js/backend.js?'.wa()->getVersion());
        return "<style>#sidebar-publicgallery-plugin-awaiting { margin-top: 15px; } </style>";
    }
    
    public function frontendAssets() {
        $v = wa()->getVersion();
        $this->addJs('js/frontend.js?'.$v);
        $this->addJs('js/rate.widget.js?'.$v, false);

        $strings = array();
        foreach(array(
            'Empty result', //_w('Empty result')
            'Files with extensions *.gif, *.jpg, *.jpeg, *.png are allowed only.', //_w('Files with extensions *.gif, *.jpg, *.jpeg, *.png are allowed only.')
            'Upload complete! <a href="#">Reload page</a> to view your photos.', //_w('Upload complete! <a href="#">Reload page</a> to view your photos.')
        ) as $s) {
            $strings[$s] = _w($s);
        }
        $view = wa()->getView();
        $view->assign('strings', $strings ? $strings : new stdClass()); // stdClass is used to show {} instead of [] when there's no strings
        return $view->fetch($this->path.'/templates/actions/frontend/FrontendLoc.html');
    }
    
    public function backendSidebar()
    {
        $photo_model = new photosPhotoModel();
        $awaiting_count = $photo_model->countByField(array('moderation' => '0'));
        if (!$awaiting_count) {
            $awaiting_count = 0;
        }
        $declined_count = $photo_model->countByField(array('moderation' => '-1'));
        if (!$declined_count) {
            $declined_count = 0;
        }
        $items = array(

            'awaiting' => '<span class="count '.($awaiting_count ? 'indicator' : '').' red">'.$awaiting_count.'</span><a href="#/search/moderation=0/"><i class="icon16 exclamation"></i>'._wp('Pending moderation').'</a>',
            'declined' => '<span class="count">'.$declined_count.'</span><a href="#/search/moderation=-1/"><i class="icon10 no"></i>'._wp('Declined').'</a>'

        );
        return array(
            'menu' => $items
        );
    }
    
    public function preparePhotosBackend(&$photos)
    {
        if (wa()->getUser()->getRights('photos', 'edit')) {
            foreach ($photos as &$p) {
                if ($p['source'] == 'publicgallery') {
                    $links = array(
                        '<a href="javascript:void(0);" class="moderation approve small nowrap" 
                    style="margin-right: 10px; '.($p['moderation'] == 1 ? 'display:none' : '').'"'.
                              'data-action="approve"><i class="icon10 yes"></i> '._wp('Approve photo').'</a>',
                        '<a href="javascript:void(0);" class="moderation decline small nowrap" 
                    style="'.($p['moderation'] == -1 ? 'display:none' : '').'"'.
                             'data-action="decline"><i class="icon10 no"></i> '._wp('Decline').'</a>'
                    );
                    $p['hooks']['thumb'][$this->id] = implode('', $links);
                }
            }
            unset($p);
        }
    }
    
    public function beforeSaveField(&$params)
    {
        if (empty($params['photo_id'])) {
            return;
        }
        if (is_array($params['photo_id'])) {
            $photo_id = (int) reset($params['photo_id']);
        } else {
            $photo_id = (int) $params['photo_id'];
        }
        
        $photo_model = new photosPhotoModel();
        $photo = $photo_model->getById($photo_id);
        if (!$photo) {
            return;
        }
        
        if (empty($params['data'])) {
            return;
        }
        $data = $params['data'];
        if (!isset($data['rate'])) {
            return;
        }
        
        $contact_id = wa()->getUser()->getId();
        
        $vote_model = new photosPublicgalleryVoteModel();
        
        if ($vote_model->getByField(array(
            'photo_id' => $photo_id,
            'contact_id' => $contact_id
        ))) {
            $params['data'] = $photo['rate'];
        } else {
            $vote_model->insert(array(
                'photo_id' => $photo_id,
                'contact_id' => wa()->getUser()->getId(),
                'rate' => $data['rate'],
                'datetime' => date('Y-m-d H:i:s'),
                'ip' => waRequest::getIp(true)
            ));
            $params['data']['rate'] = $vote_model->getRate($photo_id);
            $params['data']['votes_count'] = $vote_model->getVotesCount($photo_id);
        }
    }
    
    public function preparePhotosFrontend(&$photos)
    {
        foreach ($photos as &$p) {
            if (!isset($p['frontend_link'])) {
                $p['frontend_link'] = photosFrontendPhoto::getLink(array(
                    'url' => 'myphotos/'.$p['url']
                ));
            }
        }
        unset($p);
    }
    
    public function backendPhotoToolbar()
    {
        if (wa()->getUser()->getRights('photos', 'edit')) {
            $items = array(
                '<li data-action="approve" class="moderation approve"><a href="javascript:void(0);"><i class="icon16 yes"></i>'._wp('Approve').'</a></li>',
                '<li data-action="decline" class="moderation decline"><a href="javascript:void(0);"><i class="icon16 no"></i>'._wp('Decline').'</a></li>',
            );
            return array('share_menu' => $items);
        }
    }
    
    public function backendPhoto($photo_id)
    {
        $photo_model = new photosPhotoModel();
        $photo = $photo_model->getById($photo_id);
        if ($photo) {
            $votes_count_text = '';
            if ($photo['votes_count'] > 0) {
                $votes_count_text = _wp('%d vote', '%d votes', $photo['votes_count']);
            }
            
            $vote_model = new photosPublicgalleryVoteModel();
            $vote_item = $vote_model->getByField(array('photo_id' => $photo['id'], 'contact_id' => wa()->getUser()->getId()));
            $your_rate = 0;
            if ($vote_item) {
                $your_rate = $vote_item['rate'];
            }
            
            $html =  '<a class="hint" href="javascript:void(0);" id="photo-rate-votes-count" data-you-voted="'.(int)($your_rate > 0).'"><u>'.$votes_count_text.'</u></a>'.
                    '<span id="p-your-rate-wrapper">'._wp('My vote: ').
                        '<a href="javascript:void(0);" id="your-rate" class="p-rate-photo" data-rate="'.$your_rate.'">'.
                            photosPhoto::getRatingHtml($your_rate, 10, true).
                        '</a></span>'.
                '<a class="inline-link p-rate-clear small" href="javascript:void(0);" style="display:none;" id="clear-photo-rate"><b><i>'._wp('cancel my vote').'</b></i></a>';
            $html .= '<script>$.photos.publicgalleryInitYourRate();</script>';
            return array(
                'after_rate' => $html
            );
        }
    }
    
    public function searchFrontendLink($query)
    {
        if ($query == 'votes_count>0' || $query == 'moderation=0' || $query == 'moderation=-1') {
            return '';
        }
    }
    
    public function prepareCollection($params)
    {
        if (isset($params['hash']) && $params['hash'][0] == 'publicgallery' && $params['hash'][1] == 'myphotos') {
            $id = wa()->getUser()->getId();
            if ($id) {
                /**
                 * @var photosCollection
                 */
                $params['collection']->setCheckRights(false);
                $params['collection']->addWhere('contact_id='.$id);
                $params['collection']->setTitle(_wp('My uploads'));
                $params['collection']->orderBy('p.moderation,p.upload_datetime DESC,p.id');
            } else {
                $params['collection']->addWhere(0);
            }
            return true;
        }
    }
    
    public function extraPrepareCollection($params) 
    {
        if (isset($params['hash'][1]) && strstr($params['hash'][1], 'moderation=') !== false) {
            if (wa()->getEnv() == 'frontend') {
                $params['collection']->addWhere(0);
                return;
            }
            $search = explode('=', $params['hash'][1]);
            if (isset($search[1])) {
                if ($search[1] == '0') {
                    $params['collection']->setTitle(_wp('Pending moderation'));
                } else if ($search[1] == '-1') {
                    $params['collection']->setTitle(_wp('Declined'));
                }
                $params['collection']->addWhere("source='publicgallery'");
                return;
            }
        }
        
        // hash = id/photo_id:private_hash
        if (isset($params['hash'][0]) && $params['hash'][0] == 'id' && 
                isset($params['hash'][1]) && preg_match("!^[\d]+:[\da-fA-F]{32}$!", $params['hash'][1]))
        {
            return;
        }
        // hash = publicgallery/myphotos
        if (isset($params['hash']) && $params['hash'][0] == 'publicgallery' && $params['hash'][1] == 'myphotos') {
            return;
        }
        
        $params['collection']->addWhere('moderation=1');
    }
}