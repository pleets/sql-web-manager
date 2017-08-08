$(function(){

    /**
     *  Submit forms with AJAX
     */
    $("body").delegate("form[data-role='ajax-request']", "submit", function(event)
    {
        event.preventDefault();

        var formObject = $(this);

        formObject.find("input").attr("readonly", "readonly");
        formObject.find("select").attr("readonly", "readonly");
        formObject.find("button[type='submit']").attr("disabled", "disabled");

        var url  = $(this).attr('action');
        var type = $(this).attr('method');
        var box  = $(this).attr('data-response');
        var data = $(this).attr('data-object');

        var call = eval($(this).attr('data-callback')) || {};

        call.success = call.success || new Function();
        call.before  = call.before  || new Function();
        call.error   = call.error   || new Function();

        var form_data = $(this).serializeArray();

        var parsed = eval(data);

        for (var i in parsed)
        {
            form_data.push({ name: i, value: parsed[i] });
        }

        $.ajax({
            url: url,
            type: type,
            data: form_data,
            beforeSend: function() {
                $(box).html("&nbsp; <span class='$ rotate-x infinite fast' style='display: inline-block'><i class='icon idea' aria-hidden='true'></i></span> Cargando...");
                call.before();
            },
            error: function(jqXHR, textStatus, errorThrown)
            {
                $(box).html("Error processing request!. " + errorThrown);

                var e = {};
                e.jqXHR = jqXHR;
                e.textStatus = textStatus;
                e.errorThrown = errorThrown;

                call.error(e);
            },
            success: function(data)
            {
                $(box).html(data);
                call.success(data);
            },
            complete: function(data)
            {
                formObject.find("input").removeAttr("readonly");
                formObject.find("select").removeAttr("readonly");
                formObject.find("button[type='submit']").removeAttr("disabled");
            }
        });
    });

    /**
     *  General AJAX request
     */
    $("body").delegate(":not(form)[data-role='ajax-request']", "click", function(event)
    {
        event.preventDefault();

        var url  = $(this).attr('data-url');
        var type = $(this).attr('data-type');
        var box  = $(this).attr('data-response');
        var data = $(this).attr('data-object');
        var frm  = $(this).attr('data-form');

        var call = eval($(this).attr('data-callback')) || {};

        call.success = call.success || new Function();
        call.before  = call.before  || new Function();
        call.error   = call.error   || new Function();

        var form_data = $(frm).serializeArray();

        var parsed = eval(data);

        for (var i in parsed)
        {
            form_data.push({ name: i, value: parsed[i] });
        }

        $.ajax({
            url: url,
            type: type,
            data: form_data,
            beforeSend: function() {
                $(box).html("&nbsp; <span class='$ rotate-x infinite fast' style='display: inline-block'><i class='icon idea' aria-hidden='true'></i></span> Cargando...");
                call.before();
            },
            error: function(jqXHR, textStatus, errorThrown)
            {
                $(box).html("Error processing request!. " + errorThrown);

                var e = {};
                e.jqXHR = jqXHR;
                e.textStatus = textStatus;
                e.errorThrown = errorThrown;

                call.error(e);
            },
            success: function(data)
            {
                $(box).html(data);
                call.success(data);
            }
        });
    });
});