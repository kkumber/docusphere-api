<?php 

namespace App\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Cloudinary\Cloudinary;
use Illuminate\Http\UploadedFile;

class CloudinaryService
{

    protected Cloudinary $cloudinary;

    public function __construct(Cloudinary $cloudinary)
    {
        $this->cloudinary = $cloudinary;
    }

    public function uploadToCloudinary(UploadedFile $file, string $folder): string 
    {
        try {
            $publicId = (string) Str::uuid();

            $uploaded = $this->cloudinary->uploadApi()->upload(
                $file->getRealPath(),
                [
                    'public_id'    => $publicId,
                    'folder'       => $folder,
                    'resource_type'=> 'raw',
                    'type'         => 'private',
                    'overwrite'    => false,
                ]
            );

            return $uploaded['public_id']; // includes folder

        } catch (\Throwable $e) {
            Log::error('Cloudinary upload failed', [
                'message' => $e->getMessage(),
                'folder'  => $folder,
                'file'    => $file,
                'line'    => $e->getLine(),
                'filed'    => $e->getFile()
            ]);

            throw new \RuntimeException('Failed to upload file to Cloudinary');
        }
    }


    /**
     * @param string $publicId
     * @param int $expiresInSeconds its in 1 hour as default
     */
     public function generateSignedUrl(string $publicId, int $expiresInSeconds = 3600)
    {
        try {

            $expiresAt = time() + $expiresInSeconds;
            // Generate signed URL
            $signedUrl = $this->cloudinary->raw($publicId)
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