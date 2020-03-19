<?php

class blogEmailsubscriptionPlugin extends blogPlugin
{
    public function postPublishAction($params)
    {
        $post_id = (int)$params['id'];
        $blog_id = (int)$params['blog_id'];

        // check rights for this blog at first and unsubscribe user if he hasn't

        $sql = "SELECT c.id FROM blog_emailsubscription s
        JOIN wa_contact c ON s.contact_id = c.id
        WHERE s.blog_id = ".$blog_id;

        $model = new waModel();
        $unsubscribe_contact_ids = array();
        foreach ($model->query($sql) as $row) {
            $rights = 1;
            try {
                $rights = blogHelper::checkRights($blog_id, $row['id'], blogRightConfig::RIGHT_READ);
            } catch (Exception $e) {
                $rights = 0;
            }
            if (!$rights) {
                $unsubscribe_contact_ids[] = $row['id'];
            }
        }
        if ($unsubscribe_contact_ids) {
            $em = new blogEmailsubscriptionModel();
            $em->deleteByField(array(
                'contact_id' => $unsubscribe_contact_ids,
                'blog_id' => $blog_id
            ));
        }

        // add subscribers to queue
        $sql = "REPLACE INTO blog_emailsubscription_log (post_id, contact_id, name, email, datetime)
                SELECT ".$post_id.", c.id, c.name, e.email, '".date('Y-m-d H:i:s')."' FROM blog_emailsubscription s
                JOIN wa_contact c ON s.contact_id = c.id
                JOIN wa_contact_emails e ON c.id = e.contact_id AND e.sort = 0
                WHERE s.blog_id = ".$blog_id;
        $model->exec($sql);

        // save backend url for cron
        $app_settings_model = new waAppSettingsModel();
        $app_settings_model->set(array($this->app_id, $this->id), 'backend_url', wa()->getRootUrl(true).wa()->getConfig()->getBackendUrl());
    }

    /**
     * @deprecated
     * @see blogEmailsubscriptionCli
     */
    public function cronAction($params)
    {
        $model = new blogEmailsubscriptionLogModel();
        $row = $model->getByField('status', 0);
        if ($row) {
            $post_id = $row['post_id'];
            $post_model = new blogPostModel();
            $post = $post_model->getById($post_id);

            $blog_model = new blogBlogModel();
            $blog = $blog_model->getById($post['blog_id']);

            $subject = $blog['name'].': '.$post['title'];
            $post_title = htmlspecialchars($post['title']);
            if ($blog['status'] == blogBlogModel::STATUS_PUBLIC) {
                $post_url = blogPost::getUrl($post);
            } else {
                $app_settings_model = new waAppSettingsModel();
                $post_url = $app_settings_model->get(array($this->app_id, $this->id), 'backend_url', wa()->getRootUrl(true).wa()->getConfig()->getBackendUrl());
                $post_url .= "/blog/?module=post&id=".$post_id;
            }
            $blog_name = htmlspecialchars($blog['name']);
            $body = '<html><body>'.sprintf(_wp("New post in blog “%s”"), $blog_name).': <strong><a href="'.$post_url.'">'.$post_title.'</a></strong></body></html>';
            $message = new waMailMessage();
            $message->setEncoder(Swift_Encoding::getBase64Encoding());
            $message->setSubject($subject);
            $message->setBody($body);
            $rows = $model->getByField(array('status' => 0, 'post_id' => $post_id), true);

            foreach ($rows as $row) {
                try {
                    $message->setTo($row['email'], $row['name']);
                    $status = $message->send() ? 1 : -1;
                    $model->setStatus($row['id'], $status);
                } catch (Exception $e) {
                    $model->setStatus($row['id'], -1, $e->getMessage());
                }
            }
        }
    }

    public function settingsAction($params)
    {
        $blog_id = $params['id'];
        $html = '<div class="fields-group">
        <div class="field">
            <div class="name">'._wp('Subscribed via email').'</div>
            <div class="value">';
        $model = new blogEmailsubscriptionModel();
        $contacts = $model->getSubscribers($blog_id);
        $rights = wa()->getUser()->getRights('contacts');
        $html .= '<ul class="menu-v">';
        if (!$contacts) {
            $html .= '<li>'._wp('none').'</li>';
        }
        foreach ($contacts as $c) {
            $html .= '<li>';
            if ($rights) {
                $html .= '<a href="'.wa()->getConfig()->getBackendUrl(true).'contacts/#/contact/'.$c['id'].'">';
            }
            $html .= '<i class="icon16 userpic20" style="background-image: url('.waContact::getPhotoUrl($c['id'], $c['photo'], 20).')"></i>';
            $html .= '<span>'.htmlspecialchars($c['name']).'</span>';
            if ($rights) {
                $html .= '</a>';
            }
            $html .= '</li>';
        }
        $html .= '</ul></div></div></div>';

        return array(
            'settings' => $html
        );
    }

    public function blogAction($params)
    {
        if (!empty($params['blog'])) {
            $blog_id = $params['blog']['id'];
            $model = new blogEmailsubscriptionModel();
            $subscribed = $model->getByField(array('blog_id' => $blog_id, 'contact_id' => wa()->getUser()->getId()));

            /**
             * @deprecated
             * For backward compatibility reason
             */
            $cron_schedule_time = waSystem::getSetting('cron_schedule', 0, 'blog');

            $last_emailsubscription_cron_time = waSystem::getSetting('last_emailsubscription_cron_time', 0, array('blog', 'emailsubscription'));
            if (!$cron_schedule_time && !$last_emailsubscription_cron_time) {
                $msg = sprintf( _wp('WARNING: Email subscription works only if Cron scheduled task for the Blog app is properly configured (%s), and it appears that Cron was not setup for your Webasyst installation yet. Please contact your server administrator, or follow instructions given in the Plugins > Email subscription page of the Blog app. Click OK to subscribe anyway (subscription will be enabled, but will start to work only when Cron is enabled).'), 'cli.php blog emailsubscription' );
                $confirm_subscribe =  "
            if ($(this).is(':checked') && !confirm('".$msg."')) {
                $(this).iButton('toggle', false);
                return false;
            }
            subscribe();
          ";
            } else {
                $confirm_subscribe  = "subscribe();";
            }
            $html = '<div class="b-ibutton-checkbox">
    <ul class="menu-h">
        <li style="margin-top: -3px;"><input type="checkbox" id="blog-emailsubscription-checkbox"'.($subscribed ? ' checked="checked"' : '').'></li>
        <li style="margin-top: -4px; padding-left: 0.3em; padding-right: 0.3em;"><span id="blog-emailsubscription-status"'.(!$subscribed ? ' class="b-unselected"':'').'>'._wp('Email alerts').'</span></li>
    </ul>
</div>
<script>
    $("#blog-emailsubscription-checkbox").iButton({
        labelOn: "",
        labelOff: "",
        classContainer: "ibutton-container mini"
    }).change(function () {
        if ($(this).is(":checked")) {
            $("#blog-emailsubscription-status").removeClass("b-unselected");
        } else {
            $("#blog-emailsubscription-status").addClass("b-unselected");
        }
        var that = this;
        function subscribe() {
            $.post("?plugin=emailsubscription&module=subscribe", {blog_id:'.$blog_id.', subscribe: $(that).is(":checked") ? 1 : 0}, function () {
            }, "json");
        }

        '.$confirm_subscribe.'
    });
</script>';
            return array(
                'menu' => $html
            );
        }
    }
}