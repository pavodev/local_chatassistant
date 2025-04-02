<?php
// local_openai_assistant/chat.php
require_once(__DIR__ . '/../../config.php');
require_login();

// Get the user input.
$input = required_param('input', PARAM_RAW);

// Replace with your actual OpenAI API key.
$api_key = 'YOUR_OPENAI_API_KEY';
$endpoint = 'https://api.openai.com/v1/engines/davinci-codex/completions';

// Prepare the data for the API call.
$data = [
  'prompt' => $input,
  'max_tokens' => 150,
];

// Initialize and execute the cURL request.
$ch = curl_init($endpoint);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
  'Content-Type: application/json',
  'Authorization: Bearer ' . $api_key,
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
$response = curl_exec($ch);
curl_close($ch);

// Return the API response.
header('Content-Type: application/json');
echo $response;
