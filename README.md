# Laravel Google Drive Storage

A Laravel package that integrates Google Drive as a storage option, allowing seamless file uploads, folder creation, and file management directly from your Laravel application.

## Installation

To install this package, you need to include it in your `composer.json` file:

```terminal
composer require tes/laravel-google-drive-storage
```

## Configuration

Ensure you have the following environment variables set in your `.env` file:

```env
GOOGLE_DRIVE_CLIENT_ID=your_client_id
GOOGLE_DRIVE_CLIENT_SECRET=your_client_secret
GOOGLE_DRIVE_REFRESH_TOKEN=your_refresh_token
```

Also you need to add in file `config/filesystems.php` file:

```filesystems
'disks' => [
    .........

    'google' => [
      'driver' => 'google',
      'clientId' => env('GOOGLE_DRIVE_CLIENT_ID'),
      'clientSecret' => env('GOOGLE_DRIVE_CLIENT_SECRET'),
      'refreshToken' => env('GOOGLE_DRIVE_REFRESH_TOKEN'),
    ]
  ],
```

## Usage

Here are examples of how to use the methods provided by the package.

### `uploadFile`

Upload a file to Google Drive.

```php
use Tes\LaravelGoogleDriveStorage\GoogleDriveService;

$file = $request->file('upload'); // Assuming this comes from a form

$response = GoogleDriveService::uploadFile($file);
echo "File uploaded with ID: " . $response->id;
```

### `createFolder`

Create a new folder in Google Drive.

```php
use Tes\LaravelGoogleDriveStorage\GoogleDriveService;

$folderName = 'New Folder';
$response = GoogleDriveService::createFolder($folderName);
echo "Folder created with ID: " . $response['folderId'];
```

### `search`

Search for files or folders in Google Drive by name.

```php
use Tes\LaravelGoogleDriveStorage\GoogleDriveService;

$searchName = 'example';
$files = GoogleDriveService::search($searchName, 'all'); // Can be 'files', 'folders', or 'all'

foreach ($files as $file) {
    echo "Found file with ID: " . $file['id'] . " and name: " . $file['name'] . "\n";
}
```

### `listFilesInFolder`

List files within a specific folder.

```php
use Tes\LaravelGoogleDriveStorage\GoogleDriveService;

$folderId = 'your_folder_id';
$files = GoogleDriveService::listFilesInFolder($folderId);

foreach ($files as $file) {
    echo "File in folder with ID: " . $file['id'] . " and name: " . $file['name'] . "\n";
}
```

### `getFileMetadata`

Retrieve metadata of a specific file.

```php
use Tes\LaravelGoogleDriveStorage\GoogleDriveService;

$fileId = 'your_file_id';
$metadata = GoogleDriveService::getFileMetadata($fileId);

echo "File metadata:\n";
echo "ID: " . $metadata['id'] . "\n";
echo "Name: " . $metadata['name'] . "\n";
echo "MIME Type: " . $metadata['mimeType'] . "\n";
echo "Size: " . $metadata['size'] . "\n";
echo "Created Time: " . $metadata['createdTime'] . "\n";
echo "Modified Time: " . $metadata['modifiedTime'] . "\n";
```

### `updateFileMetadata`

Update the metadata (e.g., name) of a specific file.

```php
use Tes\LaravelGoogleDriveStorage\GoogleDriveService;

$fileId = 'your_file_id';
$newName = 'Updated File Name';
$response = GoogleDriveService::updateFileMetadata($fileId, $newName);

echo "File updated with ID: " . $response['id'] . " and new name: " . $response['name'];
```

### `download`

Download a file from Google Drive.

```php
use Tes\LaravelGoogleDriveStorage\GoogleDriveService;

$path = 'path/to/your/file/on/drive';
$response = GoogleDriveService::download($path); // you got it!!
```

### `url`

Get the URL of a file stored in Google Drive.

```php
use Tes\LaravelGoogleDriveStorage\GoogleDriveService;

$path = 'path/to/your/file/on/drive';
$url = GoogleDriveService::url($path);

echo "File URL: " . $url; // Like This Format: https://drive.google.com/file/d/1jGhj2nX2MNbH5VPwe8SqTKSUu0U-S-VX/view?usp=sharing
```

### `delete`

Delete a file from Google Drive.

```php
use Tes\LaravelGoogleDriveStorage\GoogleDriveService;

$path = 'path/to/your/file/on/drive';
$response = GoogleDriveService::delete($path);

if ($response) {
    echo "File deleted successfully.";
} else {
    echo "Failed to delete file.";
}
```

## License

This package is licensed under the MIT License. See the [LICENSE](LICENSE) file for more details.
