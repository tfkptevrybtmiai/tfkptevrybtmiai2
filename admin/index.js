/*global window*/
/*jslint browser, long, multivar*/

(function (window, $) {

    "use strict";

    var form, onSuccess, test;

    form = $("form").submit(function () {
        var data = {};

        form.serializeArray().forEach(function (input) {
            data[input.name] = input.value;
        });

        $.ajax({
            contentType: "application/json",
            data: JSON.stringify(data),
            success: onSuccess,
            type: "POST",
            url: "configure.php"
        });

        return false;
    });

    onSuccess = function (response) {
        if (response.error) {
            window.alert(response.error);
        } else {
            $.LoadingOverlay("show");

            setTimeout(test, 3000);
        }
    };

    test = function () {
        $.ajax("index.js").done(function () {
            window.location.reload();
        }).fail(function () {
            setTimeout(test, 3000);
        });
    };

    $(".ip").on("input", function (event) {
        var input, next, values;

        input = $(event.target);
        next = input;

        input.val().split(".").forEach(function (text) {
            var num = text.replace(/[^0-9]/g, "").trim();

            next.focus();

            if (num.length) {
                if (num.length > 3) {
                    num = num.substring(num.length - 3);
                }

                num = parseInt(num, 10);

                if (num < 0) {
                    num = 0;
                } else if (num > 255) {
                    num = 255;
                }

                next.val(num);
                next = next.nextAll("input").first();
            }
        });

        values = [];

        if (next === input) {
            next.val("");
        } else {
            input.parent().children("input").each(function (ignore, node) {
                if (node.value.length) {
                    values.push(node.value);
                }
            });
        }

        if (values.length === 4) {
            values = values.join(".");
        } else {
            values = "";
        }

        input.parent().prev().val(values);
    });

    $("input[data-target]").change(function (event) {
        var target = $("." + $(event.target).data("target"));

        if (event.target.value === "dhcp") {
            target.addClass("hide").find("input.req").prop("required", false);
        } else if (event.target.value === "static") {
            target.removeClass("hide").find("input.req").prop("required", true);
        }
    });

    $.ajaxSetup({cache: false});

    $.LoadingOverlaySetup({
        color: "rgba(255,255,255,.3)",
        fade: [0, 500]
    });

}(window, window.jQuery));
