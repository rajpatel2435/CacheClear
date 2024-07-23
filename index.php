<?php
/*
Plugin Name: Cache Clearing Plugin
Description: Adds a new admin menu option that allows to clear all cache manually every 5 minutes.
Version: 1.0
Author: BetUsDev
*/

// Hook to add admin menu
add_action('admin_menu', 'admin_button_plugin_menu');

function admin_button_plugin_menu() {
    add_menu_page(
        'Cache Clearing Plugin',     // Page title
        'Cache Clearing Plugin',     // Menu title
        'manage_options',   // Capability
        'admin-button',     // Menu slug
        'admin_button_page' // Callback function
    );
}

function admin_button_page() {
    ?>
    <div class="wrap">
        <h1>Clear Cloudfare Cache</h1>
        <?php
        $last_clicked = get_option('admin_button_last_clicked');
        $current_time = current_time('timestamp');

        if ($last_clicked && ($current_time - $last_clicked < 300)) {
            echo '<p>Cache cleared less than 5 minutes ago, please wait before clicking the button again.</p>';
        } else {
            ?>
            <form method="post" action="">
                <?php wp_nonce_field('admin_button_action', 'admin_button_nonce'); ?>
                <input type="hidden" name="admin_button_click" value="true">
                <input type="submit" class="button button-primary" value="Clear Cache">
            </form>
            <?php
        }
        ?>
    </div>
    <?php
}

add_action('admin_init', 'handle_admin_button_click');

function handle_admin_button_click() {
    if (isset($_POST['admin_button_click']) && $_POST['admin_button_click'] === 'true') {
        if (!isset($_POST['admin_button_nonce']) || !wp_verify_nonce($_POST['admin_button_nonce'], 'admin_button_action')) {
            return;
        }

        $last_clicked = get_option('admin_button_last_clicked');
        $current_time = current_time('timestamp');

        if (!$last_clicked || ($current_time - $last_clicked >= 300)) {
            update_option('admin_button_last_clicked', $current_time);
            // Handle your request here, for example:
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => "https://api.cloudflare.com/client/v4/zones/0ee0a28bbb758bddaa240e2d7c2a0efa/purge_cache",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => '{"purge_everything": true}',
                CURLOPT_HTTPHEADER => [
                    "Content-Type: application/json",
                    "Authorization: Bearer nr12UZK7M5BJTD3rWfg8fLv78XB4-E1_j2db1yVg",
                ],
            ]);
        
            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);

            // Notify the user
            add_action('admin_notices', function() use (&$response, &$err) {
                if ($err) {
                    echo '<div class="notice notice-error is-dismissible"><p>'. $err . '.</p></div>';
                }
                if ($response) {
                    $response = json_decode($response, $associative = true);
                    if ($response["success"]){
                        echo '<div class="notice notice-success is-dismissible"><p>Cache cleared successfully.</p></div>';
                    }
                }
            });
        }
    }
}
