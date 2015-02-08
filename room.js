var mark_play_next = true;
var timer_play_next = null;
var last_sync_time = 0;
var is_room_admin = false;
var room_id = "";
var DT_update_version = "";
var DT_currently_playing_id = "";
var DT_currently_playing_data = {};
var tag = document.createElement('script');

tag.src = "https://www.youtube.com/iframe_api";
var firstScriptTag = document.getElementsByTagName('script')[0];
firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);

var player;

function onYouTubeIframeAPIReady() {
    player = new YT.Player('player', {
        height: '390',
        width: '640',
        videoId: 'M7lc1UVf-VE',
        events: {
            'onReady': onPlayerReady,
            'onStateChange': onPlayerStateChange,
            'onError': onPlayerError
        }
    });
}

function onPlayerReady(event) {
    if (!is_room_admin) {
        player.mute(); // mute by default for none admin users.. changeable by clicking on unmute in the player
    }
    doPolling();
}

function onPlayerError(event) {
    var reason = "";
    switch (event.data) {
        case "2":
            reason = "The request contains an invalid parameter value";
            break;
        case "100":
            reason = "The video requested was not found";
            break;
        case "101":
        case "150":
        default:
            reason = "The owner of the requested video does not allow it to be played in embedded players";
            break;
    }
    console.log("error: " + event.data, reason);
    admin_report("player_error", reason);
}

function onPlayerStateChange(event) {
    console.log("stateChanged : " + event.data);
    switch (event.data) {
        case -1:
            // unstarted
            break;
        case YT.PlayerState.PLAYING:

            break;
        case YT.PlayerState.PAUSED:

            break;
        case YT.PlayerState.ENDED:
            admin_report("player_end");
            break;
        case YT.PlayerState.BUFFERING:
            break;
        case YT.PlayerState.CUED:
            break;
    }
}

function playVideo(id) {
    _loadVideo(id);
    _playVideo();
}

function _loadVideo(id) {
    player.loadVideoById(id, 0)
}

function _playVideo() {
    player.playVideo();
}

function _stopVideo() {
    player.stopVideo();
}
var unloading_page = false;
var last_poll_request = null;

function doPolling() {
    (function poll_data() {
        if (unloading_page) {
            return;
        }
        last_poll_request = $.ajax({
            url: "server.php?" + generate_ajax_key(),
            type: "POST",
            data: {
                "id": room_id,
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
}

window.onbeforeunload = function() {
    unloading_page = true;
    try {
        _stopVideo();
        last_poll_request.abort();
    } catch (e) {}
}

function generate_ajax_key() {
    return Date.now() + "." + Math.random() * Date.now();
}

function parsePollingData(data) {
    if (!data || data["timeout"] == true) {
        return;
    }
    DT_update_version = data["update_version"];
    var currently_playing_id = data["currently_playing_id"];
    var playlist = data["playlist"];
    var history = data["history"];
    var members = data["members"];
    for (var i in playlist) {
        var one = playlist[i];
        if (one["id"] == currently_playing_id) {
            is_song_changed(currently_playing_id, one);
            break;
        }
    }
    update_lists_info(playlist, history, members);
}

function is_song_changed(id, data) {
    if (id == DT_currently_playing_id) {
        return
    }
    DT_currently_playing_id = id;
    DT_currently_playing_data = data;
    playVideo(data["v"]);
    update_current_info(data);
}

function update_lists_info(playlist, history, members) {
    $("#div_history")[0].innerHTML = "";
    $("#div_playlist")[0].innerHTML = "";
    redraw_list($("#div_history")[0], history, false);
    redraw_list($("#div_playlist")[0], playlist, true);
    redraw_members_list(members);
}

function create_inline_div(text, className, title) {
    var el = $("<div>")[0];
    el.innerHTML = text;
    if (title) {
        el.title = title;
    }
    el.className = className || "";
    el.className += " item_inline_div";
    return el;
}

function redraw_members_list(members) {
    // last_update, member_email, member_name
    var container = $("#room_members_list")[0];
    // clear old data
    container.innerHTML = "";

    for (var i in list) {
        var one = list[i];
        var li = $("<li>")[0];
        li.innerText = one["member_name"];
        li.title = one["member_email"] + " (last update before " + one["last_update"] + " seconds)";
        container.appendChild(li);
    }
    $("#room_members_list_head_count")[0].innerText = members.length;
}

function redraw_list(div, list, inc_counter) {

    var counter = inc_counter ? 0 : list.length + 1;
    for (var i in list) {
        var one = list[i];
        var id = one["id"];
        if (DT_currently_playing_id == id) {
            continue;
        }
        inc_counter ? counter++ : counter--;
        var v = one["v"];
        var title = one["title"];
        var added_by_email = one["added_by_email"];
        var datetime = one["datetime"];
        var skip_reason = one["skip_reason"];
        var user_name = one["user_name"];
        var length = one["length"];
        var youtube_url = "https://www.youtube.com/watch?v=" + v;

        var div_container = $("<div>")[0];
        div_container.appendChild(create_inline_div(counter + "."));
        div_container.appendChild(create_inline_div("<a target='_blank' href='" + youtube_url + "'>" + title + "</a>", "room_list_title"));
        div_container.appendChild(create_inline_div("[" + length_to_time(length) + "]", "room_song_time"));
        div_container.appendChild(create_inline_div("(" + user_name + ")", "", added_by_email));
        if (skip_reason && skip_reason != "played") {
            div_container.appendChild(create_inline_div("(" + skip_reason + ")", "item_inline_skipreason"));
        }
        div.appendChild(div_container);
    }
}

function update_current_info(one) {
    $("#current_song_title")[0].innerHTML = "";
    var v = one["v"];
    var title = one["title"];
    var added_by_email = one["added_by_email"];
    var datetime = one["datetime"];
    var skip_reason = one["skip_reason"];
    var user_name = one["user_name"];
    var length = one["length"];

    var container = $("#current_song_title")[0];
    container.appendChild(create_inline_div(title, "room_current_title"));
    container.appendChild(create_inline_div("[" + length_to_time(length) + "]", "room_song_time"));
    container.appendChild(create_inline_div("(" + user_name + ")", "", added_by_email));
}

function length_to_time(length) {
    length = length + "";
    return length.toHHMMSS();
}

String.prototype.toHHMMSS = function() {
    var sec_num = parseInt(this, 10); // don't forget the second param
    var hours = Math.floor(sec_num / 3600);
    var minutes = Math.floor((sec_num - (hours * 3600)) / 60);
    var seconds = sec_num - (hours * 3600) - (minutes * 60);

    if (hours < 10) {
        hours = "0" + hours;
    }
    if (minutes < 10) {
        minutes = "0" + minutes;
    }
    if (seconds < 10) {
        seconds = "0" + seconds;
    }
    var time = (hours != "00" ? hours + ':' : "") + minutes + ':' + seconds;
    return time;
}


function admin_report(kind, reason) {
    if (!is_room_admin) {
        return
    }
    if (!reason) {
        reason = "";
    }
    $.ajax({
        url: "server.php?" + generate_ajax_key(),
        type: "POST",
        data: {
            "id": room_id,
            "task": "report",
            "kind": kind,
            "reason": reason
        },
        success: function(data) {
            // no return data
        },
        dataType: "json",
        timeout: 60000
    });
}

function add_youtube_video(address) {
    var v = extract_url_yt(address);
    if (!v) {
        alert("The video url is invalid");
        return;
    }

    $("#div_loading_area").removeClass("add_new_form_loading_hide");
    $.ajax({
        url: "server.php?" + generate_ajax_key(),
        type: "POST",
        data: {
            "id": room_id,
            "task": "client",
            "kind": "add",
            "video_id": v
        },
        complete: function(data) {
            $("#div_loading_area").addClass("add_new_form_loading_hide");
        },
        dataType: "json",
        timeout: 60000
    });
}

function extract_url_yt(url) {
    var expr = /[a-zA-Z0-9\-\_]{11}/;
    var result = expr.exec(url);
    if (result.length > 0) {
        return result[0];
    }
    return false;
}
