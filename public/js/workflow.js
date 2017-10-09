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

        var other_tabs = $("div[data-tab][data-conn='" + id_conn + "']");

        var tab_index = (other_tabs.length) ? parseInt(other_tabs.last().attr('data-tab-index')) + 1 : 1;

        title.html(conn_name + "~" + tab_index + "&nbsp; <button class='ui small compact basic button btn-remove-worksheet'>x</button>").attr('data-tab', n);

        $("#worksheet-collector .worksheet-item-title").last().parent().append(title);

        var content = $("#worksheet-collector .worksheet-item-content").last().clone();
        content.empty().attr('data-conn', id_conn);
        content.empty().attr('data-tab-index', tab_index);

        $("#worksheet-collector .worksheet-item-content").last().parent().append(content);

        if (content.hasClass('active'))
            content.removeClass('active');

        content.attr('data-tab', n);
        content.load(content.attr("data-resource"), { id: n, conn: id_conn });

        $('.menu .item').tab();

        $(title).trigger('click');
    });

    $('.menu .item').tab();

    $("body").delegate(".btn-remove-worksheet", "click", function(event)
    {
        event.preventDefault();
        event.stopPropagation();

        var tab = $(this).parent().attr('data-tab');
        $("[data-tab='" + tab + "']").remove();
        $("a[data-tab='home']").trigger("click");
    });
});