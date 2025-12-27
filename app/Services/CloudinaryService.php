<?php 

namespace App\Services;

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

            $publicId = (string) Str::uuid();
            $filePath = $file->getRealPath();

            Log::info('Attempting Cloudinary upload', [
                'file' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'folder' => $folder,
            ]);

            // Upload using the SDK's uploadApi
            $result = $this->cloudinary->uploadApi()->upload($filePath, [
                'public_id' => $folder . '/' . $publicId,
                'resource_type' => 'raw',
                'type' => 'private',
                'overwrite' => false,
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
            // Generate a private download URL
            $options = [
                'resource_type' => 'raw',
                'type' => 'private',
                'sign_url' => true,
                'secure' => true,
                'attachment' => true,
                'expires_at' => time() + $expiresInSeconds,
            ];

            // Build the URL using Cloudinary's URL builder
            $url = $this->cloudinary->image($publicId)
                ->toUrl();

            // For raw files, we need to manually construct the URL
            $cloudName = config('cloudinary.cloud_name');
            $signature = $this->generateSignature($publicId, $expiresInSeconds);
            
            $url = sprintf(
                'https://res.cloudinary.com/%s/raw/private/s--%s--/%s',
                $cloudName,
                $signature,
                $publicId
            );

            return $url;

        } catch (\Throwable $e) {
            Log::error('Cloudinary URL generation failed', [
                'public_id' => $publicId,
                'message'   => $e->getMessage()
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
}