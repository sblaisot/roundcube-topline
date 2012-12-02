<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * topline plugin for roundcube webmail
 *
 *
 * Simple plugin to add information to the topline of the interface
 * of the roundcube webmail.
 *
 * @version 0.0.4
 * @author Sebastien Blaisot <sebastien@blaisot.org>
 * @website https://github.com/sblaisot/roundcube-topline
 * @licence http://www.gnu.org/licenses/gpl-3.0.html GNU GPLv3+
 *
 */


class topline extends rcube_plugin
{

    public function init()
    {
        $this->add_texts('localization/', false);

        $this->load_config('config/config.inc.php.dist');
        if(file_exists("./plugins/topline/config/config.inc.php"))
          $this->load_config('config/config.inc.php');

        $this->add_hook('login_after', array($this, 'store_lastlogin'));
        $this->add_hook('template_container', array($this, 'update_container'));

        if (rcmail::get_instance()->config->get('topline_hide_username', true))
            $this->add_hook('template_object_username', array($this, 'hide_username'));
    }
    
    private function get_content($what)
    {
        $rcmail = rcmail::get_instance();
        $user = $rcmail->user;
        $identity = $user->get_identity();
        $content="";
        $mpty=1;
        foreach ($what as &$value) {
            switch ($value) {
                case 'username':
                    $content.=$user->data['username'];
                    break;
                case 'mail_host':
                    $content.=$user->data['mail_host'];
                    break;
                case 'email':
                    $content.=$identity['email'];
                    break;
                case 'full_name':
                    $content.=$identity['name'];
                    break;
                case 'lastlogin':
                    $content.=$this->gettext('lastlogin').$_SESSION['lastlogin'];
                    break;
                default:
                    $content.=$value;
            }
        }
            return $content;
    }

    public function store_lastlogin($p)
    {
        $rcmail = rcmail::get_instance();
        $user = $rcmail->user;
        $_SESSION['lastlogin'] = $user->data['last_login'];
    }

    public function hide_username($p)
    {
        return array('content' => "");
    }

    public function update_container($p)
    {
        $rcmail = rcmail::get_instance();
        switch ($p['name']) {
            case 'topline-left':
                return array('content' => $p['content'].Q($this->get_content($rcmail->config->get('topline_left_content', true))));
                break;
            case 'topline-center':
                return array('content' => $p['content'].Q($this->get_content($rcmail->config->get('topline_center_content', true))));
                break;
            case 'topline-right':
                return array('content' => $p['content'].Q($this->get_content($rcmail->config->get('topline_right_content', true))));
                break;
        }
    }
}
