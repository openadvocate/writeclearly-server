<!DOCTYPE html>
<html>
<head>
  <meta http-equiv="content-type" content="text/html; charset=utf-8" />
  <title>Write Clearly</title>
  <script type="text/javascript" language="javascript" src="<?php print !empty($_SERVER['HTTPS']) ? 'https' : 'http' ?>://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.js"></script>
  <script type="text/javascript" language="javascript" src="<?php print !empty($_SERVER['HTTPS']) ? 'https' : 'http' ?>://<?php print $_SERVER['HTTP_HOST'] ?>/oawc/widget.js"></script>
  <script type="text/javascript" language="javascript">
    serviceUrl = '<?php print $_SERVER['HTTP_HOST'] ?>';
  </script>
  <link rel="stylesheet" type="text/css" href="<?php print !empty($_SERVER['HTTPS']) ? 'https' : 'http' ?>://<?php print $_SERVER['HTTP_HOST'] ?>/oawc/style.css"></link>
</head>
<body id="oawc-widget">

<div class="oawc-header">
  <div class="oawc-logo">
    <a class="oawc-logo-link" target="_blank" href="http://www.openadvocate.org/writeclearly">OpenAdvocate</a>
  </div>
  <div class="oawc-pager">
    <div class="oawc-pager-jump"><input type="text" name="oawc-jump-number" class="oawc-suggestion-current"> of <span class="oawc-suggestion-total">1</span> <a class="oawc-nav oawc-jump">Go</a></div>

    <div class="oawc-pager-buttons">
      <a href="#" class="oawc-nav oawc-first inactive">First</a>
      <a href="#" class="oawc-nav oawc-prev inactive"><b><</b> Previous</a>
      <a href="#" class="oawc-nav oawc-next">Next <b>></b></a>
      <a href="#" class="oawc-nav oawc-last">Last</a>
    </div>
  </div>
  <div class="clearfix"></div>
</div>

<div class="oawc-content">

  <div class="oawc-card landing">

    <div class="oawc-left">
        <div class="oawc-index"><span id="wc-fk-index"><?php if (!empty($FKGL)) print $FKGL; ?></span>
          <?php if (isset($last_index)): ?>
            <span class="oawc-arrow <?php print ($FKGL > $last_index) ? 'oawc-up' : ($FKGL < $last_index ? 'oawc-down' : 'oawc-same'); ?>"></span>
          <?php endif; ?>
        </div>

        <?php if (isset($last_index)): ?>
          <div class="oawc-last-index">Last <?php print $last_index; ?></div>
        <?php endif; ?>

        <div class="oawc-index-name">
          Flesch-Kincaid Grade Level
        </div>
      
        <div class="oawc-feedback-link">
          <a href="http://goo.gl/forms/03F2g4YyKv" target="_blank">Give us Feedback</a>
        </div>
      
        <?php if ($selection_only): ?>
        <div class="oawc-selection-note">
          For selected text only.
        </div>
      <?php endif; ?>

    </div>

    <div class="oawc-right">

      <div class="oawc-suggestion">
        <div class="oawc-overall-summary">Suggestion Summary: <?php print $overall_summary; ?></div>
        <?php if (!empty($suggestions)): ?>
          <a href="#" class="oawc-sentence-see">See suggestions</a>
        <?php endif; ?>

        <div class="oawc-tip-container"><?php print htmlspecialchars($random_tip); ?></div>
      </div>


      <?php foreach ($suggestions as $key => $suggestion): ?>

          <div class="oawc-suggestion" style="display:none">
            <div class="oawc-holder-1">
              <div class="oawc-sentence-id-container">
                <span class="oawc-sentence-id" <?php if ($suggestion['paragraph_id']) print ' id="' . $suggestion['paragraph_id'] . '"'; ?>>#<?php print ($key + 1); ?></span>
              </div>
            </div>
            <div class="oawc-holder-2">
              <div class="oawc-sentence-hints"><?php print $suggestion['content']; ?></div>
              <div class="oawc-sentence-summary"><?php print $suggestion['summary']; ?></div>
            </div>

          </div>


      <?php endforeach; ?>

    </div>

  </div>

</div>

</body>
</html>
