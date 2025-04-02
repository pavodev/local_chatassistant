<?php
// local_chatassistant/settings.php

// This is the settings file for the Chat Assistant local plugin.
// It defines the configuration options available in the Moodle admin interface.

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
  $settings = new admin_settingpage('local_chatassistant', get_string('pluginname', 'local_chatassistant'));
  $ADMIN->add('localplugins', $settings);

  // Add a setting to store the API key.
  $settings->add(new admin_setting_configtext(
    'local_chatassistant/api_key',
    get_string('api_key', 'local_chatassistant'),
    get_string('api_key_desc', 'local_chatassistant'),
    '', // default value
    PARAM_TEXT
  ));
}
