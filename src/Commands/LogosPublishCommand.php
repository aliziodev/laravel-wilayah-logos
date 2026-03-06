<?php

namespace Aliziodev\WilayahLogos\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class LogosPublishCommand extends Command
{
    protected $signature = 'logos:publish
                              {--force : Overwrite files yang sudah ada}';

    protected $description = 'Publish asset logo/lambang daerah ke direktori public.';

    public function handle(): int
    {
        $this->info('📸 Publishing logo/lambang daerah...');

        $args = [
            '--tag' => 'wilayah-logos',
            '--provider' => 'Aliziodev\\WilayahLogos\\LogosServiceProvider',
        ];

        if ($this->option('force')) {
            $args['--force'] = true;
        }

        Artisan::call('vendor:publish', $args);

        $this->info(Artisan::output());
        $this->info('✅ Logo berhasil di-publish ke public/vendor/wilayah/logos/');
        $this->comment('Akses via: asset("vendor/wilayah/logos/prov/img/11.png")');
        $this->comment('Atau via model: $province->logoUrl()');

        return self::SUCCESS;
    }
}
