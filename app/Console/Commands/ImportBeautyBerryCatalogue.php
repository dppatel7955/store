<?php

namespace App\Console\Commands;

use Database\Seeders\BeautyBerryCatalogueSeeder;
use Illuminate\Console\Command;

class ImportBeautyBerryCatalogue extends Command
{
    protected $signature = 'catalogue:import-beauty-berry
                            {--fresh-json : Re-parse PDF into JSON before import (requires Python pypdf)}';

    protected $description = 'Import Beauty Berry catalogue products from storage/app/beauty_berry_products.json';

    public function handle(): int
    {
        if ($this->option('fresh-json')) {
            $script = storage_path('app/parse_beauty_berry.py');
            if (! file_exists($script)) {
                $this->error('Parser script missing: storage/app/parse_beauty_berry.py');

                return self::FAILURE;
            }

            $this->info('Re-parsing PDF catalogue...');
            $cmd = 'py -3.10 ' . escapeshellarg($script);
            passthru($cmd, $exitCode);
            if ($exitCode !== 0) {
                $this->error('PDF parse failed.');

                return self::FAILURE;
            }
        }

        $json = storage_path('app/beauty_berry_products.json');
        if (! file_exists($json)) {
            $this->error('JSON not found: storage/app/beauty_berry_products.json');

            return self::FAILURE;
        }

        $this->info('Importing products into the store...');
        $this->call('db:seed', [
            '--class' => BeautyBerryCatalogueSeeder::class,
            '--force' => true,
        ]);

        return self::SUCCESS;
    }
}
