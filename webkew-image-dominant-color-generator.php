<?php
/*
Plugin Name: WebKew Image Dominant Color Generator
Description:Automatically generate a dominant color and a colors palette (6 colors) for any uploaded image to the WordPress media library.
Version: 1.0.0
Text Domain: webkew-image-dominant-color-generator
Author: Peshmerge Morad
Author URI: https://peshmerge.io
*/

require __DIR__ . '/vendor/autoload.php';

use ColorThief\ColorThief;

//The number of colors in the colors palette.
const COLORS_COUNT = 6;
// Any number between 0 and 10. 10 very high and this more computationally expensive.
const COLORS_QUALITY = 5;

add_action('admin_enqueue_scripts', 'webkew_cifdc_enqueue_color_picker');
function webkew_cifdc_enqueue_color_picker($hook_suffix)
{
    // Not, on the settings page, don't load anything!
    if ('options-general.php' !== $hook_suffix) {
        return;
    }
    wp_enqueue_style('wp-color-picker');
    wp_enqueue_script('wp-color-picker');
    wp_add_inline_script('wp-color-picker', '
        (function( $ ) {
            "use strict";
            jQuery(document).ready(function($) {
                $(".webkew-idcg-fallback-color-setting-cl").wpColorPicker();
            });
        })(jQuery);
    ');
}

add_action('admin_enqueue_scripts', 'webkew_cifdc_enqueue_admin_scripts');
function webkew_cifdc_enqueue_admin_scripts()
{
    $screen = get_current_screen();
//    Enqueue the scripts when are dealing with custom post types, posts, pages and in the media library
    if ($screen && ($screen->base === 'post' || $screen->id === 'upload' || $screen->id === 'post') || $screen->id === 'page') {
        wp_register_script(
            'webkew-idcg-js',
            plugins_url('js/webkew-idcg-admin.js', __FILE__),
            array('jquery'),
            1.0,
            true
        );
        wp_enqueue_script('webkew-idcg-js');


        wp_register_style('webkew-idcg-css', plugins_url('css/webkew-idcg-admin.css', __FILE__), [], 1.0);
        wp_enqueue_style('webkew-idcg-css');
    }
}

// Register the setting
function webkew_idcg_register_settings()
{
    register_setting('general', 'webkew_idcg_fallback_color_setting', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_hex_color',
        'default' => '#f9bc40',
    ));
}

add_action('admin_init', 'webkew_idcg_register_settings');

// Add the color picker field to the settings page
function webkew_settings_field()
{
    ?>
    <tr>
        <th scope="row"><label for="webkew_idcg_fallback_color_setting">
                <?php esc_html_e('Select a fallback color', 'webkew-image-dominant-color-generator') ?></label></th>
        <td>
            <input type="text" id="webkew_idcg_fallback_color_setting" name="webkew_idcg_fallback_color_setting"
                   value="<?php echo esc_attr(get_option('webkew_idcg_fallback_color_setting')); ?>"
                   class="webkew-idcg-fallback-color-setting-cl" data-default-color="#f9bc40"/>
        </td>
    </tr>
    <?php
}

add_action('admin_init', function () {
    add_settings_field(
        'webkew_cifdc_fallback_color_setting',
        __('WebKew Image Dominant Color Generator - Fallback color', 'webkew-image-dominant-color-generator'),
        'webkew_settings_field',
        'general'
    );
});


add_action('plugins_loaded', 'webkew_idcg_init');
function webkew_idcg_init()
{
    load_plugin_textdomain(
        'webkew-image-dominant-color-generator',
        false,
        plugin_basename(dirname(__FILE__)) . '/languages'
    );
}

// Register the shortcode with WordPress
add_shortcode('dominant_color_pesho', 'get_dominant_color_shortcode1');
function get_dominant_color_shortcode1($attributes)
{

}

add_action('add_attachment', 'generate_dominant_color_and_colors_palette_for_uploaded_image', 10, 1);
function generate_dominant_color_and_colors_palette_for_uploaded_image($attachment_id)
{
    if (!wp_attachment_is_image($attachment_id)) {
        return "Attachment is not an image!";
    }
    try {
        $image = get_attached_file($attachment_id);
        $dominant_color = ColorThief::getColor($image, COLORS_QUALITY, null, 'hex');
        $colors_palette = ColorThief::getPalette($image, COLORS_COUNT, COLORS_QUALITY, null, 'hex');
    } catch (Exception $exception) {
        return $exception;
    }
    update_post_meta($attachment_id, 'webkew_dominant_color', $dominant_color);
    update_post_meta($attachment_id, 'webkew_colors_palette', $colors_palette);
}


function get_colors_palette($attachment_id)
{
    $colors_palette = get_post_meta($attachment_id, 'webkew_colors_palette', true);
    if (!$colors_palette) {
        generate_dominant_color_and_colors_palette_for_uploaded_image($attachment_id);
        return get_post_meta($attachment_id, 'webkew_colors_palette', true);
    }
    return $colors_palette;
}

function get_dominant_color($attachment_id)
{
    return get_post_meta($attachment_id, 'webkew_dominant_color', true);
}

add_filter('attachment_fields_to_edit', 'add_colors_palette_fields', 10, 2);
function add_colors_palette_fields($form_fields, $post)
{
    $colors_palette = get_colors_palette($post->ID);
    $dominant_color = get_dominant_color($post->ID);

    if (!$colors_palette || !$dominant_color) {
        $html = __('No colors palette available.', 'webkew-image-dominant-color-generator');
        $html .= '<br /><a href="#" class="build-colors-palette" data-dominance-rebuild="' . $post->ID . '">';
        $html .= __('Generate colors palette', 'webkew-image-dominant-color-generator');
        $html .= '</a>';
    } else {
        $html_colors_palette_array = [];
        $html_colors_palette_array[] = "<ul>";
        foreach ($colors_palette as $color) {
            $html = '<li class="colors-palette-color" title="' . $color . '"';
            $html .= ' data-color="' . $color . '" style="background-color: ' . $color . '"></li>';
            $html_colors_palette_array[] = $html;
        }
        $html_colors_palette_array[] = "</ul>";

        $html = '<div id="colors-palette-container">' . implode($html_colors_palette_array) . '</div>';
        $html .= '<br /><a href="#" class="build-colors-palette" data-dominance-rebuild="' . $post->ID . '">';
        $html .= __('Regenerate the colors palette', 'webkew-image-dominant-color-generator');
        $html .= '</a> <sub>' . __('Requires closing and reopening the modal', 'webkew-image-dominant-color-generator') . '</sub>';
    }

    $html .= '<script>assignEventListeners();</script>';

    $form_fields['webkew-dominant-color-selected'] = array(
        'value' => get_post_meta($post->ID, 'webkew_dominant_color', true),
        'class' => 'webkew-dominant-color-selected',
        'input' => 'hidden',
    );

    $html_dominant = '<ul><li id="dominant-color" title="' . $dominant_color . '" style="background-color:' .
        $dominant_color . ';" ></li></ul>';
    $form_fields['dominant-color-selected'] = array(
        'value' => '',
        'input' => 'html',
        'class' => 'dominant-color-selected',
        'html' => $html_dominant,
        'label' => __('Selected dominant color:', 'webkew-image-dominant-color-generator'),
    );


    $form_fields['webkew-cifdc-color-palette'] = array(
        'value' => '',
        'input' => 'html',
        'html' => $html,
        'label' => __('Generated colors palette:', 'webkew-image-dominant-color-generator'),
    );
    return $form_fields;
}

add_filter('attachment_fields_to_save', 'save_dominant_color', 10, 2);
function save_dominant_color($post, $attachment)
{
    if (isset($attachment['webkew-dominant-color-selected'])) {
        if ($attachment['webkew-dominant-color-selected'] == 'build-colors-palette') {
            generate_dominant_color_and_colors_palette_for_uploaded_image($post['ID']);
        } else {
            update_post_meta($post['ID'], 'webkew_dominant_color', $attachment['webkew-dominant-color-selected']);
        }
    }
    return $post;
}


// Register the shortcode with WordPress
add_shortcode('webkew_dc', 'get_webkew_dominant_color_shortcode');

function get_webkew_dominant_color_shortcode($attributes)
{
    $attributes = shortcode_atts(array('field_name' => '', 'override_field' => ''), $attributes, 'webkew_dc');

    if (!array_key_exists('field_name', $attributes) || (!array_key_exists('override_field', $attributes))) {
        return __("All attributes field_name and override_field  must be present!", 'webkew-image-dominant-color-generator');
    }

    if (!is_singular()) {
        return __(
            'This shortcode can only be used on Custom Post Types, Posts, Pages or Attachments!',
            'webkew-image-dominant-color-generator'
        );
    }
    $post_id = get_the_ID();

// We have override_field filled. Then ignore anything else and return it.
    if (!empty($attributes['override_field'])) {
        //Assumption that this contains a Hex color code
        $dominant_color = get_post_meta($post_id, $attributes['override_field'], true);
        if (!empty($dominant_color)) {
            return esc_html($dominant_color);
        }
    }
    /**
     * We have a field name specified, and its value is "featured", then get the dominant color of the featured image
     * of the current post/page/custom post type
     * If the value is something else, then we know that this the name of a custom field (type image) attached to the
     * current post/page/custom post type, then get the dominant color of the linked image.
     **/
    if (!empty($attributes['field_name'])) {

        if ($attributes['field_name'] == 'featured') {
            // Get the image ID from the featured image
            $image_id = get_post_thumbnail_id($post_id);
        } else {
            // Get the image ID from the custom field
            $image_id = get_post_meta($post_id, $attributes['field_name'], true);
        }
        if ($image_id) {
            $dominant_color = get_dominant_color($image_id);
            if (!empty($dominant_color)) {
                return esc_html($dominant_color);
            }
        }
    }

// Otherwise return the fall-back color
    return esc_html(get_option('webkew_idcg_fallback_color_setting'));
}
