$(function() {
  $(".retweet-highlighter").click(function(event) {
    // Package POST data for sending.
    var dataString = "title=" + event.target.id;
    $.ajax({
      type: "POST",
      url: "/twitter/retweet/",
      data: dataString,
      success: function(result) {
       	if(result.substring(0,9) == "The birds") {
          alert(result);
          return;
        }
        $("#"+event.target.id+"-message").html("<span id=\"highlighter\">" + result + "</span>");
        $("#retweet-highlighter").html("<span id=\"highlighter\">" + result + "</span>");
        $("#"+event.target.id).html("");
      },
      error: function(result) {
        $("#"+event.target.id).html("<span id=\"highlighter\">Tweet fail.</span>");
      }
    });
    return false;
  });
});

$(function() {
  $(".full-retweet-highlighter").click(function(event) {
    // Package POST data for sending.
    var dataString = "title=" + event.target.id.replace("-retweet", "");
    $.ajax({
      type: "POST",
      url: "/twitter/retweet/",
      data: dataString,
      success: function(result) {
        $("#"+event.target.id).html("<span id=\"highlighter\">" + result + "</span>");
      },
      error: function(result) {
        $("#"+event.target.id).html("<span id=\"highlighter\">Tweet fail.</span>");
      }
    });
    return false;
  });
});

$(function() {
  $(".playback-highlighter").click(function(event) {
    // Package POST data for sending.
    var dataString = "title=" + event.target.id;
    $.ajax({
      type: "POST",
      url: "/playback/playpause/",
      data: dataString,
      success: function(result) {
       	if(result == "paused" || result == "play") {
          $("#"+event.target.id).html("OFF");
        } else if(result == "pause") {
          $("#"+event.target.id).html("ON");
        }
      },
      error: function(result) {
        alert("Fail. Tweet us @feedzebirdshelp for assistance.");
      }
    });
    return false;
  });
});


$(function() {
  $(".playback-cancel").click(function(event) {
    var answer = confirm ("Are you sure you want to cancel this campaign?"
                   + "The campaign will be stopped, and remaining funds will be sent to "
                   + "your receiving address.");
    if (!answer) {
      return;
    }

    // Package POST data for sending.
    var dataString = "title=" + event.target.id.replace("-cancel", "");
    $.ajax({
      type: "POST",
      url: "/playback/cancel/",
      data: dataString,
      success: function(result) {
       	if(result == "canceled") {
          alert("Your campaign has been canceled. Remaining funds will be refunded to you.");
        } else {
          alert("Could not cancel campaign. Tweet us @feedzebirdshelp for assistance. " + result);
        }
      },
      error: function(result) {
        alert("Fail. Tweet us @feedzebirdshelp for assistance. " + result);
      }
    });
    return false;
  });
});

$(function() {
  $(".changecpm").click(function(event) {
    var title = event.target.id.replace("-modify", "");
    var dataString = "cpm=" + $("#" + title + "-cpmin").val() + "&title=" + title;
    $.ajax({
      type: "POST",
      url: "/playback/modify/",
      data: dataString,
      success: function(result) {
        if(result == "badcpm") {
          alert("Cost Per Mille must be a number greater than 0.00001.");
        } else {
          alert("CPM changed.");
       	}
      },
      error: function(result) {
        alert("Error modifying campaign. Please refresh the page and try again. If the error persists, please let us know.");
      }
    });
    return false;
  });
});
