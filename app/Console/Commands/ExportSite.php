<?php

namespace App\Console\Commands;

use App\Models\Site;
use App\Services\SiteExporter;
use Illuminate\Console\Command;

class ExportSite extends Command
{
    protected $signature = 'site:export {slug : The preview site slug} {--to= : Directory to move the zip into}';

    protected $description = 'Export a preview site (site.json + media/) as a zip to seed the real site';

    public function handle(SiteExporter $exporter): int
    {
        $site = Site::withTrashed()->where('slug', $this->argument('slug'))->first();

        if (! $site) {
            $this->error('No site with that slug.');

            return self::FAILURE;
        }

        $path = $exporter->export($site);

        if ($to = $this->option('to')) {
            if (! is_dir($to)) {
                $this->error("Directory not found: {$to}");

                return self::FAILURE;
            }
            $target = rtrim($to, '/\\').DIRECTORY_SEPARATOR.$exporter->filename($site);
            rename($path, $target);
            $path = $target;
        }

        $this->info("Exported {$site->slug} → {$path}");

        return self::SUCCESS;
    }
}
