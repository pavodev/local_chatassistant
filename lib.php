<?php
// local_chatassistant/lib.php
defined('MOODLE_INTERNAL') || die();

/**
 * Extend the global navigation.
 *
 * This callback is called on pages with a navigation tree. We use it to load our JS and CSS.
 */
function local_chatassistant_extend_navigation(global_navigation $navigation)
{
  global $PAGE;
  // Load our AMD module.
  $PAGE->requires->js_call_amd('local_chatassistant/chatbutton', 'init');
  // Load our custom CSS.
  $PAGE->requires->css(new moodle_url('/local/chatassistant/styles.css'));
}

/**
 * (Optional) Inject additional HTML before the closing body tag.
 * In this example our JS creates the required elements, so we leave it empty.
 */
function local_chatassistant_before_standard_end_of_body()
{
  return '';
}
