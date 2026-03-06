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

    $this->uploadedIds = [];
});


test('successful upload to cloudinary', function () {
    $result = $this->cloudinary->uploadToCloudinary($this->file, $this->folder);

    expect($result)->not()->toBeEmpty();

    $this->cloudinary->destroyFromCloudinary([$result]);

});

test('throw exception when upload fails', function () {
    $tempPath = sys_get_temp_dir() . '/invalid.exe';
    file_put_contents($tempPath, str_repeat('A', 0));
    $this->file = new UploadedFile(
        $tempPath,
        'invalid.exe',
        'image/jpeg',
        null,
        true
    );

    $this->expectException(\RuntimeException::class);
    $result = $this->cloudinary->uploadToCloudinary($this->file, $this->folder);

    expect($result)->toBeEmpty();
});

afterEach(function () {
    unlink(sys_get_temp_dir() . '/test.pdf');
});

?>