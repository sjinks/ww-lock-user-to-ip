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

defined('ABSPATH') or die();

class LockUser
{
    public static function instance()
    {
        static $self = null;

        if (! $self) {
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
        load_plugin_textdomain('lock-user', false, substr(__DIR__, strlen(WP_PLUGIN_DIR) + 1) . '/lang/');
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

    public function edit_user_profile_update($id)
    {
        if (isset($_POST['psb_ip_list'])) {
            $ips = explode("\n", trim($_POST['psb_ip_list']));
            $list = array();
            foreach ($ips as $ip) {
                $ip = trim($ip);
                if ($ip) {
                    $ip = inet_pton($ip);
                    if (false !== $ip) {
                        $ip = inet_ntop($ip);
                        $list[] = $ip;
                    }
                }
            }

            if ($list) {
                update_user_meta($id, 'psb_ip_list', $list);
            }
            else {
                delete_user_meta($id, 'psb_ip_list');
            }
        }
    }

    public function wp_login($user_login)
    {
        $user = get_userdatabylogin($user_login);
        if (empty($user) || $user->ID < 1 || empty($_SERVER['REMOTE_ADDR'])) {
            return;
        }

        $id  = $user->ID;
        $cur = inet_pton($_SERVER['REMOTE_ADDR']);
        $ips = get_user_meta($user->ID, 'psb_ip_list', true);

        if ($ips) {
            foreach ($ips as $ip) {
                $ip = inet_pton($ip);
                if (!strcmp($ip, $cur)) {
                    return;
                }
            }

            $message = sprintf(
                _(
                      "User %1\$s has tried to log in from %2\$s\n"
                    . "Time: %3\$s\n"
                    . "Browser: %4\$s\n",
                    'lock-user'
                ),
                $user_login, $_SERVER['REMOTE_ADDR'], date_i18n(get_option('date_format'), time()), $_SERVER['HTTP_USER_AGENT']
            );

            if (function_exists('geoip_record_by_name')) {
                $rec = geoip_record_by_name($_SERVER['REMOTE_ADDR']);
                if (is_array($rec)) {
                    $message .= sprintf(__("Country: %1\$s\n", 'lock-user'), isset($rec['country_name']) ? $rec['country_name'] : '');
                    $message .= sprintf(__("City: %1\$s\n",    'lock-user'), isset($rec['city'])         ? $rec['city']         : '');
                }
            }

            wp_mail(get_option('admin_email'), __('Suspicious login attempt', 'lock-user'), $message);

            wp_logout();
            wp_redirect(wp_login_url());
            exit();
        }
    }
}

LockUser::instance();
