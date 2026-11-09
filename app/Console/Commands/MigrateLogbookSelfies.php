<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class MigrateLogbookSelfies extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:logbook-selfies {--dry-run : Run without making changes}';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Migrate logbook selfies from public_path to storage disk';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->info('🔍 Running in DRY-RUN mode (no changes will be made)');
        }
        
        $this->info('Starting migration of logbook selfies...');
        
        // Get all logbook_daily records dengan realisasi_photo
        $logbooks = DB::table('logbook_daily')
            ->whereNotNull('realisasi_photo')
            ->where('realisasi_photo', '!=', '')
            ->get();
        
        $total = count($logbooks);
        $migrated = 0;
        $skipped = 0;
        $failed = 0;
        
        if ($total === 0) {
            $this->info('✅ Tidak ada file yang perlu dimigrasikan');
            return 0;
        }
        
        $this->info("📁 Total records to process: {$total}\n");
        
        $bar = $this->output->createProgressBar($total);
        $bar->start();
        
        foreach ($logbooks as $logbook) {
            try {
                $filePath = $logbook->realisasi_photo;
                
                // Check if already in storage format
                if (Storage::disk('public')->exists($filePath)) {
                    $skipped++;
                    $bar->advance();
                    continue;
                }
                
                // Try to find file in public_path
                $publicPath = public_path($filePath);
                
                if (!File::exists($publicPath)) {
                    $this->warn("\n❌ File not found: {$filePath}");
                    $failed++;
                    $bar->advance();
                    continue;
                }
                
                if (!$dryRun) {
                    // Read file dari public
                    $fileContent = File::get($publicPath);
                    
                    // Write to storage
                    Storage::disk('public')->put($filePath, $fileContent);
                    
                    // Delete from public (optional - keep backup)
                    // File::delete($publicPath);
                }
                
                $migrated++;
                $bar->advance();
                
            } catch (\Exception $e) {
                $this->error("\n❌ Error processing {$logbook->id}: " . $e->getMessage());
                $failed++;
                $bar->advance();
            }
        }
        
        $bar->finish();
        
        $this->newLine();
        $this->info("\n✅ Migration Summary:");
        $this->info("   Migrated: {$migrated}");
        $this->info("   Already in storage: {$skipped}");
        $this->info("   Failed: {$failed}");
        
        if ($dryRun) {
            $this->warn("\n⚠️  This was a DRY-RUN. Run without --dry-run to actually migrate files.");
        } else {
            $this->info("\n✨ Migration completed successfully!");
        }
        
        return 0;
    }
}
