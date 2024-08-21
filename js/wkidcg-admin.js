// Function to trigger building the colors alette
function wkidcgBuildColorPalette(element){
    element = jQuery(element);
    element.html('Building Color Palette...');
    let currently_selected_color = jQuery('input[name*="webkew-dominant-color-selected"]');
    currently_selected_color.val('build-colors-palette');
    currently_selected_color.change();
    jQuery('#dominant-color').css("background-color", jQuery('input[name*="webkew-dominant-color-selected"]').val());
}
// Change the selected dominant color for any given image from the colors palette
function wkidcgChangeSelectedDominantColor(element) {
    element =  jQuery(element);
    let currently_selected_color = jQuery('input[name*="webkew-dominant-color-selected"]');
    currently_selected_color.val(element.data('color'));
    currently_selected_color.change();
    // Change the current dominant color based on user selection.
    jQuery('#dominant-color').css("background-color", element.data('color'));
    jQuery('#dominant-color').attr('data-col', element.data('color'));
    jQuery(element.parent().find('.selected')).removeClass('selected');
    jQuery(element).addClass('selected');
}