<?php

require('../../config.php');
require_login();

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no'); // For NGINX

echo str_pad('', 4096); // Padding for SSE buffering
@ob_flush();
flush();

$input = required_param('input', PARAM_RAW_TRIMMED);

$OPENAI_API_KEY = get_config('local_chatassistant', 'api_key');
if (empty($OPENAI_API_KEY)) {
  echo "data: " . json_encode(['error' => 'API key not configured.']) . "\n\n";
  exit;
}

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

// 1. Create a thread
$thread = call_openai("https://api.openai.com/v1/threads", "POST");
$thread_id = $thread['id'] ?? null;
if (!$thread_id) {
  echo "data: " . json_encode(['error' => 'Failed to create thread.']) . "\n\n";
  exit;
}

// 2. Add user message
call_openai("https://api.openai.com/v1/threads/$thread_id/messages", "POST", [
  'role' => 'user',
  'content' => $input
]);

// 3. Run the assistant with streaming
$headers = [
  "Authorization: Bearer $OPENAI_API_KEY",
  "OpenAI-Beta: assistants=v2",
  "Content-Type: application/json",
  "Accept: text/event-stream"
];

$payload = json_encode([
  'assistant_id' => $ASSISTANT_ID,
  'stream' => true
]);

$ch = curl_init("https://api.openai.com/v1/threads/$thread_id/runs");
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => false,
  CURLOPT_POST => true,
  CURLOPT_HTTPHEADER => $headers,
  CURLOPT_POSTFIELDS => $payload,
  CURLOPT_WRITEFUNCTION => function ($ch, $data) {
    $lines = explode("\n", $data);
    foreach ($lines as $line) {
      $line = trim($line);
      if (str_starts_with($line, 'data: ')) {
        $json = trim(substr($line, 6));
        if ($json === '[DONE]') {
          echo "data: [DONE]\n\n";
          @ob_flush();
          flush();
          return strlen($data);
        }

        $parsed = json_decode($json, true);
        $delta = $parsed['delta'] ?? null;

        if (isset($delta['content'][0]['text']['value'])) {
          $text = $delta['content'][0]['text']['value'];
          echo "data: " . json_encode([
            'content' => [
              ['text' => ['value' => $text]]
            ]
          ]) . "\n\n";
          @ob_flush();
          flush();
        }
      }
    }

    return strlen($data);
  }
]);
curl_exec($ch);

if (curl_errno($ch)) {
  echo "data: " . json_encode(['error' => 'Curl error: ' . curl_error($ch)]) . "\n\n";
}
curl_close($ch);

// require_once(__DIR__ . '/../../config.php');

// header('Content-Type: text/event-stream');
// header('Cache-Control: no-cache');
// header('X-Accel-Buffering: no'); // For NGINX

// // Get input from POST
// $input = required_param('input', PARAM_RAW);

// // OpenAI keys and IDs
// // Retrieve the API key from plugin settings.
// $OPENAI_API_KEY = get_config('local_chatassistant', 'api_key');
// if (empty($OPENAI_API_KEY)) {
//   header('Content-Type: application/json');
//   echo json_encode(['error' => 'API key not configured.']);
//   exit;
// }

// // You might want to use session or database to persist the thread_id per user.
// $ASSISTANT_ID = 'asst_vCoi5NohNiKC9zl3fR3XYg9H';


// // 1. Create a new thread
// $thread = curl_init('https://api.openai.com/v1/threads');
// curl_setopt_array($thread, [
//   CURLOPT_RETURNTRANSFER => true,
//   CURLOPT_POST => true,
//   CURLOPT_HTTPHEADER => [
//     'Authorization: Bearer ' . $OPENAI_API_KEY,
//     'OpenAI-Beta: assistants=v2',
//     'Content-Type: application/json'
//   ],
//   CURLOPT_POSTFIELDS => '{}'
// ]);
// $response = curl_exec($thread);
// curl_close($thread);
// $threadData = json_decode($response, true);
// $threadId = $threadData['id'] ?? null;

// if (!$threadId) {
//   echo "data: {\"error\": \"Failed to create thread\"}\n\n";
//   exit;
// }

// // 2. Add user message to thread
// $message = curl_init("https://api.openai.com/v1/threads/$threadId/messages");
// curl_setopt_array($message, [
//   CURLOPT_RETURNTRANSFER => true,
//   CURLOPT_POST => true,
//   CURLOPT_HTTPHEADER => [
//     'Authorization: Bearer ' . $apiKey,
//     'OpenAI-Beta: assistants=v2',
//     'Content-Type: application/json'
//   ],
//   CURLOPT_POSTFIELDS => json_encode([
//     'role' => 'user',
//     'content' => $input
//   ])
// ]);
// curl_exec($message);
// curl_close($message);

// // 3. Run the assistant with stream=true
// $run = curl_init("https://api.openai.com/v1/threads/$threadId/runs");
// curl_setopt_array($run, [
//   CURLOPT_HTTPHEADER => [
//     'Authorization: Bearer ' . $apiKey,
//     'OpenAI-Beta: assistants=v2',
//     'Content-Type: application/json'
//   ],
//   CURLOPT_POST => true,
//   CURLOPT_POSTFIELDS => json_encode([
//     'assistant_id' => $ASSISTANT_ID,
//     'stream' => true
//   ]),
//   CURLOPT_WRITEFUNCTION => function ($ch, $data) {
//     $lines = explode("\n", $data);
//     foreach ($lines as $line) {
//       if (str_starts_with($line, 'data: ')) {
//         echo $line . "\n\n";
//         ob_flush();
//         flush();
//       }
//     }
//     return strlen($data);
//   }
// ]);
// curl_exec($run);
// curl_close($run);
