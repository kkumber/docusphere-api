<?php

use App\Services\CloudinaryService;
use Illuminate\Http\UploadedFile;

beforeEach(function () {
   $this->folder = 'test/'; 
    // temp file
    $this->tempPath = sys_get_temp_dir() . '/test.pdf';
    file_put_contents($this->tempPath, str_repeat('A', 1024));

    $this->file = new UploadedFile(
        $this->tempPath,
        'test.pdf',
        'application/pdf',
        null,
        true
    );

   $this->cloudinary = new CloudinaryService(new \Cloudinary\Cloudinary([
            'cloud' => [
                'cloud_name' => config('cloudinary.cloud_name'),
                'api_key'    => config('cloudinary.api_key'),
                'api_secret' => config('cloudinary.api_secret'),
            ],
            'url' => [
                'secure' => true
            ]
        ]));

    $this->publicId = $this->cloudinary->uploadToCloudinary($this->file, $this->folder);
});

test('generate signed url', function () {
    $result = $this->cloudinary->generateSignedUrl($this->publicId, 300);

    expect($result)->not()->toBeEmpty();
});

afterEach(function () {
    unlink(sys_get_temp_dir() . '/test.pdf');
    $this->cloudinary->destroyFromCloudinary([$this->publicId]);
});

?>