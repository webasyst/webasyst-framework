<?php

class blogTrollPlugin extends blogPlugin
{
    public function prepareView(&$comments)
    {
        $email = wa_make_pattern(trim($this->getSettingValue('email')));
        if ($email) {
            $pattern = '/(.*'.preg_replace('/[,\n\s]{1,}/','|.*',$email).')/i';
            if ( (wa()->getEnv() == 'backend') && wa()->getUser()->isAdmin($this->app_id)) {
                $label = '<a href="?module=plugins&amp;slug=troll"><i class="icon16 troll"  title="'.($title = _wp('Troll')).'"><!-- trollface --></i></a>';
            } else {
                $label = '<i class="icon16 troll"  title="'.($title = _wp('Troll!')).'"><!-- trollface --></i>';
            }

            foreach ($comments as &$comment) {
                $email = false;
                if ( !$comment['contact_id']
                  && (
                        ($comment['email'] && preg_match($pattern, $comment['email']))
                     || ($comment['site'] && preg_match($pattern, $comment['site']))
                     || ($comment['name'] && preg_match($pattern, $comment['name']))
                     )
                ) {
                    $comment['plugins']['authorname_suffix'][$this->id] = $label;
                }
                unset($comment);
            }
        }
    }

    public function addControls()
    {
        $this->addCss('css/troll.css',true);
    }

    public function postView()
    {
        $url = wa()->getAppStaticUrl().$this->getUrl('css/troll.css', true);
        $content = array();
        $content['head'] = "<link href=\"{$url}\" rel=\"stylesheet\" type=\"text/css\">";
        return $content;
    }
}
//EOF