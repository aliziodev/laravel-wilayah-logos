<?php

namespace Aliziodev\WilayahLogos\Tests;

use Aliziodev\Wilayah\WilayahServiceProvider;
use Aliziodev\WilayahLogos\LogosServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            WilayahServiceProvider::class,
            LogosServiceProvider::class,
        ];
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(
            realpath(__DIR__.'/../../laravel-wilayah/src/Database/Migrations')
        );
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app['config']->set('wilayah.cache.enabled', false);
    }

    protected function seedTestData(): void
    {
        $now = now()->toDateTimeString();

        \DB::table('provinces')->insert([
            'code' => '32', 'name' => 'JAWA BARAT',
            'created_at' => $now, 'updated_at' => $now,
        ]);

        \DB::table('regencies')->insert([
            'code' => '32.73', 'province_id' => 1, 'name' => 'KOTA BANDUNG', 'type' => 1,
            'created_at' => $now, 'updated_at' => $now,
        ]);
    }
}
