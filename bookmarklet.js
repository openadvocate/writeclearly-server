/*
jQuery loader generated with:
http://benalman.com/code/test/jquery-run-code-bookmarklet/
*/
(function(e, a, g, h, f, c, b, d) {
    // Conditionally load jquery if not available.
    if (!(f = e.jQuery) || g > f.fn.jquery || h(f)) {

        c = a.createElement("script");
        c.type = "text/javascript";

        if (window.location.protocol == "https:") {
          c.src = "https://ajax.googleapis.com/ajax/libs/jquery/" + g + "/jquery.min.js";
        }
        else {
          c.src = "http://ajax.googleapis.com/ajax/libs/jquery/" + g + "/jquery.min.js";
        }

        c.onload = c.onreadystatechange = function() {
            if (!b && (!(d = this.readyState) || d == "loaded" || d == "complete")) {
                h((f = e.jQuery).noConflict(1), b = 1);
                f(c).remove()
            }
        };

        if (a.documentElement.childNodes[0].nodeName == 'HEAD' || a.documentElement.childNodes[0].nodeName == 'BODY') {
          a.documentElement.childNodes[0].appendChild(c)
        }
        else {
          // Unsure which element is the first child. Append to HTML.
          a.documentElement.appendChild(c)
        }
    }
})(window, document, "1.9.0", function($, L) {

    var serviceUrl = 'writeclearly.openadvocate.org';

    // Check if widget is already showing.
    if ($('#writeclearly-overlay-container').length) {
      return;
    }


    // Add css.
    var labelRules = 'position:absolute; left:-40px; top:0; letter-spacing:1px; background-color:#9933cc; -webkit-border-radius: 8px; border-radius: 8px; padding:0px 5px; margin:0; line-height: 24px; font-size: 14px; color:white; max-width: 32px; text-align: center;';
    var pRules = 'position:relative;';

    var style = document.createElement("style");
    style.appendChild(document.createTextNode("")); // WebKit hack
    document.head.appendChild(style);

    var sheet = document.styleSheets[(document.styleSheets.length - 1)];
    if('insertRule' in sheet) {
      sheet.insertRule('.oawc-paragraph .oawc-label' + '{' + labelRules + '}', sheet.cssRules.length);
      sheet.insertRule('.oawc-paragraph' + '{' + pRules + '}', sheet.cssRules.length);
    }
    else if('addRule' in sheet) {
      sheet.addRule('.oawc-paragraph .oawc-label', labelRules);
      sheet.addRule('.oawc-paragraph', pRules);
    }


    var content;
    var selectionOnly = true;

    // If there is any user selection, use that as content.
    var selection = '';
    if (typeof window.getSelection != "undefined") {
        selection = window.getSelection();
    } else if (typeof document.selection != "undefined") {
        selection = document.selection.createRange().text;
    }
    content = String(selection);

    if (!content) {
      // Try to determine content.
      var scope;
      selectionOnly = false;

      if ($('.openadvocate-content').length > 0) {
        // WC content.
        scope = $('.openadvocate-content');
      }
      else if ($('.page-node .node').length > 0) {
        // Drupal content (full node page).
        scope = $('.page-node .node');
      }
      else if ($('.page-node .field-name-body').length > 0) {
        // Drupal content (node body on non-node page).
        scope = $('.page-node .field-name-body');
      }
      else if ($('.single-post .post').length > 0) {
        // Wordpress content.
        scope = $('.single-post .post');
      }
      else {
        // Grab the whole body.
        scope = $('body');
      }

      // Add identifier to all paragraphs.
      var pId = 0;
      scope.find('p, li').each(function (i) {
        $(this).addClass('oawc-p-' + pId);
        $(this).addClass('oawc-paragraph');
        pId++;
      });

      // Try to remove unneccesary items.
      scope = scope.clone();
      scope.find('script').remove();
      scope.find('style').remove();
      scope.find('header').remove();
      scope.find('footer').remove();

      content = scope.html();

    }


    // Add widget container.
    $('<div id="writeclearly-overlay-container" style="-webkit-box-shadow: 0px 0px 5px 0px rgba(0,0,0,0.75); -moz-box-shadow: 0px 0px 5px 0px rgba(0,0,0,0.75); box-shadow: 0px 0px 5px 0px rgba(0,0,0,0.75); position: fixed; bottom:0px; left: 0px; width:100%; z-index:99999"><div id="writeclearly-overlay" style="background-color:#f5f5f1; background-image: url(http://writeclearly.openadvocate.org/oawc/img/loading.gif); background-repeat: no-repeat; background-position: 50% 50%; background-size: 30px 30px; border:1px solid black; height:250px"><a href="#" id="writeclearly-close" style="display:block; background-color: transparent; position: absolute; top:3px; right: 25px; z-index:100; color: #aa9; padding: 3px 5px; border: 1px solid #aa9;  border-radius:5px;  font-size: 14px;">Close &#10005;</a></div></div>').appendTo('body').fadeIn('fast');

    // Click handle for close link.
    $('#writeclearly-close').click(function (e) {
      e.preventDefault();
      e.stopPropagation();
      $('#writeclearly-overlay-container').remove();

      // Remove labels.
      $('.oawc-paragraph .oawc-label').remove();
    });

    // Trigger 'close' on esc keypress.
    $(document).keyup(function(e) {
      if (e.keyCode == 27) {
        $('#writeclearly-close').click();
      }
    });


    // Cookie helper functions.
    function createCookie(name, value, days) {
      var expires;

      if (days) {
        var date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        expires = "; expires=" + date.toGMTString();
      } else {
        expires = "";
      }
      document.cookie = escape(name) + "=" + escape(value) + expires + "; path=/";
    }

    function readCookie(name) {
      var nameEQ = escape(name) + "=";
      var ca = document.cookie.split(';');
      for (var i = 0; i < ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) === ' ') c = c.substring(1, c.length);
        if (c.indexOf(nameEQ) === 0) return unescape(c.substring(nameEQ.length, c.length));
      }
      return null;
    }


    // Load content.
    $.ajax({
      type: 'POST',
      url: '//' + serviceUrl + '/oawc/service.php',
      data: JSON.stringify({content: content, selection: selectionOnly, last: readCookie('wc_last_index')}),
      processData: false,
      contentType: 'application/json',
      timeout: 30000,
      success: function (data, textStatus, jqXHR) {
        $('#writeclearly-overlay').css('background-image', 'none');
        var index = $(data).find('#wc-fk-index').text();
        if (index) {
          createCookie('wc_last_index', index);
        }

        // Find paragraph ids in suggestions, and add label in origianl text.
        $(data).find('.oawc-card .oawc-sentence-id').each(function (i) {
          var id = $(this).attr('id');

          if (id) {
            var label = $(this).text();

            if ($('.' + id + ' .oawc-label').length) {
              // The paragraph already has a label; append to it.
              var currentLabel = $('.' + id + ' .oawc-label').text();

              currentLabel = currentLabel + ' ' + label;
              $('.' + id + ' .oawc-label').text(currentLabel);
            }
            else {
              $('.' + id).prepend('<span class="oawc-label" style="">' + label + '</span>');
            }

          }
        });

        var iframe = document.createElement('iframe');
        var html = data;

        iframe.style.width = '100%';
        iframe.style.height = '100%';
        iframe.style.border = 'none';
        iframe.style.background = '#f5f5f1';
        iframe.style.zIndex = 1;
        // iframe.src = 'data:text/html;charset=iso-8859-1,' + encodeURIComponent(html);

        document.getElementById('writeclearly-overlay').appendChild(iframe);

        // Write content to iframe.
        var iFrameWindow = iframe.contentWindow || iframe.documentWindow;
        var iFrameDoc = iFrameWindow.document;
        iFrameDoc.open();
        iFrameDoc.write(html);
        iFrameDoc.close();

      },
      error: function (jqXHR, textStatus, errorThrown) {
        console.log('Error: ' + errorThrown);
        $('#writeclearly-overlay').css('background-image', 'none').append('<div style="text-align: center; padding: 10px">Sorry, WriteClearly was unable to analyze this page. Please try again after later.</div>');

      }
    });


});
