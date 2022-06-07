<?php

namespace Loyalty_Card_Check;

class Main
{
    private static $instance;
    const API_URL = 'http://example.com/check-loyal-card';
    const ROLE = 'has_loyalty_card';

    private function __construct()
    {
        add_filter('cron_schedules', [$this, 'cron_schedules']);
        add_action('loyalty_card_cron', [$this, 'cron_function_check_loyalcard']);
        register_activation_hook(__FILE__, [$this, 'cronstarter_activation']);
        register_deactivation_hook(__FILE__, [$this, 'cronstarter_deactivate']);
    }

    //schedules - timetable
    public function cron_schedules($timetable)
    {
        $timetable['cron_30m'] = [ //prod
            'interval' => 1800,
            'display' => '30 minutes'
        ];
        return $timetable;
    }

    // check loyalcard
    public function cron_function_check_loyalcard()
    {
        $users = get_users([
            'number' => 250,
            'meta_query' => [
                'relation' => 'OR',
                [
                    'key' => 'last_card_check',
                    'compare' => 'NOT EXISTS'
                ],
                [
                    'key' => 'last_card_check',
                    'value' => time() - 1,
                    'compare' => '<='
                ]
            ]
        ]);

        foreach ($users as $user) {
            $user_meta = get_user_meta($user->ID);
            $args = http_build_query(
                [
                    'email' => $user->user_email,
                    'name' => $user_meta['first_name'][0],
                    'surname' => $user_meta['last_name'][0],
                    'username' => 'KaZDmXotUk',
                    'password' => 'B0NDwty3LauDcNHwbaS7'
                ]
            );
            $response = wp_remote_post(self::API_URL . "?$args", [
                'timeout' => 30
            ]);
            if (is_wp_error($response))
                continue;
            $body = json_decode($response['body']);
            if ($body->isValid) {
                $user->add_role(self::ROLE);
            } else {
                $user->remove_role(self::ROLE);
            }
            update_user_meta($user->ID, 'last_card_check', time());
        }
        die;
    }

    // Activation hook
    public function cronstarter_activation()
    {
        if (!wp_next_scheduled('loyalty_card_cron')) {
            wp_schedule_event(time(), 'cron_30m', 'loyalty_card_cron');
        }
    }

    // Deactivation hook
    public function cronstarter_deactivate()
    {
        $timestamp = wp_next_scheduled('loyalty_card_cron');
        wp_unschedule_event($timestamp, 'loyalty_card_cron');
    }

    private function __wakeup()
    {
    }

    private function __clone()
    {
    }

    public static function getInstance()
    {
        return self::$instance !== null ? self::$instance : new self();
    }
}

Main::getInstance();