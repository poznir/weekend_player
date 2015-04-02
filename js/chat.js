var Chat = {
    getInfo: function () {
        console.log('weee');
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
                if (data["members"]) {
                    redraw_members_list(data["members"]);
                }
            }
            return;
        }
        var chat_history = data["chat"];
        console.log(chat_history);

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
    }
}