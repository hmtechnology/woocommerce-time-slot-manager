<?php

/*
Plugin Name: WooCommerce Time Slot Manager
Description: Define specific opening and closing times for products, ensuring that they are available to customers only during the designated time slots
Version: 1.0
Author: hmtechnology
Author URI: https://github.com/hmtechnology
License: GNU General Public License v3.0
License URI: https://www.gnu.org/licenses/gpl-3.0.txt
Plugin URI: https://github.com/hmtechnology/woocommerce-time-slot-manager
*/

// Add two custom fields for opening and closing times with select options
function add_opening_closing_time_fields() {
    $time_options = array();

    // Generate select options for 30-minute intervals
    for ($hour = 0; $hour < 24; $hour++) {
        for ($minute = 0; $minute < 60; $minute += 30) {
            $time = sprintf('%02d:%02d', $hour, $minute);
            $time_options[$time] = $time;
        }
    }

    woocommerce_wp_select(
        array(
            'id' => '_opening_time',
            'label' => 'Opening Time',
            'desc_tip' => true,
            'description' => 'Select the opening time',
            'options' => $time_options,
        )
    );

    woocommerce_wp_select(
        array(
            'id' => '_closing_time',
            'label' => 'Closing Time',
            'desc_tip' => true,
            'description' => 'Select the closing time',
            'options' => $time_options,
        )
    );
}
add_action('woocommerce_product_options_general_product_data', 'add_opening_closing_time_fields');

// Save custom fields
function save_opening_closing_time_fields($product) {
    $opening_time = isset($_POST['_opening_time']) ? sanitize_text_field($_POST['_opening_time']) : '';
    $closing_time = isset($_POST['_closing_time']) ? sanitize_text_field($_POST['_closing_time']) : '';
    $product->update_meta_data('_opening_time', $opening_time);
    $product->update_meta_data('_closing_time', $closing_time);
}
add_action('woocommerce_admin_process_product_object', 'save_opening_closing_time_fields');


// Check the time when purchasing
function check_availability_time($purchasable, $product) {

    $opening_time = $product->get_meta('_opening_time');
    $closing_time = $product->get_meta('_closing_time');
    
    if (empty($opening_time) || empty($closing_time)) return $purchasable;

    $current_time_utc = new DateTime('now', new DateTimeZone('UTC'));
    $current_time_utc->setTimezone(new DateTimeZone('Europe/Rome'));
    $current_time_rome = $current_time_utc->format('H:i');
    
    if ($current_time_rome < $opening_time || $current_time_rome > $closing_time) {
        return false; // The product cannot be purchased outside the specified interval between opening and closing times
    }

    return $purchasable;
}
add_filter('woocommerce_is_purchasable', 'check_availability_time', 10, 2);
