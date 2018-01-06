<?php

define('DICTIONARY_API_KEY', 'INSERTKEY');
require "conf.php";
require "functions.php";

error_reporting(E_ALL ^ E_DEPRECATED);

session_start(array('cookie_lifetime' => 86400));
$post = file_get_contents('php://input');
if (empty($post)) {
  exit();
}
$input = json_decode($post, TRUE);

// The input should contain json data, which may contain a 'command' attribute.
// Available commands:
// ignore_poly: Saves a polysyllable word that the user chooses to ignore.
// replace_poly: Saves a replacement that the user has picked for a polysyllable.
if (isset($input['command'])) {
  if ($input['command'] == 'ignore_poly') {
    save_ignored_poly($input['word']);
  }

  if ($input['command'] == 'replace_poly') {
    save_replaced_poly($input['original'], $input['word']);
  }

  return;
}
// Otherwise, the default command is to analyze the submitted text.


$results = analyze_text($input['content']);

/**
 * Analyzes the text and returns the FKGL score and suggestions.
 * Algorithm developed by WriteClearly.org.
 */
function analyze_text($input) {
  $results = array();

  if (empty($input['content'])) {
    // No data posted. Set defaults.
    $results['FKGL'] = 0;
    $results['suggestions'] = array();
    $results['overall_summary'] = 'No suggestions.';
    return $results;
  }

  $suggestions = array();

  $overall_has_long_sentence = FALSE;
  $overall_has_poly = FALSE;
  $overall_has_bad_word = FALSE;
  $overall_has_click_here = FALSE;
  $overall_has_gender = FALSE;
  $overall_has_all_caps = FALSE;
  $overall_has_img_without_alt = FALSE;
  $overall_has_long_p = FALSE;
  $overall_has_underlined = FALSE;

  // Load abbreviations. Removing the period from common abbreviations to help sentence recognition.
  $abbrevs = file("abbreviations.txt");
  $gabbrevs = array();
  foreach ($abbrevs as $key => $abbrev) {
    $abbrevs[$key] = trim($abbrev);
    $gabbrevs[$key] = trim(str_replace(".", " ", $abbrev));
  };

  // Keep track of totals for index calculation.
  $total_sentences = 0;
  $total_word_count = 0;
  $total_syl = 0;
  $total_poly = 0;

  $gender_words = array(
    'he/she',
    'she/he',
    'he or she',
    'she or he',
    'his/her',
    'her/his',
    'his or her',
    'her or his',
    'him/her',
    'her/him',
    'him or her',
    'her or him',
    'husband/wife',
    'wife/husband',
    'husband or wife',
    'wife or husband',
    'boyfriend/girlfriend',
    'girlfriend/boyfriend',
    'boyfriend or girlfriend',
    'girlfriend or boyfriend',
  );

  // Fetch the list of words that the current user has already chosen to ignore.
  $ignored_polys = get_ignored_polys();
  $ignored_polys = array_merge($ignored_polys, $gender_words);

  // Check for <img> tags without alt text.
  if (preg_match_all('#<img[^>]+>#i', $input['content'], $imgs)) {
    foreach ($imgs[0] as $img) {
      if (!preg_match('#alt *=#', $img)) {
        $overall_has_img_without_alt = TRUE;
      }
    }
  }

  // Check for underlined text.
  if (preg_match('#<u[^>]*>#i', $input['content'])) {
    $overall_has_underlined = TRUE;
  }

  // Remove all tags except <p> and <li>. Those are used to position labels.
  // <a> is left for link detection, but removed later.
  $input['content'] = preg_replace('#</?(?!li[ >])(?!p[ >])(?!a[ >])[^<>]*>#i', '', $input['content']);

  // Split the input on <p> tags, and store paragraph ids.
  $chunks = preg_split('#(</?(p|li)[^>]*>)#i', $input['content'], 0, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
  // Example input: aaa<p class="oawc-p-0">bbb</p><p class="oawc-p-1">ccc</p>ddd<p class="oawc-p-2">eee</p>
  //
  // Out: array(
  //   aaa
  //   <p class="oawc-p-0">
  //   bbb
  //   </p>
  //   <p class="oawc-p-1">
  //   ccc
  //   </p>
  //   ddd
  //   <p class="oawc-p-2">
  //   eee
  //   </p>
  // )


  // Collect paragraps, along with their IDs.
  // The IDS (oawc-p-[id] classes) were added by the javascript.
  $current_id = '';
  $paragraphs = array();

  foreach ($chunks as $chunk) {
    if (preg_match('#<(p|li)[^>]*?>#', $chunk, $m)) {
      // Stepped into a paragraph.

      if (preg_match('#(oawc-p-[0-9]+)#', $chunk, $m)) {
        $current_id = $m[0];
      }
      else {
        // P does not have a oawc class.
        $current_id = '';
      }

      continue;
    }

    if (preg_match('#</(p|li)[^>]*>#', $chunk, $m)) {
      // Stepped out of a paragraph
      $current_id = '';
      continue;
    }

    $paragraphs[] = array(
      'content' => $chunk,
      'id' => $current_id,
    );

  }

  foreach ($paragraphs as $paragraph) {
    $data = $paragraph['content'];

    $data = remove_smart_quotes($data);

    // Replace abbreviations.
    $data = str_replace($abbrevs, $gabbrevs, $data);

    // Replacing bad abbreviations with good ones that don't fool the exploder
    // Removing a2j variables
    $data = preg_replace("/\%\%[\[\]\(\)\_\#\-0-9A-Za-z ]{1,}\%\%/", "[___]", $data);


    $data = preg_replace("/>/", "> ", $data);

    // Check for long paragraph.
    $words = explode(" ", strip_tags($data));
    if (count($words) > 250) {
      $overall_has_long_p = TRUE;
    }

    // Split sentences at periods, multiple periods, question marks, exclamation
    // marks and hyphens.
    $sentences = preg_split("/(\.+ |\.+\"|\?[ ]*|!+[ ]*|\.[ ]{1,}|\.?\n)|\- /", trim(strip_tags($data)), -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
    $test_array = array();

    foreach ($sentences as $num => $sentence) {
      $sentence = trim($sentence);

      // Ignore too short sentences.
      if (strlen($sentence) > 3) {
        $total_sentences++;

        if ($num < (count($sentences) - 1)) {
          array_push($test_array, $sentence . $sentences[$num + 1]);
        }
        else {
          array_push($test_array, $sentence);
        }

      }

    }

    $text = strip_tags($data);
    $total_word_count += str_word_count($text);

    // Analyze text in this paragraph.
    if (count($test_array) > 0) {
      // Count syllables for use in index later.
      foreach ($sentences as $key => $sentence) {
        $sentence = trim($sentence);
        $words = explode(" ", $sentence);

        foreach ($words as $word) {
          $total_syl = $total_syl + count_syllables($word);
          if (count_syllables($word) > 2) {

            // Count polysyllablic words
            $total_poly++;
          }
        }
      }

      $badwords = file("en_bad.txt");
      $goodwords = file("en_good.txt");

      foreach ($test_array as $key => $sentence) {
        $CPW = round(strlen(str_replace(" ", "", $sentence)) / (str_word_count($sentence) + 0.1), 2);
        $tooltips = $sentence;
        $suggestion_meta = array();
        $words = explode(" ", $sentence);
        $score = $CPW * str_word_count($sentence);
        $syl_count = 0;

        $has_long_sentence = FALSE;
        $has_poly = FALSE;
        $has_bad_word = FALSE;
        $has_click_here = FALSE;
        $has_gender = FALSE;

        // Check sentence length.
        if (count($words) > 30 || strlen($sentence) > 200) {
          $has_long_sentence = TRUE;
          $overall_has_long_sentence = TRUE;
        }

        // Check for polysyllables.
        foreach ($words as $word) {
          $trimmed_word = trim($word, '!.,:;"\'()-');

          $capnum = 0;
          $captest = preg_replace("/[A-Z]/", "", $word, -1, $capnum);
          $syl_count = $syl_count + count_syllables($word);

          // Skip phone numbers, url, emails.
          if (preg_match('#^([0-9]+|.*@.*\..*|https?://.*)$#', $word)) {
            continue;
          }

          if (count_syllables($word) > 3 and $capnum == 0 && !in_array($trimmed_word, $ignored_polys)) {

            // $word = preg_replace("/[^A-Za-z]/","",$word);
            $word = str_replace('\\', '\\\\', $word);
            $word = str_replace('(', '\\(', $word);
            $word = str_replace(')', '\\)', $word);
            $word = str_replace('/', '\\/', $word);
            $word = trim($word);

            $synonyms = get_synonyms($trimmed_word);
            if (!empty($synonyms) && empty($input['json'])) {

              foreach ($synonyms as $key => $synonym) {
                $synonyms[$key] = '<span class="wc-replace-word wc-clickable">' . $synonym . '</span>';
              }

              if (count($synonyms) <= 3) {
                $synonym_replace = '<span class="wc-poly-replace wc-synonym">
                <span class="wc-poly-try-button wc-clickable">Try simpler word</span> 
                <span class="wc-list"><span class="wc-close wc-clickable"><span>X</span></span> Synonyms: ' . implode(', ', $synonyms) . '</span>
              </span>';
              }
              else {
                $truncated_list = array_slice($synonyms, 0, 3);

                $synonym_replace = '<span class="wc-poly-replace wc-synonym">
                <span class="wc-poly-try-button wc-clickable">Try simpler word</span> 
                <span class="wc-list wc-truncated"><span class="wc-close wc-clickable"><span>X</span></span> Synonyms: ' . implode(', ', $truncated_list) . ' ...<span class="wc-show-more wc-clickable"><span>&gt; MORE</span></span></span>
                <span class="wc-list-full"><span class="wc-close wc-clickable"><span>X</span></span> Synonyms: ' . implode(', ', $synonyms) . '</span>
              </span>';

              }
            }
            else {
              if (!empty($synonyms)) {
                // Request is json format.
                $suggestion_meta['synonyms'][$trimmed_word] = $synonyms;
              }
              else {
                $synonym_replace = '';
              }
            }

            if (empty($input['json'])) {
              $markup = '<span class="wc-poly-container">
            <span class="wc-hinted" data-word="' . $trimmed_word . '">' . $word . '</span> 
            <span class="wc-hint wc-poly wc-clickable" data-word="' . $trimmed_word . '">Keep this word</span>
            ' . $synonym_replace .
                '</span>';
            }
            else {
              // Json formatted output will have minimal markup - only to
              // allow identification of replaced words.
              $markup = '<span class="wc-hinted">' . $word . '</span>';
            }

            // Backslash needs to be quoted for regex.
            $tooltips = preg_replace("/ " . $word . " /", ' ' . $markup . ' ', $tooltips);
            $has_poly = TRUE;
            $overall_has_poly = TRUE;
          }

        }

        // Check all caps.
        if (strlen($sentence) > 25 && strlen(preg_replace('#[A-Z ]#', '', $sentence)) < 5) {
          $sentence_all_caps = TRUE;
          $overall_has_all_caps = TRUE;
        }
        else {
          $sentence_all_caps = FALSE;
        }

        // Check for too many exclamation marks.
        if (strpos($sentence, '!!!') !== FALSE) {
          $sentence_many_exclamation = TRUE;
          $overall_has_many_exclamation = TRUE;
        }
        else {
          $sentence_many_exclamation = FALSE;
        }

        // Check for bad words.
        foreach ($badwords as $word_key => $badword) {
          $badword = trim($badword);

          if (preg_match("/ " . $badword . " /", $tooltips)) {
            if (empty($input['json'])) {
              $tooltips = preg_replace("/ " . $badword . " /", " <span class='wc-hinted'>" . $badword . "</span> <span class='wc-hint wc-synonym'>Synonym: " . trim($goodwords[$word_key]) . "?</span> ", $tooltips);
            }
            else {
              $tooltips = preg_replace("/ " . $badword . " /", " <span class='wc-hinted'>" . $badword . "</span>", $tooltips);
              $suggestion_meta['replacements'][$badword] = explode(',', $goodwords[$word_key]);
            }
            $has_bad_word = TRUE;
            $overall_has_bad_word = TRUE;
          }
        }

        // Check for "click here" link.
        if (preg_match("/click here/i", $tooltips)) {
          $tooltips = preg_replace("/(click here)/i", " <span class='wc-hinted'>$1</span>", $tooltips);
          $has_click_here = TRUE;
          $overall_has_click_here = TRUE;
        }

        // Check for gender specific words.
        foreach ($gender_words as $gender_word) {
          if (preg_match("@" . $gender_word . "@i", $tooltips)) {
            if (empty($input['json'])) {
              $tooltips = preg_replace("@(" . $gender_word . ")@i", " <span class='wc-hinted wc-highlight'>$1</span>", $tooltips);
            }
            else {
              $tooltips = preg_replace("@(" . $gender_word . ")@i", " <span class='wc-hinted'>$1</span>", $tooltips);
            }
            $has_gender = TRUE;
            $overall_has_gender = TRUE;
          }
        }

        // Add suggestion summary if there is anything to suggest.
        if ($has_long_sentence || $has_poly || $has_bad_word || $has_click_here || $has_gender || $sentence_all_caps || $sentence_many_exclamation) {
          $summary = array();

          if ($has_long_sentence) {
            $summary[] = "Shorten the sentence.";
          }

          if ($has_poly) {
            $summary[] = "Replace polysyllabic words where possible.";
          }

          if ($has_bad_word) {
            $summary[] = "Replace complex words with simpler alternatives.";
          }

          if ($has_click_here) {
            $summary[] = "Don't use 'click here' to indicate links.";
          }

          if ($has_gender) {
            $summary[] = "Try using gender-neutral language. Examples: Change \"The judge will make his/her decision...\" to \"The judge will make their decision...\"; Change \"husband/wife\" to \"spouse\".";
          }

          if ($sentence_many_exclamation) {
            $summary[] = "Use exclamation marks sparingly.";
          }

          if ($sentence_all_caps) {
            $summary[] = "Avoid all-caps sentences.";
          }

          $suggestions[] = array(
              'paragraph_id' => $paragraph['id'],
              'content' => $tooltips,
              'summary' => implode(' ', $summary),
            ) + $suggestion_meta;
        }

      } // foreach ($test_array as $key => $sentence)
    } // if (count($test_array) > 0)
  } // foreach ($paragraphs as $paragraph)


  // Calculate indexes.
  if ($total_sentences == 0 || $total_word_count == 0) {
    $FKGL = 1;
  }
  else {
    $FKGL = round(0.39 * ($total_word_count / $total_sentences) + 11.8 * ($total_syl / $total_word_count) - 15.59, 2);
  }

  // Index from the previous run is stored in a cookie, and posted by the bookmarklet.
  $last_index = !empty($input['last']) ? $input['last'] : NULL;
  $selection_only = !empty($input['selection']) ? $input['selection'] : FALSE;

  // Construct overall summary text for the landing page.
  $overall_summary = array();

  if ($overall_has_long_sentence) {
    $overall_summary[] = 'Shorten long sentences.';
  }

  if ($overall_has_long_p) {
    $overall_summary[] = 'Shorten long paragraphs.';
  }

  if ($overall_has_poly) {
    $overall_summary[] = 'Replace polysyllabic words where possible.';
  }

  if ($overall_has_bad_word) {
    $overall_summary[] = 'Replace complex words with simpler alternatives.';
  }

  if ($overall_has_click_here) {
    $overall_summary[] = "Don't use 'click here' to indicate links.";
  }

  if ($overall_has_gender) {
    $overall_summary[] = "Try using gender-neutral language.";
  }

  if ($overall_has_underlined) {
    $overall_summary[] = "Avoid underlines.";
  }

  if ($overall_has_all_caps) {
    $overall_summary[] = "Avoid all-caps sentences.";
  }

  if (!empty($overall_has_many_exclamation)) {
    $overall_summary[] = "Use exclamation marks sparingly.";
  }

  if ($overall_has_img_without_alt) {
    $overall_summary[] = "Add ALT attributes to all images.";
  }

  if ($overall_summary) {
    $overall_summary = implode(' ', $overall_summary);
  }
  else {
    $overall_summary = 'No suggestions.';
  }

  log_results($FKGL, $last_index);

  // Load random tip.
  $tips = file("tips.txt");
  $random = mt_rand(0, count($tips) - 1);
  $random_tip = $tips[$random];

  $results['FKGL'] = $FKGL;
  $results['suggestions'] = $suggestions;
  $results['overall_summary'] = $overall_summary;
  $results['selection_only'] = $selection_only;
  $results['last_index'] = $last_index;
  $results['random_tip'] = $random_tip;
}

if (!empty($input['json'])) {
  // Return json
  $return = array(
    'index' => !empty($results['FKGL']) ? $results['FKGL'] : 0,
    'suggestions' => $results['suggestions'],
  );

  header('Content-type: application/json');
  print json_encode($return);
}
else {
  // Print widget.
  extract($results);
  include('widget.tpl.php');
}

// Logging

function log_results($FKGL, $last_index) {
  oawc_ensure_db();

  $query = "INSERT INTO oawc_log
            (`timestamp`, `ip`, `referer`, `index`, `last_index`, `session_id`)
            VALUES (
              " . time() . ",
              '" . mysql_real_escape_string($_SERVER['REMOTE_ADDR']) . "',
              " . (isset($_SERVER['HTTP_REFERER']) ? "'" . mysql_real_escape_string($_SERVER['HTTP_REFERER']) . "'" : 'NULL') . ",
              '" . mysql_real_escape_string($FKGL) . "',
              " . (!empty($last_index) ? "'" . mysql_real_escape_string($last_index) . "'" : 'NULL') . ",
              " . (session_id() ? "'" . mysql_real_escape_string(session_id()) . "'" : 'NULL') . "
            );";

  $result = mysql_query($query);

  if (!$result) {
    log_error("Database error: " . mysql_error());
  }
}
