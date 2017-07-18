<?php
/*
 * Plugin Name: Lock User
 * Plugin URI:
 * Description: Locks a user to specific IP addresses
 * Version: 1.0.0
 * Author: Volodymyr Kolesnykov
 * License: MIT
 * Text Domain: lock-user
 * Domain Path: /lang
 */

namespace WildWolf;

class LockUser
{
    public static function instance()
    {
        static $self = null;

        if (!$self) {
            $self = new self();
        }

        return $self;
    }

    private function __construct()
    {
        add_action('plugins_loaded', [$this, 'plugins_loaded']);
        add_action('init',           [$this, 'init']);
    }

    public function plugins_loaded()
    {
        load_plugin_textdomain('lock-user', false, substr(__DIR__, strlen(\WP_PLUGIN_DIR) + 1) . '/lang/');
    }

    public function init()
    {
        add_action('wp_login',   [$this, 'wp_login']);
        add_action('admin_init', [$this, 'admin_init']);
    }

    public function admin_init()
    {
        add_action('edit_user_profile_update', [$this, 'edit_user_profile_update']);
        add_action('edit_user_profile',        [$this, 'edit_user_profile']);
    }

    public function edit_user_profile(\WP_User $user)
    {
        $ips = get_user_meta($user->ID, 'psb_ip_list', true);
        $ips = is_array($ips) ? join("\n", $ips) : '';
        require __DIR__ . '/views/profile.php';
    }

    private static function ipListToArray($iplist)
    {
        $list = [];
        if (!empty($iplist)) {
            $ips  = explode("\n", $iplist);

            foreach ($ips as $ip) {
                $ip = trim($ip);
                if ($ip) {
                    $ip = inet_pton($ip);
                    if (false !== $ip) {
                        $list[] = inet_ntop($ip);
                    }
                }
            }
        }

        return $list;
    }

    public function edit_user_profile_update($id)
    {
        $iplist = trim(filter_input(INPUT_POST, 'psb_ip_list', FILTER_UNSAFE_RAW));
        $list   = self::ipListToArray($iplist);

        if ($list) {
            update_user_meta($id, 'psb_ip_list', $list);
        }
        else {
            delete_user_meta($id, 'psb_ip_list');
        }
    }

    private static function warnAdmin($user_login, $addr)
    {
        $ua      = filter_input(INPUT_SERVER, 'HTTP_USER_AGENT');
        $message = sprintf(
            _(
                "User %1\$s has tried to log in from %2\$s\n"
                . "Time: %3\$s\n"
                . "Browser: %4\$s\n",
                'lock-user'
            ),
            $user_login, $addr, date_i18n(get_option('date_format'), time()), $ua
        );

        if (function_exists('geoip_record_by_name')) {
            $rec = geoip_record_by_name($addr);
            if (is_array($rec)) {
                $message .= sprintf(__("Country: %1\$s\n", 'lock-user'), isset($rec['country_name']) ? $rec['country_name'] : '');
                $message .= sprintf(__("City: %1\$s\n",    'lock-user'), isset($rec['city'])         ? $rec['city']         : '');
            }
        }

        wp_mail(get_option('admin_email'), __('Suspicious login attempt', 'lock-user'), $message);
    }

    public function wp_login($user_login)
    {
        $user = get_user_by('login', $user_login);
        $addr = filter_input(INPUT_SERVER, 'REMOTE_ADDR');
        if (empty($user) || $user->ID < 1 || empty($addr)) {
            return;
        }

        $cur = inet_pton($addr);
        $ips = get_user_meta($user->ID, 'psb_ip_list', true);

        if ($ips) {
            foreach ($ips as $ip) {
                $ip = inet_pton($ip);
                if (!strcmp($ip, $cur)) {
                    return;
                }
            }

            self::warnAdmin($user_login, $addr);

            wp_logout();
            wp_redirect(wp_login_url());
            die();
        }
    }
}

if (defined('ABSPATH')) {
    LockUser::instance();
}
