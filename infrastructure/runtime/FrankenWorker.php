<?php

declare(strict_types=1);

namespace app\infrastructure\runtime;

use GuzzleHttp\Psr7\HttpFactory;
use Psr\Http\Message\ServerRequestInterface;
use yii2\extensions\psrbridge\emitter\SapiEmitter;

use function file_get_contents;
use function frankenphp_handle_request;
use function getallheaders;
use function is_array;
use function is_string;
use function pcntl_signal;
use function pcntl_signal_dispatch;

use const SIGTERM;
use const UPLOAD_ERR_OK;

/**
 * @codeCoverageIgnore
 */
final class FrankenWorker
{
    private bool $shouldStop = false;
    private int $requestCount = 0;
    private RunnerApplication|null $app = null;
    private readonly SapiEmitter $emitter;
    private readonly HttpFactory $httpFactory;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(
        private readonly array $config, 
        private readonly int $maxRequests = 1000
    ) {
        $this->httpFactory = new HttpFactory();
        $this->emitter = new SapiEmitter();

        $this->app = new RunnerApplication($this->config);
        $this->app->init();

        $this->registerSignalHandlers();
    }

    public function run(): int
    {
        while ($this->shouldContinue()) {
            $handled = frankenphp_handle_request($this->handleRequest(...));

            if (!$handled) {
                break;
            }

            $this->requestCount++;
            pcntl_signal_dispatch();
        }

        $this->shutdown();

        return 0;
    }

    private function handleRequest(): void
    {
        $psrRequest = $this->createPsr7Request();

        if (!$this->app instanceof RunnerApplication) {
            throw new \RuntimeException('Application not initialized');
        }

        $response = $this->app->handlePsrRequest($psrRequest);

        $this->emitter->emit($response);
    }

    private function createPsr7Request(): ServerRequestInterface
    {
        $uri = is_string($_SERVER['REQUEST_URI'] ?? null) ? $_SERVER['REQUEST_URI'] : '/';
        $method = is_string($_SERVER['REQUEST_METHOD'] ?? null) ? $_SERVER['REQUEST_METHOD'] : 'GET';

        $request = $this->httpFactory->createServerRequest($method, $uri, $_SERVER);

        $request = $request->withQueryParams($_GET);
        $request = $request->withParsedBody($_POST);
        $request = $request->withCookieParams($_COOKIE);

        foreach (getallheaders() as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        $body = file_get_contents('php://input');
        if ($body !== false && $body !== '') {
            $stream = $this->httpFactory->createStream($body);
            $request = $request->withBody($stream);
        }

        return $this->addUploadedFiles($request);
    }

    private function addUploadedFiles(ServerRequestInterface $request): ServerRequestInterface
    {
        $uploadedFiles = [];

        foreach ($_FILES as $name => $file) {
            if (!is_array($file) || !isset($file['tmp_name']) || !is_string($file['tmp_name'])) {
                continue;
            }

            $stream = $this->httpFactory->createStreamFromFile($file['tmp_name']);
            $uploadedFiles[$name] = $this->httpFactory->createUploadedFile(
                $stream,
                (int) ($file['size'] ?? 0),
                (int) ($file['error'] ?? UPLOAD_ERR_OK),
                $file['name'] ?? null,
                $file['type'] ?? null
            );
        }

        if ($uploadedFiles !== []) {
            $request = $request->withUploadedFiles($uploadedFiles);
        }

        return $request;
    }

    private function shouldContinue(): bool
    {
        if ($this->shouldStop) {
            return false;
        }
        return $this->requestCount < $this->maxRequests;
    }

    private function registerSignalHandlers(): void
    {
        pcntl_signal(SIGTERM, function (): void {
            $this->shouldStop = true;
        });
    }

    private function shutdown(): void
    {
    }
}
