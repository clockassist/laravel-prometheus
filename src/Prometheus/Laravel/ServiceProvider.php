<?php

namespace Prometheus\Laravel;

use Illuminate\Redis\Connections\PhpRedisConnection;
use Illuminate\Support\Facades\Redis as RedisFacade;
use Prometheus\CollectorRegistry;
use Prometheus\Laravel\Adapters\RedisClusterAdapter;
use Prometheus\Laravel\Adapters\RedisWithoutSummariesAdapter;
use Prometheus\Storage\Adapter;
use Prometheus\Storage\InMemory;
use RuntimeException;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    private const CONFIG_KEY = 'prometheus';

    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../../../config/prometheus.php', self::CONFIG_KEY);

    }

    public function boot()
    {
        $this->publishes([
            __DIR__.'/../../../config/prometheus.php' => config_path('prometheus.php'),
        ]);

        $this->app->bind('prometheus', function () {
            return new CollectorRegistry(
                storageAdapter: $this->buildAdapter(),
                registerDefaultMetrics: false
            );
        });
    }

    private function buildAdapter(): Adapter
    {
        $config = $this->app['config'][self::CONFIG_KEY] ?? [];

        $adapter = $config['adapter'] ?? null;

        if ($adapter === null) {
            return new InMemory();
        }

        if ($adapter === 'memory') {
            return new InMemory();
        }

        if ($adapter === 'redis') {
            return $this->buildRedisAdapter($config['redis_connection'] ?? 'default');
        }

        throw new RuntimeException('Unknown adapter: '.$adapter);
    }

    private function buildRedisAdapter(string $connectionName): RedisWithoutSummariesAdapter|RedisClusterAdapter
    {
        $connection = RedisFacade::connection($connectionName);

        if ($connection instanceof PhpRedisConnection) {
            $client = $connection->client();

            if ($client instanceof \Redis) {
                return RedisWithoutSummariesAdapter::fromExistingConnection($client);
            }

            if ($client instanceof \RedisCluster) {
                return RedisClusterAdapter::fromExistingConnection($client);
            }

            throw new RuntimeException('Unexpected client instance type: '.$client::class);
        }

        $database = $this->app['config']['database']['redis'][$connectionName] ?? [];

        return new RedisWithoutSummariesAdapter([
            'host' => $database['host'] ?? null,
            'port' => $database['port'] ?? null,
            'password' => $database['password'] ?? null,
            'database' => ($db = $database['database'] ?? null) ? (int) $db : null,
        ]);
    }
}
