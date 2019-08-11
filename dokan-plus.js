jQuery( document ).ready(function() {
    jQuery(".dokan-plus-locations-title").click(function () {
        if (!jQuery(".dokan-plus-locations-body").is(':visible')) {
            jQuery(".dokan-plus-locations-body").show(600);
        } else {
            jQuery(".dokan-plus-locations-body").hide(600);
        }
    });
});