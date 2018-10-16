/*global window*/
/*jslint browser, long*/

(function (window, $) {

    "use strict";

    var next = $("#next-number");

    $(".call_minus").click(function () {
        var num = next.val();

        if (num.length) {
            num = parseInt(num, 10) + 1999;
        } else {
            num = 1999;
        }

        next.val(num.toString().substring(1));
    });

    $(".call_plus").click(function () {
        var num = next.val();

        if (num.length) {
            num = parseInt(num, 10) + 1001;
        } else {
            num = 1001;
        }

        next.val(num.toString().substring(1));
    });

    $("li[data-number]").click(function (event) {
        var num = next.val();

        if (num.length >= 3) {
            num = num.substring(num.length - 2);
        }

        next.val(num + $(event.target).data("number"));
    });

    $(".call_number_delete").click(function () {
        var num = next.val();

        if (num.length) {
            num = Math.floor(parseInt(num, 10) / 10) + 1000;
        } else {
            num = 1000;
        }

        next.val(num.toString().substring(1));
    });

    $(".call_number_stop").click(function () {
        $.ajax("http://" + window.location.hostname + ":8000/stop-calling");
    });

    $("#call").click(function () {
        var num = next.val();

        if (num !== "000") {
            $.ajax("http://" + window.location.hostname + ":8000/calling?num=" + num);
        }
    });

    $.ajaxSetup({cache: false});

}(window, window.jQuery));
