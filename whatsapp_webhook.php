<?php

declare(strict_types=1);

$configFile = __DIR__ . '/whatsapp_config.php';
$fileConfig = file_exists($configFile) ? require $configFile : [];

$config = [
    'verify_token' => getenv('WHATSAPP_VERIFY_TOKEN') ?: ($fileConfig['verify_token'] ?? ''),
    'access_token' => getenv('WHATSAPP_ACCESS_TOKEN') ?: ($fileConfig['access_token'] ?? ''),
    'phone_number_id' => getenv('WHATSAPP_PHONE_NUMBER_ID') ?: ($fileConfig['phone_number_id'] ?? ''),
];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    verifyWebhook($config['verify_token']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    handleIncomingMessage($config);
}

http_response_code(405);
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['error' => 'Method not allowed']);
exit;

function verifyWebhook(string $verifyToken): void
{
    $mode = $_GET['hub_mode'] ?? $_GET['hub.mode'] ?? '';
    $token = $_GET['hub_verify_token'] ?? $_GET['hub.verify_token'] ?? '';
    $challenge = $_GET['hub_challenge'] ?? $_GET['hub.challenge'] ?? '';

    if ($mode === 'subscribe' && hash_equals($verifyToken, $token)) {
        http_response_code(200);
        echo $challenge;
        exit;
    }

    http_response_code(403);
    echo 'Invalid verify token';
    exit;
}

function handleIncomingMessage(array $config): void
{
    $payload = json_decode(file_get_contents('php://input'), true);

    if (!is_array($payload)) {
        respondJson(400, ['error' => 'Invalid JSON payload']);
    }

    $message = extractMessage($payload);
    if ($message === null) {
        respondJson(200, ['status' => 'ignored']);
    }

    $from = $message['from'] ?? '';
    $text = trim(mb_strtolower($message['text']['body'] ?? '', 'UTF-8'));

    if ($from === '' || $text === '') {
        respondJson(200, ['status' => 'ignored']);
    }

    if ($config['access_token'] === '' || $config['phone_number_id'] === '') {
        respondJson(500, ['error' => 'Missing WhatsApp credentials']);
    }

    $replyText = buildReply($text);
    $result = sendWhatsAppText($config['phone_number_id'], $config['access_token'], $from, $replyText);

    respondJson(200, ['status' => 'sent', 'result' => $result]);
}

function extractMessage(array $payload): ?array
{
    $entries = $payload['entry'] ?? [];
    foreach ($entries as $entry) {
        $changes = $entry['changes'] ?? [];
        foreach ($changes as $change) {
            $value = $change['value'] ?? [];
            $messages = $value['messages'] ?? [];
            if (!empty($messages[0]) && ($messages[0]['type'] ?? '') === 'text') {
                return $messages[0];
            }
        }
    }

    return null;
}

function buildReply(string $text): string
{
    $menu = "Hola, soy el bot de Mokumba.\n\n" .
        "Puedes escribir:\n" .
        "- contratacion\n" .
        "- disponibilidad\n" .
        "- eventos\n" .
        "- premios\n" .
        "- integrantes\n" .
        "- redes\n" .
        "- contacto\n" .
        "- ayuda";

    $rules = [
        'contratacion' => "Para contrataciones de Mokumba, por favor envianos estos datos:\n- ciudad\n- fecha del evento\n- tipo de presentacion\n- horario aproximado",
        'contrataciones' => "Para contrataciones de Mokumba, por favor envianos estos datos:\n- ciudad\n- fecha del evento\n- tipo de presentacion\n- horario aproximado",
        'disponibilidad' => "Para revisar disponibilidad, envianos la fecha, ciudad y tipo de evento. Nuestro equipo te responde lo antes posible.",
        'evento' => "Mokumba participa en eventos culturales, festivales, conciertos y presentaciones especiales. Si deseas una presentacion, escribe contratacion.",
        'eventos' => "Mokumba participa en eventos culturales, festivales, conciertos y presentaciones especiales. Si deseas una presentacion, escribe contratacion.",
        'premio' => "Mokumba es ganador del Festival Petronio Alvarez 2025, uno de los reconocimientos mas importantes de la musica tradicional del Pacifico.",
        'premios' => "Mokumba es ganador del Festival Petronio Alvarez 2025, uno de los reconocimientos mas importantes de la musica tradicional del Pacifico.",
        'integrante' => "Mokumba es una agrupacion juvenil de violines caucanos con direccion musical de Jhon David Balanta y management de Deyanira Bocanegra.",
        'integrantes' => "Mokumba es una agrupacion juvenil de violines caucanos con direccion musical de Jhon David Balanta y management de Deyanira Bocanegra.",
        'redes' => "Siguenos en Instagram como @mokumba_ y en Facebook como Mokumba.",
        'instagram' => "Nuestro Instagram es @mokumba_.",
        'facebook' => "Puedes encontrarnos en Facebook como Mokumba.",
        'contacto' => "Puedes escribirnos tambien al correo contactomokumba@gmail.com o dejar aqui tu mensaje para que nuestro equipo te responda.",
        'ayuda' => $menu,
        'hola' => "Hola. Bienvenido a Mokumba.\n\n" . $menu,
        'buenas' => "Hola. Bienvenido a Mokumba.\n\n" . $menu,
        'menu' => $menu,
        'menú' => $menu,
    ];

    foreach ($rules as $keyword => $reply) {
        if (str_contains($text, $keyword)) {
            return $reply;
        }
    }

    return "Gracias por escribir a Mokumba.\n\n" . $menu;
}

function sendWhatsAppText(string $phoneNumberId, string $accessToken, string $recipient, string $message): array
{
    $url = "https://graph.facebook.com/v23.0/{$phoneNumberId}/messages";
    $payload = [
        'messaging_product' => 'whatsapp',
        'to' => $recipient,
        'type' => 'text',
        'text' => [
            'preview_url' => false,
            'body' => $message,
        ],
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
    ]);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    $statusCode = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    return [
        'http_status' => $statusCode,
        'response' => $response,
        'error' => $error,
    ];
}

function respondJson(int $statusCode, array $data): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}
