<?php

/**
 * Minimal stand-in for POST /v1/messages (streaming), run with `php -S` by ClaudeGeneratorTest.
 *
 *   FAKE_AI_RESPONSE  file with the JSON text the "model" returns
 *   FAKE_AI_CAPTURE   file the request (headers + body) is written to
 *   FAKE_AI_MODE      ok | refusal | fallback | max_tokens | auth
 */
$capture = getenv('FAKE_AI_CAPTURE');
$headers = function_exists('getallheaders') ? getallheaders() : [];
file_put_contents($capture, json_encode([
    'path' => $_SERVER['REQUEST_URI'] ?? '',
    'headers' => array_change_key_case($headers, CASE_LOWER),
    'body' => json_decode(file_get_contents('php://input'), true),
]));

$mode = getenv('FAKE_AI_MODE') ?: 'ok';

if ($mode === 'auth') {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['type' => 'error', 'error' => ['type' => 'authentication_error', 'message' => 'invalid x-api-key']]);

    return;
}

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');

$send = function (string $event, array $data) {
    echo "event: {$event}\ndata: ".json_encode($data)."\n\n";
};

$text = (string) file_get_contents(getenv('FAKE_AI_RESPONSE'));
$model = 'claude-opus-5-5';

$send('message_start', ['type' => 'message_start', 'message' => [
    'id' => 'msg_test', 'type' => 'message', 'role' => 'assistant', 'model' => $model, 'content' => [],
    'stop_reason' => null, 'stop_sequence' => null,
    'usage' => ['input_tokens' => 1500, 'output_tokens' => 1],
]]);

$index = 0;

// thinking block first (content omitted by default on current models)
$send('content_block_start', ['type' => 'content_block_start', 'index' => $index, 'content_block' => ['type' => 'thinking', 'thinking' => '', 'signature' => '']]);
$send('content_block_stop', ['type' => 'content_block_stop', 'index' => $index++]);

if ($mode === 'fallback') {
    // the declined model's partial text, then a fallback block, then the real answer
    $send('content_block_start', ['type' => 'content_block_start', 'index' => $index, 'content_block' => ['type' => 'text', 'text' => '']]);
    $send('content_block_delta', ['type' => 'content_block_delta', 'index' => $index, 'delta' => ['type' => 'text_delta', 'text' => '{"seo": {"tit']]);
    $send('content_block_stop', ['type' => 'content_block_stop', 'index' => $index++]);
    $send('content_block_start', ['type' => 'content_block_start', 'index' => $index, 'content_block' => [
        'type' => 'fallback', 'from' => ['model' => $model], 'to' => ['model' => 'claude-opus-5'],
    ]]);
    $send('content_block_stop', ['type' => 'content_block_stop', 'index' => $index++]);
}

if ($mode !== 'refusal') {
    $send('content_block_start', ['type' => 'content_block_start', 'index' => $index, 'content_block' => ['type' => 'text', 'text' => '']]);
    foreach (mb_str_split($text, 40) as $chunk) {
        $send('content_block_delta', ['type' => 'content_block_delta', 'index' => $index, 'delta' => ['type' => 'text_delta', 'text' => $chunk]]);
    }
    $send('content_block_stop', ['type' => 'content_block_stop', 'index' => $index++]);
}

$stop = match ($mode) {
    'refusal' => 'refusal',
    'max_tokens' => 'max_tokens',
    default => 'end_turn',
};

$send('message_delta', ['type' => 'message_delta', 'delta' => ['stop_reason' => $stop, 'stop_sequence' => null], 'usage' => ['output_tokens' => 2400]]);
$send('message_stop', ['type' => 'message_stop']);
