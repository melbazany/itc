<?php
/**
 * ExpressionEngine Pro
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
*/

namespace ExpressionEngine\Addons\Pro\Service\Cookie;

use ExpressionEngine\Service\Cookie as Core;

/**
 * Cookie Service
 */
class Cookie extends Core\Cookie
{
    /**
     * {exp:consent:cookies}
     */
    public function cookiesTag()
    {
        $cookie_prefix = (! ee()->config->item('cookie_prefix')) ? 'exp_' : ee()->config->item('cookie_prefix') . '_';
        $allCookies = ee('Model')->get('CookieSetting')->all();
        $type = null;
        if (in_array(ee()->TMPL->fetch_param('type'), ['necessary', 'functionality', 'performance', 'targeting'])) {
            $type = ee()->TMPL->fetch_param('type');
            $fn = 'is' . $type;
        }
        $vars = [];
        foreach ($allCookies as $cookie) {
            if (empty($type) || ee('CookieRegistry')->{$fn}($cookie->cookie_name)) {
                $vars[] = [
                    'cookie_title' => $cookie->cookie_title,
                    'cookie_name' => $cookie_prefix . $cookie->cookie_name,
                    'cookie_description' => $cookie->cookie_description,
                    'cookie_lifetime' => ($cookie->cookie_enforced_lifetime !== null) ? $cookie->cookie_enforced_lifetime : $cookie->cookie_lifetime,
                    'cookie_provider' => $cookie->cookie_provider
                ];
            }
        }
        if (empty($vars)) {
            return ee()->TMPL->no_results();
        }
        return ee()->TMPL->parse_variables(ee()->TMPL->tagdata, $vars);
    }
}
