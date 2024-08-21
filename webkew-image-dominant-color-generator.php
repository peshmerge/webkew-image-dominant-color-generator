<?php
/*
Plugin Name: WebKew Image Dominant Color Generator
Description:Automatically generate a dominant color and a colors palette (6 colors) for any uploaded image to the WordPress media library.
Version: 1.0.0
Text Domain: webkew-image-dominant-color-generator
Author: Peshmerge Morad
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html
Author URI: https://peshmerge.io
*/
if (!defined('ABSPATH')) exit; // Exit if accessed directly

require __DIR__ . '/vendor/autoload.php';

use ColorThief\ColorThief;

//The number of colors in the colors palette.
const WKIDCG_COLORS_COUNT = 6;
// Any number between 0 and 10. 10 very high and this more computationally expensive.
const WKIDCG_COLORS_QUALITY = 5;

//Enqueue the CSS and JS files for handling the dominant color and the colors palette
add_action('admin_enqueue_scripts', 'wkidcg_enqueue_admin_scripts');
function wkidcg_enqueue_admin_scripts($hook_suffix)
{
    $screen = get_current_screen();
    //Enqueue the scripts when are dealing with custom post types, posts, pages and in the media library
    if ($screen && in_array($screen->base, ['post', 'upload', 'page'])) {
        wp_register_style(
            'wkidcg-admin-css',
            plugins_url('css/wkidcg-admin.css', __FILE__),
            [],
            1.0,
            false
        );
        wp_enqueue_style('wkidcg-admin-css');

        wp_register_script(
            'wkidcg-admin-js',
            plugins_url('js/wkidcg-admin.js', __FILE__),
            [],
            1.0,
            true
        );
        wp_enqueue_script('wkidcg-admin-js');
    }

    // Only load on the settings page
    if ('options-general.php' === $hook_suffix) {
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        wp_add_inline_script('wp-color-picker', '
        (function($) {
            "use strict";
            jQuery(document).ready(function($) {
                $(".wkidcg-fallback-color-setting-cl").wpColorPicker();
            });
        })(jQuery);
    ');
    }
}

add_action('admin_init', 'wkidcg_register_settings');
// Register the fallback color for the plugin.
function wkidcg_register_settings()
{
    register_setting('general', 'wkidcg_fallback_color_setting', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_hex_color',
        'default' => '#f9bc40',
    ));
}

// Add the color picker field to the settings page for the fallback color field, added previously
function wkidcg_settings_field()
{
    ?>
    <tr>
        <th scope="row"><label for="wkidcg_fallback_color_setting">
                <?php esc_html_e('Select a fallback color', 'webkew-image-dominant-color-generator') ?>
            </label>
        </th>
        <td>
            <input type="text" id="wkidcg_fallback_color_setting" name="wkidcg_fallback_color_setting"
                   value="<?php echo esc_attr(get_option('wkidcg_fallback_color_setting')); ?>"
                   class="wkidcg-fallback-color-setting-cl" data-default-color="#f9bc40"/>
        </td>
    </tr>
    <?php
}


// Add the fallback color field to the settings page
add_action('admin_init', function () {
    add_settings_field(
        'wkidcg_fallback_color_setting',
        __('WebKew Image Dominant Color Generator - Fallback color', 'webkew-image-dominant-color-generator'),
        'wkidcg_settings_field',
        'general'
    );
});


add_action('plugins_loaded', 'wkidcg_init');
// Load the plugin's text domain
function wkidcg_init()
{
    load_plugin_textdomain(
        'webkew-image-dominant-color-generator',
        false,
        plugin_basename(dirname(__FILE__)) . '/languages'
    );
}

add_action('add_attachment', 'wkidcg_generate_dominant_color_and_colors_palette_for_uploaded_image');
// Generate the dominant color and the colors palette when an attachment (image) is successfully added to the media lib.
function wkidcg_generate_dominant_color_and_colors_palette_for_uploaded_image($attachment_id)
{
    if (!wp_attachment_is_image($attachment_id)) {
        return "Attachment is not an image!";
    }
    try {
        $image = get_attached_file($attachment_id);
        $dominant_color = ColorThief::getColor($image, WKIDCG_COLORS_QUALITY, null, 'hex');
        $colors_palette = ColorThief::getPalette($image, WKIDCG_COLORS_COUNT, WKIDCG_COLORS_QUALITY, null, 'hex');
    } catch (Exception $exception) {
        return $exception;
    }
    // Save the generated dominant color and the colors palette for the uploaded media!
    update_post_meta($attachment_id, 'wkidcg_dominant_color', $dominant_color);
    update_post_meta($attachment_id, 'wkidcg_colors_palette', $colors_palette);
}


// Helper to fetch the colors palette of an attachment (image)
function wkidcg_get_colors_palette($attachment_id)
{
    $colors_palette = get_post_meta($attachment_id, 'wkidcg_colors_palette', true);
    if (!$colors_palette) {
        wkidcg_generate_dominant_color_and_colors_palette_for_uploaded_image($attachment_id);
        return get_post_meta($attachment_id, 'wkidcg_colors_palette', true);
    }
    return $colors_palette;
}

// Helper to fetch the dominant color of an attachment (image)
function wkidcg_get_dominant_color($attachment_id)
{
    return get_post_meta($attachment_id, 'wkidcg_dominant_color', true);
}


add_filter('attachment_fields_to_edit', 'wkidcg_add_colors_palette_fields', 10, 2);

// Add the dominant color and the colors palette and other options to the end of the attachment (image) frame.
function wkidcg_add_colors_palette_fields($form_fields, $post)
{
    if (!wp_attachment_is_image($post->ID)) {
        return;
    }
    $colors_palette = wkidcg_get_colors_palette($post->ID);
    $dominant_color = wkidcg_get_dominant_color($post->ID);

    if (!$colors_palette || !$dominant_color) {
        $html = __('No colors palette available.', 'webkew-image-dominant-color-generator');
        $html .= '<br /><a href="#" class="build-colors-palette" data-dominance-rebuild="';
        $html .= $post->ID . '" onclick="wkidcgBuildColorPalette(this)">';
        $html .= __('Generate colors palette', 'webkew-image-dominant-color-generator');
        $html .= '</a>';
    } else {
        $html_colors_palette_array = [];
        $html_colors_palette_array[] = "<ul>";
        foreach ($colors_palette as $color) {
            $html = '<li class="colors-palette-color" title="' . $color . '"data-color="' . $color . '"';
            $html .= 'style="background-color: ' . $color . '" onclick="wkidcgChangeSelectedDominantColor(this)"></li>';
            $html_colors_palette_array[] = $html;
        }
        $html_colors_palette_array[] = "</ul>";

        $html = '<div id="colors-palette-container">' . implode($html_colors_palette_array) . '</div>';
        $html .= '<br /><a href="#" class="build-colors-palette" data-dominance-rebuild="';
        $html .= $post->ID . '" onclick="wkidcgBuildColorPalette(this)">';
        $html .= __('Regenerate the colors palette', 'webkew-image-dominant-color-generator');
        $html .= '</a> <sub>' . __('Requires closing and reopening the modal',
                'webkew-image-dominant-color-generator') . '</sub>';
    }

    $form_fields['webkew-dominant-color-selected'] = array(
        'value' => get_post_meta($post->ID, 'wkidcg_dominant_color', true),
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

    $form_fields['wkidcg-color-palette'] = array(
        'value' => '',
        'input' => 'html',
        'html' => $html,
        'label' => __('Generated colors palette:', 'webkew-image-dominant-color-generator'),
    );
    return $form_fields;
}

add_filter('attachment_fields_to_save', 'wkidcg_save_dominant_color', 10, 2);
// Save the selected dominant color!
function wkidcg_save_dominant_color($post, $attachment)
{
    if (isset($attachment['webkew-dominant-color-selected'])) {
        if ($attachment['webkew-dominant-color-selected'] == 'build-colors-palette') {
            wkidcg_generate_dominant_color_and_colors_palette_for_uploaded_image($post['ID']);
        } else {
            update_post_meta($post['ID'], 'wkidcg_dominant_color', $attachment['webkew-dominant-color-selected']);
        }
    }
    return $post;
}


add_shortcode('webkew_dc', 'wkidcg_get_dominant_color_shortcode');
/**
 * Register the shortcode with WordPress. To be used as follows
 * [webkew_dc]: in this case the fallback color will be returned
 * [webkew_dc field_name=]: if field_name= "featured", the dominant color of the featured image is returned
 * [webkew_dc field_name=]: if field_name= "field_x", the dominant color of the image of that field is returned
 * [webkew_dc override_field=]: if override_field= "field_x", color given in that field_x will be picked up and returned.
 *
 * @param $attributes
 * @return string
 */
function wkidcg_get_dominant_color_shortcode($attributes)
{
    $attributes = shortcode_atts(array('field_name' => '', 'override_field' => ''), $attributes, 'webkew_dc');

    if (!array_key_exists('field_name', $attributes) || (!array_key_exists('override_field', $attributes))) {
        return __("All attributes field_name and override_field  must be present!",
            'webkew-image-dominant-color-generator');
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
            $dominant_color = wkidcg_get_dominant_color($image_id);
            if (!empty($dominant_color)) {
                return esc_html($dominant_color);
            }
        }
    }

    // Otherwise return the fall-back color
    return esc_html(get_option('wkidcg_fallback_color_setting'));
}
