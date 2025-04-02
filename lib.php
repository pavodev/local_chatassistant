<?php
// local_openai_assistant/lib.php
defined('MOODLE_INTERNAL') || die();

/**
 * Extend the global navigation.
 *
 * This callback is called on pages with a navigation tree. We use it to load our JS and CSS.
 */
function local_openai_assistant_extend_navigation(global_navigation $navigation)
{
  global $PAGE;
  // Load our AMD module.
  $PAGE->requires->js_call_amd('local_openai_assistant/chatbutton', 'init');
  // Load our custom CSS.
  $PAGE->requires->css(new moodle_url('/local/openai_assistant/styles.css'));
}

/**
 * (Optional) Inject additional HTML before the closing body tag.
 * In this example our JS creates the required elements, so we leave it empty.
 */
function local_openai_assistant_before_standard_end_of_body()
{
  return '';
}
