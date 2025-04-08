<?php

require('../../config.php');
require_login();

header('Content-Type: application/json');

$input = required_param('input', PARAM_RAW_TRIMMED);

// Retrieve the API key from plugin settings.
$OPENAI_API_KEY = get_config('local_chatassistant', 'api_key');
if (empty($OPENAI_API_KEY)) {
  header('Content-Type: application/json');
  echo json_encode(['error' => 'API key not configured.']);
  exit;
}

// You might want to use session or database to persist the thread_id per user.
$ASSISTANT_ID = 'asst_vCoi5NohNiKC9zl3fR3XYg9H';

function call_openai($url, $method = 'GET', $data = null)
{
  global $OPENAI_API_KEY;

  $headers = [
    "Authorization: Bearer $OPENAI_API_KEY",
    "OpenAI-Beta: assistants=v2",
    "Content-Type: application/json"
  ];

  $ch = curl_init($url);
  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  if ($data !== null) {
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
  }
  $response = curl_exec($ch);
  curl_close($ch);
  return json_decode($response, true);
}

// 1. Create a thread (can also reuse per session)
$thread = call_openai("https://api.openai.com/v1/threads", "POST");
$thread_id = $thread['id'] ?? null;

// 2. Add user message to thread
call_openai("https://api.openai.com/v1/threads/$thread_id/messages", "POST", [
  'role' => 'user',
  'content' => $input
]);

// 3. Run the assistant
$run = call_openai("https://api.openai.com/v1/threads/$thread_id/runs", "POST", [
  'assistant_id' => $ASSISTANT_ID
]);
$run_id = $run['id'] ?? null;

// 4. Poll until run is complete
do {
  sleep(1); // avoid hammering API
  $status_check = call_openai("https://api.openai.com/v1/threads/$thread_id/runs/$run_id");
  $status = $status_check['status'] ?? 'error';
} while ($status !== 'completed' && $status !== 'failed');

// 5. Get messages
$messages = call_openai("https://api.openai.com/v1/threads/$thread_id/messages");

$assistantMessage = null;
foreach ($messages['data'] as $message) {
  if ($message['role'] === 'assistant') {
    $assistantMessage = $message;
    break;
  }
}

// echo $assistantMessage['content'][0]['text']['message'];

$content = $assistantMessage;

echo json_encode([
  'message' => $content
]);
