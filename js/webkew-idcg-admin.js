function assignEventListeners() {
    jQuery('.build-colors-palette').on('click', function () {
        jQuery(this).html('Building Color Palette...');
        let currently_selected_color = jQuery('input[name*="webkew-dominant-color-selected"]');
        currently_selected_color.val('build-colors-palette');
        currently_selected_color.change();
        jQuery('#dominant-color').css("background-color", jQuery('input[name*="webkew-dominant-color-selected"]').val());

    });
    jQuery('.colors-palette-color').on('click', function () {
        element = jQuery(this);
        let currently_selected_color = jQuery('input[name*="webkew-dominant-color-selected"]');
        currently_selected_color.val(element.data('color'));
        currently_selected_color.change();

        // Change the current dominant color based on user selection.
        jQuery('#dominant-color').css("background-color", element.data('color'));
        jQuery('#dominant-color').attr('data-col', element.data('color'));
        jQuery(element.parent().find('.selected')).removeClass('selected');
        jQuery(element).addClass('selected');
    });
}
