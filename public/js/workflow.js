$(function(){

    $("body").delegate(".btn-new-worksheet", "click", function(event)
    {
        event.preventDefault();

        var id_conn   = $(this).attr('data-id');
        var conn_name = $(this).attr('data-name');

        var call = eval($(this).attr('data-callback')) || {};

        call.success = call.success || new Function();
        call.before  = call.before  || new Function();
        call.error   = call.error   || new Function();

        var d = new Date();
        var n = d.getTime();

        var title = $("#worksheet-collector .worksheet-item-title").last().clone();

        if (title.hasClass('active'))
            title.removeClass('active');

        title.text(conn_name + "~" + n).attr('data-tab', n);

        $("#worksheet-collector .worksheet-item-title").last().parent().append(title);

        var content = $("#worksheet-collector .worksheet-item-content").last().clone();
        content.empty().attr('data-conn', id_conn);

        $("#worksheet-collector .worksheet-item-content").last().parent().append(content);

        if (content.hasClass('active'))
            content.removeClass('active');

        content.attr('data-tab', n);
        content.load(content.attr("data-resource"), { id: n, conn: id_conn });
    });

    $("body").delegate(".ui.menu > .item", "click", function(event)
    {
        event.preventDefault();

        $(this).parent().find(".item").removeClass('active');
        $(this).parent().parent().find(".ui.tab").removeClass('active');

        $(this).addClass('active');

        var tab = $(this).attr('data-tab');

        $(this).parent().parent().find("[data-tab='" + tab + "']").addClass('active');
    });
});