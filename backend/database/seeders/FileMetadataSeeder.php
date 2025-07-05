<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\FileMetadata;
use App\Models\User;
use App\Services\FileUploadService;
use Illuminate\Database\Seeder;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use SplFileInfo;

class FileMetadataSeeder extends Seeder
{
    private FileUploadService $fileUploadService;
    
    public function __construct(FileUploadService $fileUploadService)
    {
        $this->fileUploadService = $fileUploadService;
    }
    
    public function run()
    {
        $user = User::first();

        $this->command->info('🚀 Starting file upload seeding via service...');
        
        $sampleDocsPath = storage_path('sample-docs');
        
        if (!File::exists($sampleDocsPath)) {
            $this->command->error("❌ Sample docs directory not found: {$sampleDocsPath}");
            return;
        }
        
        $files = File::files($sampleDocsPath);
        
        if (empty($files)) {
            $this->command->warn('⚠️  No files found in sample-docs directory');
            return;
        }
        
        $bar = $this->command->getOutput()->createProgressBar(count($files));
        $bar->start();
        
        $successCount = 0;
        
        foreach ($files as $file) {
            try {
                $uploadedFile = new UploadedFile(
                    $file->getPathname(),
                    $file->getFilename(),
                    $this->getMimeType($file),
                    null,
                    true
                );
                
                $result = $this->fileUploadService->upload($user, $uploadedFile, config('file.storage.path'));
                
                if ($result->success) {
                    $successCount++;
                } else {
                    $this->command->error("\n❌ Failed: " . $result->message);
                }
            } catch (\Exception $e) {
                $this->command->error("\n❌ Exception: " . $e->getMessage());
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->command->newLine();
        
        $this->command->info("✅ Successfully uploaded {$successCount} files");
    }

    private function getMimeType(SplFileInfo $file): string
    {
        $extension = strtolower($file->getExtension());
        
        return match($extension) {
            'pdf' => 'application/pdf',
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            default => 'application/octet-stream',
        };
    }
}
