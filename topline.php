<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * topline plugin for roundcube webmail
 *
 *
 * Simple plugin to add information to the topline of the interface
 * of the roundcube webmail.
 *
 * @version 0.0.5
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

    private function get_weather($location, $unit)
    {
	$rcmail = rcmail::get_instance();

	/* Fetch weather feed */
        $weather_feed = file_get_contents("http://weather.yahooapis.com/forecastrss?w=$location&u=$unit");
        if(!$weather_feed) return rcube::Q($this->gettext('error_loading_weather_feed'));

	/* Decode weather feed */
        $weather = simplexml_load_string($weather_feed);

        $channel_yweather = $weather->channel->children("http://xml.weather.yahoo.com/ns/rss/1.0");
        foreach($channel_yweather as $x => $channel_item)
                foreach($channel_item->attributes() as $k => $attr)
                        $yw_channel[$x][$k] = $attr;

        foreach($channel_yweather->location as $x => $location_item)
                foreach($location_item->attributes() as $k => $attr)
                	$yw_location[$k] = $attr;

        $item_yweather = $weather->channel->item->children("http://xml.weather.yahoo.com/ns/rss/1.0");

        foreach($item_yweather as $x => $yw_item) {
                foreach($yw_item->attributes() as $k => $attr) {
                        if($k == 'day') $day = $attr;
                        if($x == 'forecast') { $yw_forecast[$x][$day . ''][$k] = $attr; }
                        else { $yw_forecast[$x][$k] = $attr; }
                }
	}

	/* Construct weather string */
        $weather_string = "";
	if ($rcmail->config->get('topline_weather_show_location', true)) {
        	$weather_string .= rcube::Q((string)$yw_location[city].", ".(string)$yw_location[country]);
	}
	if ($rcmail->config->get('topline_weather_show_icon', true)){
        	$weather_string .= "&nbsp;<img style=\"width:15px;height:15px;vertical-align:middle\" src=\"plugins/topline/".$this->local_skin_path()."/images/".(string)$yw_forecast[condition][code].".gif\">&nbsp;";
	}
	$weather_string .= rcube::Q((string)$yw_forecast[condition][temp]."°".strtoupper($unit).", ".(string)$yw_forecast[condition][text]);
	return $weather_string;
    }
    
    private function get_content($what)
    {
        $rcmail = rcmail::get_instance();
        $user = $rcmail->user;
        $identity = $user->get_identity();
        $content="";
        $mpty=1;
        if(!is_array($what)) $what = array($what);
        foreach ($what as &$value) {
            switch ($value) {
                case 'username':
                    $content.=rcube::Q($user->data['username']);
                    break;
                case 'mail_host':
                    $content.=rcube::Q($user->data['mail_host']);
                    break;
                case 'email':
                    $content.=rcube::Q($identity['email']);
                    break;
                case 'full_name':
                    $content.=rcube::Q($identity['name']);
                    break;
                case 'lastlogin':
                    $content.=rcube::Q($this->gettext('lastlogin').$_SESSION['lastlogin']);
                    break;
                case 'weather':
                    $content.=$this->get_weather($rcmail->config->get('topline_weather_WOEID', true),$rcmail->config->get('topline_weather_unit', true));
                    break;
                default:
                    $content.=rcube::Q($value);
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
                return array('content' => $p['content'].$this->get_content($rcmail->config->get('topline_left_content', true)));
                break;
            case 'topline-center':
                return array('content' => $p['content'].$this->get_content($rcmail->config->get('topline_center_content', true)));
                break;
            case 'topline-right':
                return array('content' => $p['content'].$this->get_content($rcmail->config->get('topline_right_content', true)));
                break;
        }
    }
}
