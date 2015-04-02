(function($) {
    $.sanitize = function(input) {
        var output = input.replace(/<script[^>]*?>.*?<\/script>/gi, '').
                     replace(/<[\/\!]*?[^<>]*?>/gi, '').
                     replace(/<style[^>]*?>.*?<\/style>/gi, '').
                     replace(/<![\s\S]*?--[ \t\n\r]*>/gi, '');
        return output;
    };
})(jQuery);

var Chat = {
    getInfo: function () {
    },

    doPolling: function () {
        (function poll_data() {
            if (unloading_page) {
                return;
            }
            last_poll_request = $.ajax({
                url: "server.php?" + generate_ajax_key(),
                type: "POST",
                data: {
                    "id": 1,
                    "task": "poll",
                    "update_version": DT_update_version
                },
                success: function(data) {
                    parsePollingData(data);
                },
                dataType: "json",
                complete: function() {
                    setTimeout(poll_data, 1000);
                },
                timeout: 15000
            });
        })();
    },

    parsePollingData: function (data) {
        if (!data || data["timeout"] == true) {
            if (data) {
                if (data["chat"]) {
                    update_chat(data["chat"]);
                }
            }
            return;
        }
        var chat_history = data["chat"];
    },

    send_chat: function (text) {
        $.ajax({
            url: "server.php?" + generate_ajax_key(),
            type: "POST",
            data: {
                "id": room_id,
                "task": "chat",
                "kind": "add",
                "text": text
            },
            complete: function(data) {
                $("#div_loading_area").addClass("add_new_form_loading_hide");
            },
            dataType: "json",
            timeout: 60000
        });
    },

    update_chat: function (list) {
        var table_row = "";
        for (var i in list) {
            var one = list[i];
            var id = one["id"];
            var text = one["text"];
            var user_name = one["name"];
            var time = one["timestamp"];

        date = new Date(time);
        text = $.sanitize(text);
        table_row += "<tr>" +
        "<td>" + date + "</td>" +
        "<td>" + user_name + "</td>" +
        "<td>" + text + "</td>" +
        "</tr>";
        // table_row += "<tr" + tr_class + ">" +
        //     "<td>" + title + "</td><td>" + length_to_time(length) + "</td><td>" + user_name + "</td>" +
        //     "<td>" +
        //       "<a href='#'><span class='glyphicon glyphicon-thumbs-up' aria-hidden='true' onclick='vote_video(" + song_id + ", 1)'></span></a>" +
        //       " <span class='badge'>" + votes + "</span> " +
        //       "<a href='#'><span class='glyphicon glyphicon-thumbs-down' aria-hidden='true' onclick='vote_video(" + song_id + ", -1)'></span></a>" +
        //     "</td>" +
        //   "</tr>";

        }

        $("#chat-table-data").html(table_row);

        //update chat message counter
        $("#chat_message_count").text(list.length);
    }
}