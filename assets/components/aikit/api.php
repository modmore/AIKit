<?php

//ini_set('display_errors', 1);
//error_reporting(E_ALL);

use modmore\AIKit\API\ApiInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;

require_once dirname(__DIR__, 3) . '/config.core.php';
require_once MODX_CORE_PATH . 'vendor/autoload.php';
$modx = new \MODX\Revolution\modX();
$modx->initialize('mgr');

$a = $_GET['a'] ?? $_SERVER['REQUEST_URI'];

/** @var ServerRequestFactoryInterface $factory */
$factory = $modx->services->get(ServerRequestFactoryInterface::class);
$request = $factory->createServerRequest(
    $_SERVER['REQUEST_METHOD'],
    $a, // We fake the request uri from $_GET['a'] if set
    $_SERVER
);

// Populate the request with any parsed body and query parameters
if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'PUT') {
    $parsedBody = json_decode(file_get_contents('php://input'), true) ?: [];
    $request = $request->withParsedBody($parsedBody);
}

$request = $request->withQueryParams($_GET)
    ->withCookieParams($_COOKIE)
    ->withUploadedFiles($_FILES);

// Support calls like conversation/await => Conversation\AwaitAPI
$a = implode('\\', array_filter(array_map('ucfirst', explode('/', $a))));
$className = '\modmore\AIKit\API\\' . ucfirst($a) . 'API';
if (class_exists($className) && is_subclass_of($className, ApiInterface::class, true)) {
    $api = new $className($modx);
    $response = $api->handleRequest($request);

    // Set the HTTP status code
    http_response_code($response->getStatusCode());

    // Set the headers
    foreach ($response->getHeaders() as $name => $values) {
        foreach ($values as $value) {
            header("$name: $value", false);
        }
    }

    @session_write_close();

    // Output the body
    echo $response->getBody();
    exit();
}


http_response_code(400);
header('Content-Type: application/json');
@session_write_close();
echo json_encode(['error' => 'Invalid action']);
exit();
