<?php
/*
Plugin Name: SEO Keyword on Image Alt Automatically
Plugin URI: https://webdesignerk.com/modulos-y-plugins/palabra-clave-de-yoast-seo-en-alt-de-imagen/
Description: Añade un alt automáticamente a tus imágenes basándote en la palabra clave proporcionada en el plugin Yoast SEO. Es necesario tener el Yoast SEO instalado.
Version: 1.0
Author: Konstantin K.
Author URI: https://webdesignerk.com/
Text Domain: seo-keyword-image-alt
Domain Path: /languages
Requires at least: 5.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

function add_custom_field() {
    add_meta_box(
        'custom-field',
        esc_html__('Palabra clave en ALT', 'seo-keyword-image-alt'),
        'custom_field_callback',
        'post',
        'normal',
        'default'
    );
}

function custom_field_callback($post) {
    wp_nonce_field('custom_field_nonce', 'custom_field_nonce');
    $value = get_post_meta($post->ID, '_yoast_wpseo_focuskw', true); // Obtiene el valor del campo "Frase clave objetivo" de Yoast SEO
    echo '<label for="custom_field">' . esc_html__('Palabra para alt', 'seo-keyword-image-alt') . '</label>';
    echo '<input type="text" id="custom_field" name="custom_field" value="' . esc_attr($value) . '">';
    echo '<p>' . esc_html__('Este campo se rellena automáticamente con la palabra clave objetivo añadida en tu plugin Yoast SEO. Puedes sobrescribir este campo, pero volverá a añadir la palabra clave después de actualizar.', 'seo-keyword-image-alt') . '</p>';
}

function save_custom_field($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!isset($_POST['custom_field_nonce']) || !wp_verify_nonce(sanitize_text_field($_POST['custom_field_nonce']), 'custom_field_nonce')) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    if (isset($_POST['custom_field'])) {
        update_post_meta($post_id, '_custom_field', sanitize_text_field($_POST['custom_field']));
    } else {
        delete_post_meta($post_id, '_custom_field');
    }
}

function add_custom_alt_attribute($content) {
    global $post;
    $custom_field = get_post_meta($post->ID, '_custom_field', true);
    if (empty($custom_field)) {
        return $content;
    }
    $pattern = '/<img\s+[^>]*alt=(["\'])(.*?)\1[^>]*>/i';
    $content = preg_replace_callback(
        $pattern,
        function($match) use ($custom_field) {
            if (empty($match[2])) {
                return str_replace('<img', '<img alt="' . esc_attr($custom_field) . '"', $match[0]);
            } else {
                return $match[0];
            }
        },
        $content
    );
    return $content;
}

add_action('add_meta_boxes', 'add_custom_field');
add_action('save_post', 'save_custom_field');
add_filter('the_content', 'add_custom_alt_attribute');
