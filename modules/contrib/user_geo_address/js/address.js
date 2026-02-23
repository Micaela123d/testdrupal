(function ($, Drupal, drupalSettings) {

    'use strict';

    Drupal.behaviors.address = {
        attach: function (context, drupalSettings) {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(showLocation);
            } else {
                $('#location').html('Geolocation is not supported by this browser.');
            }
            function showLocation(position)
            {
                    var latitude = position.coords.latitude;
                    var longitude = position.coords.longitude;
                    $.ajax(
                        {
                            type:"POST",
                            url: drupalSettings.path.baseUrl + "get-address/" + latitude + '/' + longitude,
                            contentType: "application/json;charset=utf-8",
                            dataType: "json",
                            success: function (data) {
                                var address = data;
                                $('#location').replaceWith(address);
                            },
                            error: function (ts) {
                                $('#location').replaceWith("Lacation is not updated."); }
                        }
                    );
            }
        }
    };
})(jQuery, Drupal, drupalSettings);
