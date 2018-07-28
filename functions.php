<?php

/**
 * Saves a polysyllable that the visitor chosen to ignore.
 */
function save_ignored_poly($word) {
  oawc_ensure_db();

  if (empty($word)) {
    return;
  }

  $query = "REPLACE INTO oawc_poly_ignore
                (`word`, `session_id`)
                VALUES (
                  '" . mysql_real_escape_string($word) . "',
                  '" . mysql_real_escape_string(get_session_id()) . "'
                );";

  mysql_query($query);
}

/**
 * Saves a word replacement that the visitor has selected.
 */
function save_replaced_poly($original, $word) {
  oawc_ensure_db();

  if (empty($word) || empty($original)) {
    return;
  }

  // Check if this word is already saved.
  $query = "SELECT word, replacement FROM oawc_replacements
                WHERE word = '" . mysql_real_escape_string($original) . "' AND replacement = '" . mysql_real_escape_string($word) . "' ";
  $result = mysql_query($query);
  $row = mysql_fetch_assoc($result);

  if ($row) {
    $query = "UPDATE oawc_replacements SET `count` = `count`+1
                WHERE word = '" . mysql_real_escape_string($original) . "' AND replacement = '" . mysql_real_escape_string($word) . "' ";
  }
  else {
    $query = "INSERT INTO oawc_replacements
                (`word`, `replacement`, `count`)
                VALUES (
                  '" . mysql_real_escape_string($original) . "',
                  '" . mysql_real_escape_string($word) . "',
                  1
                )
                ";
  }

  mysql_query($query);
}

/**
 * Returns a list of polysyllables that the current visitor has already ignored.
 */
function get_ignored_polys() {
  oawc_ensure_db();

  $query = "SELECT word FROM oawc_poly_ignore
                WHERE session_id = '*' OR session_id = '" . mysql_real_escape_string(get_session_id()) . "'";

  $result = mysql_query($query);

  $words = array();

  while ($row = mysql_fetch_assoc($result)) {
    $words[] = $row['word'];
  }

  return $words;
}

/**
 * Returns the session id.
 */
function get_session_id() {
  return (session_id() ? session_id() : $_SERVER['REMOTE_ADDR']);
}

/**
 * Logs an error messages to the log file.
 */
function log_error($text) {
  global $log_file;
  file_put_contents($log_file, '[' . date('Y/m/d@G:i:s') . ']' . $text . "\n", FILE_APPEND);
}

/**
 * Counts the syllables in a word.
 */
function count_syllables($word) {
  $word = strtolower($word);

  // Regex Patterns Needed
  $triples = "dn't|eau|iou|ouy|you|bl$";
  $doubles = "ai|ae|ay|au|ea|ee|ei|eu|ey|ie|ii|io|oa|oe|oi|oo|ou|oy|ue|uy|ya|ye|yi|yo|yu";
  $singles = "a|e|i|o|u|y";
  $vowels = "/(" . $triples . "|" . $doubles . "|" . $singles . ")/";
  $trailing_e = "/e$/";
  $trailing_s = "/s$/";

  // Cleaning up word endings
  $word = preg_replace($trailing_s, "", $word);
  $word = preg_replace($trailing_e, "", $word);

  // Count # of "vowels"
  preg_match_all($vowels, $word, $matches);
  $syl_count = count($matches[0]);
  return $syl_count;
};

/**
 * Replaces encoded characters.
 */
function remove_smart_quotes($file) {
  // First, replace UTF-8 characters.
  $file = str_replace(array("\xe2\x80\x98", "\xe2\x80\x99", "\xe2\x80\x9c", "\xe2\x80\x9d", "\xe2\x80\x93", "\xe2\x80\x94", "\xe2\x80\xa6"), array("'", "'", '"', '"', '-', '--', '...'), $file);

  // Next, replace their Windows-1252 equivalents.
  $file = str_replace(array(chr(145), chr(146), chr(147), chr(148), chr(150), chr(151), chr(133)), array("'", "'", '"', '"', '-', '--', '...'), $file);
  return $file;
};


/**
 * Establishes database connection.
 * Configuration is loaded from conf.php.
 */
function oawc_ensure_db() {
  global $db_hostname, $db_username, $db_password, $db_name, $db_resource;

  if (!empty($db_resource)) {
    // Connection already established.
    return TRUE;
  }

  $db_resource = mysql_connect($db_hostname, $db_username, $db_password);

  if ($db_resource) {
    if (!mysql_select_db($db_name, $db_resource)) {
      print("Unable to select database: " . mysql_error());
      return FALSE;
    }

  }
  else {
    print("Unable to open database: $db_hostname " . mysql_error());
    return FALSE;
  }

  return TRUE;
}

/**
 * Returns simpler alternative suggestions.
 */
function get_word_suggestions() {
  $handle = fopen('writeclearly-thesaurus.csv', 'r');
  $bad_words = array();
  $good_words = array();

  if ($handle !== FALSE) {
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
      if (count($data) > 1) {
        $bad_words[] = trim($data[0]);
        $good_words[] = trim($data[1]);
      }
    }
    fclose($handle);
  }

  return array($bad_words, $good_words);
}
