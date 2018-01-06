$(function () {

  var totalPages = $('#oawc-widget .oawc-suggestion').length;
  var currentPage = 1;

  $('#oawc-widget .oawc-suggestion-total').text(totalPages - 1);
  $('#oawc-widget .oawc-suggestion-current').val(currentPage - 1);

  $('#oawc-widget .oawc-next').click(function (e) {
    e.preventDefault();

    if (currentPage == totalPages) {
      return;
    }

    currentPage++;
    updatePage();
  });

  $('#oawc-widget .oawc-prev').click(function (e) {
    e.preventDefault();

    if (currentPage == 1) {
      return;
    }

    currentPage--;
    updatePage();
  });

  $('#oawc-widget .oawc-first').click(function (e) {
    e.preventDefault();

    if (currentPage == 1) {
      return;
    }

    currentPage = 1;
    updatePage();
  });

  $('#oawc-widget .oawc-last').click(function (e) {
    e.preventDefault();

    if (currentPage == totalPages) {
      return;
    }

    currentPage = totalPages;
    updatePage();
  });

  $('#oawc-widget .oawc-jump').click(function (e) {
    e.preventDefault();

    var page = parseInt($('#oawc-widget input[name=oawc-jump-number]').val());

    if (!isNaN(page)) {
      page = page + 1; // Shift because the first page is displayed as 0

      if (page < 1) {
        page = 1;
      }
      if (page > totalPages) {
        page = totalPages;
      }

      currentPage = page;
    }
    else {
      currentPage = 1;
    }
    updatePage();
  });

  $('input[name=oawc-jump-number]').on("keypress", function(e) {
    if (e.keyCode == 13) {
      $('#oawc-widget .oawc-jump').click();
    }
  });

  $('#oawc-widget .oawc-sentence-see').click(function (e) {
    e.preventDefault();
    $('#oawc-widget .oawc-next').click();
  });

  // "Keep this word" button
  $('#oawc-widget .wc-poly').click(function (e) {
    var el = $(this);
    var word = el.attr('data-word');
    if (!word) {
      return;
    }

    $.ajax({
      type: 'POST',
      url: '//' + serviceUrl + '/oawc/service.php',
      data: JSON.stringify({command: 'ignore_poly', word: word}),
      processData: false,
      contentType: 'application/json',
      success: function (data, textStatus, jqXHR) {
        var parent = el.closest('.wc-poly-container');
        parent.find('.wc-poly').fadeOut();
        parent.find('.wc-synonym').fadeOut();
        parent.removeClass('wc-poly-container');
      },
      error: function (jqXHR, textStatus, errorThrown) {
        console.log('Request error: ' + errorThrown);
      }
      // dataType: dataType
    });
  });

  $('#oawc-widget .wc-poly-try-button').click(function (e) {
    var el = $(this);
    el.hide();
    el.parent().find('.wc-list').fadeIn();
  });

  $('#oawc-widget .wc-poly-replace .wc-close').click(function (e) {
    var parent = $(this).closest('.wc-poly-replace');

    parent.find('.wc-poly-try-button').show();
    parent.find('.wc-list').hide();
    parent.find('.wc-list-full').hide();
  });


  $('#oawc-widget .wc-show-more').click(function (e) {
    var el = $(this);
    var container = el.closest('.wc-poly-container');

    container.find('.wc-list').hide();
    container.find('.wc-list-full').fadeIn();
  });

  $('#oawc-widget .wc-replace-word').click(function (e) {
    var el = $(this);
    var text = el.text();
    var container = el.closest('.wc-poly-container');
    container.find('.wc-hinted').text(text);

    // Show 'try synonyms' button.
    container.find('.wc-list').hide();
    container.find('.wc-list-full').hide();
    container.find('.wc-poly-try-button').fadeIn();

    var original = container.find('.wc-hinted').attr('data-word');
    $.ajax({
      type: 'POST',
      url: '//' + serviceUrl + '/oawc/service.php',
      data: JSON.stringify({command: 'replace_poly', original: original, word: text}),
      processData: false,
      contentType: 'application/json',
      success: function (data, textStatus, jqXHR) {
      },
      error: function (jqXHR, textStatus, errorThrown) {
        console.log('Request error: ' + errorThrown);
      }
      // dataType: dataType
    });
  });

  function updatePage() {
    $('#oawc-widget .oawc-suggestion-current').val(currentPage - 1); // Page number display starts at 0.

    $('#oawc-widget .oawc-suggestion').hide();
    $('#oawc-widget .oawc-suggestion').eq(currentPage - 1).fadeIn('fast');

    $('#oawc-widget .oawc-nav').removeClass('inactive');

    if (currentPage == 1) {
      $('#oawc-widget .oawc-first').addClass('inactive');
      $('#oawc-widget .oawc-prev').addClass('inactive');
    }

    if (currentPage == totalPages) {
      $('#oawc-widget .oawc-last').addClass('inactive');
      $('#oawc-widget .oawc-next').addClass('inactive');
    }

  }

});
