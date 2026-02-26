<?php

declare(strict_types=1);

namespace OpenAI\Laravel;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use OpenAI;
use OpenAI\Client;
use OpenAI\Contracts\ClientContract;
use OpenAI\Laravel\Commands\InstallCommand;
use OpenAI\Laravel\Exceptions\ApiKeyIsMissing;

/**
 * @internal
 */
final class ServiceProvider extends BaseServiceProvider implements DeferrableProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ClientContract::class, static function (): Client {
            $apiKey = config('openai.api_key');
            $organization = config('openai.organization');

            if (! is_string($apiKey) || ($organization !== null && ! is_string($organization))) {
                throw ApiKeyIsMissing::create();
            }

            $httpConfig = ['timeout' => config('openai.request_timeout', 30)];
            $baseUri = config('openai.base_uri');
            $proxyToken = config('openai.x_proxy_token');

            if (is_string($baseUri) && $baseUri !== '') {
                $httpConfig['base_uri'] = rtrim($baseUri, '/').'/';
            }

            if (config('openai.request_log', false)) {
                $stack = HandlerStack::create();

                $stack->push(Middleware::tap(
                    static function ($request): void {
                        $body = (string) $request->getBody();
                        if ($request->getBody()->isSeekable()) {
                            $request->getBody()->rewind();
                        }

                        Log::channel(config('openai.request_log_channel', 'daily'))->info('OpenAI Request', [
                            'method' => $request->getMethod(),
                            'uri' => (string) $request->getUri(),
                            'headers' => $request->getHeaders(),
                            'body' => $body,
                        ]);
                    },
                    static function ($request, $options, $promise): void {
                        $promise->then(
                            static function ($response): void {
                                $payload = (string) $response->getBody();
                                if ($response->getBody()->isSeekable()) {
                                    $response->getBody()->rewind();
                                }

                                Log::channel(config('openai.request_log_channel', 'daily'))->info('OpenAI Response', [
                                    'status' => $response->getStatusCode(),
                                    'headers' => $response->getHeaders(),
                                    'body' => $payload,
                                ]);
                            },
                            static function ($reason): void {
                                Log::channel(config('openai.request_log_channel', 'daily'))->error('OpenAI Response Error', [
                                    'error' => $reason instanceof \Throwable ? $reason->getMessage() : (string) $reason,
                                ]);
                            }
                        );
                    }
                ));

                $httpConfig['handler'] = $stack;
            }

            $factory = OpenAI::factory()
                ->withApiKey($apiKey)
                ->withOrganization($organization)
                ->withHttpHeader('OpenAI-Beta', 'assistants=v2');

            if (is_string($baseUri) && $baseUri !== '') {
                $factory->withBaseUri(rtrim($baseUri, '/'));
            }

            if (is_string($proxyToken) && $proxyToken !== '') {
                $factory->withHttpHeader('x-proxy-token', $proxyToken);
            }

            return $factory
                ->withHttpClient(new \GuzzleHttp\Client($httpConfig))
                ->make();
        });

        $this->app->alias(ClientContract::class, 'openai');
        $this->app->alias(ClientContract::class, Client::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/openai.php' => config_path('openai.php'),
            ]);

            $this->commands([
                InstallCommand::class,
            ]);
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [
            Client::class,
            ClientContract::class,
            'openai',
        ];
    }
}
