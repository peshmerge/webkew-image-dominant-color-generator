=== WebKew Image Dominant Color Generator ===

Contributors: peshmerge
Tags: custom-field, image, color, dominant-color, colors-palette
Donate link: https://buymeacoffee.com/peshmerge
Requires at least: 5.5
Tested up to: 6.6
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

A WordPress plugin that automatically generates a dominant color & a colors palette (6 colors) for any uploaded image to the WordPress media library.

== Description ==
A WordPress plugin that automatically generates a colors palette  (6 colors) for any uploaded image to the WordPress media
library. In addition, to the colors palette, it generates a dominant color which can be overridden by color from the generated colors palette.
All generated colors are hexadecimal values.

This plugin provides a shortcode [webkew_dc field_name="" override_field=""] which can returns a dominant color of any specific image specified using the shortcode parameters.
One use-case of this shortcode is the following: You are a web agency, and you want to create webpage for every client's project you have built. You want to give each page a background color that's based on the color of the client's brand/logo or any specific color you specify.
Instead of assigning the colors manually, this plugin will generate the dominant color automatically for you and the shortcode will provide you with that color.
The shortcode gives you also some flexibility by using parameters when using the shortcode:
1. field_name: this could be the name of image custom field or the string "featured" to select the featured image of the post/page/custom post type. The dominant color of the image specified here will be returned as a hexadecimal value.
2. override_field: ignore the dominant color provided by the previous field and use the name of another custom field in post/page/custom post type. The color specified in this field will be returned as a hexadecimal value.

If the image specified in `field_name` doesn't have a dominant color, and the `override_field` is empty, the shortcode will return the value of the fallback color which can be set in the WordPress admin panel under Settings --> General section.

== Installation ==
Install the plugin and start using it!

== Screenshots ==

1. Dominant color and colors palette generated for an uploaded image in the WordPress media library.
2. The shortcode `[webkew_dc field_name=featured]` being used to return the dominant color of the featured image of a post.
3. On the front-end, the shortcode returns the dominant color of that featured image.
4. Overriding the dominant color by another color from a custom field (color_picker in our case) by specifying its name override_field=color_picker
5. On the front-end, the shortcode ignores field_name and returns the color value specified using override_field.
6. Returning the dominant color of an image custom field (my_image in our case) by specifying its name in the shortcode field_name=my_image
7. On the front-end, the shortcode returns the dominant color of that image custom field.
8. Specifying a fallback color for the plugin. This will be used if no other option is available.
9. Using the shortcode without any parameters, will result in returning the fallback color.
10. On the front-end, the fallback color is returned.
11. Specifying a different dominant color from the colors palette.

== Changelog ==

= 1.0.0 =
* Plugin released.