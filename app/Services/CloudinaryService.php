<?php 

namespace App\Services;

use App\Models\Document;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;

class CloudinaryService
{
    private $cloudinary;

    public function __construct()
    {
        // Initialize Cloudinary using the new SDK
        $this->cloudinary = new \Cloudinary\Cloudinary([
            'cloud' => [
                'cloud_name' => config('cloudinary.cloud_name'),
                'api_key'    => config('cloudinary.api_key'),
                'api_secret' => config('cloudinary.api_secret'),
            ],
            'url' => [
                'secure' => true
            ]
        ]);
    }

    public function uploadToCloudinary(UploadedFile $file, string $folder): string 
    {
        try {
            if (!$file->isValid()) {
                throw new \RuntimeException('Invalid file upload');
            }

            $uuid = (string) Str::uuid();
            $extension = $file->getClientOriginalExtension();
            $publicId = $folder . '/' . $uuid . '.' . $extension;
            $filePath = $file->getRealPath();

            Log::info('Attempting Cloudinary upload', [
                'file' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'folder' => $folder,
            ]);

            // Upload using the SDK's uploadApi
            $result = $this->cloudinary->uploadApi()->upload($filePath, [
                'public_id' => $publicId,
                'resource_type' => 'raw',
                'type' => 'private',
                'overwrite' => false,
                'format' => 'pdf'
            ]);

            // DELETE THIS ON PROD
            Log::info('Cloudinary upload response', [
                'result' => $result,
            ]);

            if (!isset($result['public_id'])) {
                throw new \RuntimeException('Upload succeeded but no public_id in response');
            }

            Log::info('Upload successful', ['public_id' => $result['public_id']]);
            return $result['public_id'];

        } catch (\Throwable $e) {
            Log::error('Cloudinary upload failed', [
                'message' => $e->getMessage(),
                'folder'  => $folder,
                'file'    => $file->getClientOriginalName(),
                'line'    => $e->getLine(),
                'file_path' => $e->getFile(),
            ]);

            throw new \RuntimeException('Failed to upload file to Cloudinary: ' . $e->getMessage());
        }
    }

   public function generateSignedUrl(string $publicId, int $expiresInSeconds = 3600): string
    {
        try {
            $cloudName = config('cloudinary.cloud_name');
            $apiKey = config('cloudinary.api_key');
            $apiSecret = config('cloudinary.api_secret');
            
            $timestamp = time() + $expiresInSeconds;
            
            // Sign for the API endpoint
            $toSign = "public_id={$publicId}&timestamp={$timestamp}{$apiSecret}";
            $signature = sha1($toSign);
            
            // Use Cloudinary's download API
            $url = sprintf(
                'https://api.cloudinary.com/v1_1/%s/raw/download?public_id=%s&timestamp=%s&signature=%s&api_key=%s',
                $cloudName,
                urlencode($publicId),
                $timestamp,
                $signature,
                $apiKey
            );

            Log::info('Generated download URL', [
                'public_id' => $publicId,
                'url' => $url
            ]);

            return $url;

        } catch (\Throwable $e) {
            Log::error('Cloudinary URL generation failed', [
                'public_id' => $publicId,
                'message'   => $e->getMessage(),
            ]);

            throw new \RuntimeException('Failed to generate secure access link.');
        }
    }

    private function generateSignature(string $publicId, int $expiresAt): string
    {
        $apiSecret = config('cloudinary.api_secret');
        $timestamp = time() + $expiresAt;
        
        $toSign = "timestamp={$timestamp}&public_id={$publicId}";
        return hash('sha256', $toSign . $apiSecret);
    }

    /**
     * Delete resources via public ids
     * @param array of strings $publicIds
     */
    public function destroyFromCloudinary(array $publicIds): void
    {
        try {
            Log::info('Deleting File from Cloudinary', [
                'public_ids' => $publicIds
            ]);

            $result = $this->cloudinary
            ->adminApi()
            ->deleteAssets($publicIds, [
                'resource_type' => 'raw',
                'type' => 'private'
            ]);

        if (!isset($result['deleted'])) {
            throw new \RuntimeException('Unexpected Cloudinary response.');
        }

        $failed = collect($result['deleted'])
            ->filter(fn ($status) => $status !== 'deleted');

        if ($failed->isNotEmpty()) {
            Log::warning('Some Cloudinary assets were not deleted', [
                'failed' => $failed,
            ]);
        }

        Log::info('Deleted File from Cloudinary', [
            'result' => $result,
            'public_ids' => $publicIds,
        ]);

        } catch (\Throwable $e) {
            Log::error('Failed to delete file from Cloudinary', [
                'public_ids' => $publicIds,
                'message'   => $e->getMessage(),
            ]);

            throw new \RuntimeException('Failed to delete file from Cloudinary.');
        }
    }
}