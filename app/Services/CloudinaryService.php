<?php 

namespace App\Services;

use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class CloudinaryService
{

    /**
     * @param File $file
     * @param string $folder
     */
    public function uploadToCloudinary($file, string $folder) 
    {
        try {
            // Create public id
            $publicIdBase = Str::uuid()->toString();

            // Upload to cloudinary
            $uploaded = Cloudinary::upload($file->getRealPath(), [
                'public_id' => $publicIdBase,
                'folder' => $folder,
                'resource_type' => 'raw',
                'type' => 'private' // upload type
            ]);

            $publicId = $uploaded->getPublicId();

            return $publicId;
        } catch (\Exception $e) {
            Log::error([
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return null;
        }
       
    }


    /**
     * @param string $publicId
     * @param int $expiresInSeconds its in 1 hour as default
     */
     public function retrieveSignedUrlFromCloudinary(string $publicId, int $expiresInSeconds = 3600)
    {
        try {
            // Cloudinary SDK instance
            $cld = new \Cloudinary\Cloudinary(); // reads CLOUDINARY_URL from env

            $expiresAt = time() + $expiresInSeconds;
            // Generate signed URL
            $signedUrl = $cld->raw($publicId)
                ->deliveryType('private') // must match uploaded type
                ->signUrl(true)           // enable signing
                ->toUrl(['expires_at' => $expiresAt]);

            return $signedUrl;
        } catch (\Exception $e) {
            Log::error([
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return null;
        }
    }



}


?>