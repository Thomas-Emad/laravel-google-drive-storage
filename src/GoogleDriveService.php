<?php

namespace Tes\LaravelGoogleDriveStorage;

use Google\Client;
use Google\Service\Drive;
use Illuminate\Support\Str;
use Tes\LaravelGoogleDriveStorage\Interfaces\LaravelGoogleDriveInterface;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Class GoogleDriveService
 *
 * This class provides various functionalities to interact with Google Drive,
 * such as uploading files, creating folders, searching, and managing files and folders.
 */
class GoogleDriveService implements LaravelGoogleDriveInterface
{
  protected static $driveService;

  /**
   * Initialize the Google Drive service.
   *
   * This method checks for the required environment variables and
   * initializes the Google Drive service. If any variable is missing,
   * it logs an error and throws a RuntimeException.
   *
   * @throws \RuntimeException if the required Google Drive configuration is missing.
   */
  protected static function initializeService()
  {
    $clientId = env('GOOGLE_DRIVE_CLIENT_ID');
    $clientSecret = env('GOOGLE_DRIVE_CLIENT_SECRET');
    $refreshToken = env('GOOGLE_DRIVE_REFRESH_TOKEN');

    if ($clientId && $clientSecret && $refreshToken) {
      $client = new Client();
      $client->setAuthConfig([
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
      ]);
      $client->refreshToken($refreshToken);

      static::$driveService = new Drive($client);
    } else {
      $missingKeys = [];

      if (!$clientId) {
        $missingKeys[] = 'GOOGLE_DRIVE_CLIENT_ID';
      }
      if (!$clientSecret) {
        $missingKeys[] = 'GOOGLE_DRIVE_CLIENT_SECRET';
      }
      if (!$refreshToken) {
        $missingKeys[] = 'GOOGLE_DRIVE_REFRESH_TOKEN';
      }

      $errorMessage = 'Missing Google Drive configuration (.env): ' . implode(', ', $missingKeys);
      \Log::error($errorMessage);
      throw new RuntimeException($errorMessage);
    }
  }

  /**
   * Upload a file to Google Drive.
   *
   * @param \Illuminate\Http\UploadedFile $file The file to be uploaded.
   * @param string|null $folderId The ID of the folder to upload the file to (optional).
   * @return object The uploaded file's ID.
   * @throws \RuntimeException if an error occurs during the upload.
   */
  public static function uploadFile($file, $folderId = null)
  {
    try {
      if (!static::$driveService) {
        static::initializeService();
      }

      // Conditionally add 'parents' if the folder is specified or if an environment variable is set
      $fileMetadataArray = [
        'name' => Str::random(10) . '.' . $file->extension(),
      ];

      if ($folderId !== null || !empty(env('GOOGLE_DRIVE_FOLDER_ID'))) {
        $fileMetadataArray['parents'] = [$folderId ?? env('GOOGLE_DRIVE_FOLDER_ID')];
      }

      // Create and upload the file
      $fileMetadata = new Drive\DriveFile($fileMetadataArray);

      $uploadedFile = static::$driveService->files->create($fileMetadata, [
        'data' => file_get_contents($file->getRealPath()),
        'mimeType' => $file->getMimeType(),
        'uploadType' =>  'multipart',
        'fields' => 'id'
      ]);

      // Return the file ID
      return (object) $uploadedFile;
    } catch (\Exception $e) {
      \Log::error('Google Drive Error: ' . $e->getMessage());
      throw new RuntimeException((json_decode($e->getMessage())->error->message));
    }
  }

  /**
   * Create a folder in Google Drive.
   *
   * @param string $name The name of the folder to be created.
   * @return array The created folder's name and ID.
   * @throws \RuntimeException if an error occurs during the folder creation.
   */
  public static function createFolder($name)
  {
    try {
      if (!static::$driveService) {
        static::initializeService();
      }

      // Create and upload the file
      $fileMetadata = new Drive\DriveFile(array(
        'name' => $name,
        'mimeType' => 'application/vnd.google-apps.folder'
      ));
      $folder = static::$driveService->files->create($fileMetadata, array(
        'fields' => 'id'
      ));

      return [
        'folder' => $name,
        'folderId' => $folder->id
      ];
    } catch (\Exception $e) {
      \Log::error('Google Drive Error: ' . $e->getMessage());
      throw new RuntimeException((json_decode($e->getMessage())->error->message));
    }
  }

  /**
   * Search for files or folders in Google Drive by name.
   *
   * @param string $name The name or partial name of the file/folder to search for.
   * @param string $typeSearch Type of search: 'files', 'folders', or 'all'.
   * @return array List of files/folders found with their IDs and names.
   * @throws \RuntimeException if an error occurs during the search.
   */
  public static function search($name, $typeSearch = 'files')
  {
    if (!static::$driveService) {
      static::initializeService();
    }

    // Conditionally add query search file or folder or all
    $typeSearch = match ($typeSearch) {
      'files' => "and mimeType != 'application/vnd.google-apps.folder'",
      'folders' => "and mimeType = 'application/vnd.google-apps.folder'",
      'all' => '',
      default => '',
    };


    try {
      $files = [];
      $pageToken = null;

      do {
        $response = static::$driveService->files->listFiles([
          'q' => "name contains '{$name}' $typeSearch",
          'spaces' => 'drive',
          'pageToken' => $pageToken,
          'fields' => 'nextPageToken, files(id, name)',
        ]);

        foreach ($response->files as $file) {
          $files[] = ['id' => $file->id, 'name' => $file->name];
        }

        $pageToken = $response->nextPageToken;
      } while ($pageToken != null);

      return $files;
    } catch (\Exception $e) {
      \Log::error('Google Drive Error: ' . $e->getMessage());
      throw new RuntimeException((json_decode($e->getMessage())->error->message));
    }
  }

  /**
   * List all files in a specific folder on Google Drive.
   *
   * @param string $folderId The ID of the folder to list files from.
   * @return array List of files with their IDs and names.
   * @throws \RuntimeException if an error occurs during the listing.
   */
  public static function listFilesInFolder($folderId)
  {
    if (!static::$driveService) {
      static::initializeService();
    }

    try {
      $files = [];
      $pageToken = null;

      do {
        $response = static::$driveService->files->listFiles([
          'q' => "'{$folderId}' in parents",
          'spaces' => 'drive',
          'pageToken' => $pageToken,
          'fields' => 'nextPageToken, files(id, name)',
        ]);

        foreach ($response->files as $file) {
          $files[] = ['id' => $file->id, 'name' => $file->name];
        }

        $pageToken = $response->nextPageToken;
      } while ($pageToken != null);

      return $files;
    } catch (\Exception $e) {
      \Log::error('Google Drive Error: ' . $e->getMessage());
      throw new RuntimeException((json_decode($e->getMessage())->error->message));
    }
  }

  /**
   * Get metadata of a specific file on Google Drive.
   *
   * @param string $fileId The ID of the file to get metadata for.
   * @return array File metadata including ID, name, mimeType, size, createdTime, and modifiedTime.
   * @throws \RuntimeException if an error occurs while retrieving the metadata.
   */
  public static function getFileMetadata($fileId)
  {
    if (!static::$driveService) {
      static::initializeService();
    }

    try {
      $file = static::$driveService->files->get($fileId, [
        'fields' => 'id, name, mimeType, size, createdTime, modifiedTime'
      ]);

      return [
        'id' => $file->id,
        'name' => $file->name,
        'mimeType' => $file->mimeType,
        'size' => $file->size,
        'createdTime' => $file->createdTime,
        'modifiedTime' => $file->modifiedTime
      ];
    } catch (\Exception $e) {
      \Log::error('Google Drive Error: ' . $e->getMessage());
      throw new RuntimeException((json_decode($e->getMessage())->error->message));
    }
  }

  /**
   * Update metadata of a specific file on Google Drive.
   *
   * @param string $fileId The ID of the file to be updated.
   * @param string $newName The new name of the file.
   * @return array The updated file's ID and name.
   * @throws \RuntimeException if an error occurs during the update.
   */
  public static function updateFileMetadata($fileId, $newName)
  {
    if (!static::$driveService) {
      static::initializeService();
    }

    try {
      $fileMetadata = new Drive\DriveFile([
        'name' => $newName,
      ]);

      $updatedFile = static::$driveService->files->update($fileId, $fileMetadata, [
        'fields' => 'id, name'
      ]);

      return [
        'id' => $updatedFile->id,
        'name' => $updatedFile->name
      ];
    } catch (\Exception $e) {
      \Log::error('Google Drive Error: ' . $e->getMessage());
      throw new RuntimeException((json_decode($e->getMessage())->error->message));
    }
  }

  /**
   * Download a file from Google Drive.
   *
   * @param string $path The path of the file on Google Drive.
   * @return \Symfony\Component\HttpFoundation\StreamedResponse The file download response.
   * @throws \RuntimeException if an error occurs during the download.
   */
  public static function download($path)
  {
    if (!static::$driveService) {
      static::initializeService();
    }

    try {
      return Storage::disk('google')->download($path);
    } catch (\Exception $e) {
      \Log::error('Google Drive Error: ' . $e->getMessage());
      throw new RuntimeException((json_decode($e->getMessage())->error->message));
    }
  }

  /**
   * Get the URL of a file stored on Google Drive.
   *
   * @param string $path The path (file name) of the file on Google Drive.
   * @return string The URL of the file.
   * @throws \RuntimeException if an error occurs while retrieving the URL.
   */
  public static function url($path)
  {
    if (!static::$driveService) {
      static::initializeService();
    }

    try {
      return Storage::disk('google')->url($path);
    } catch (\Exception $e) {
      \Log::error('Google Drive Error: ' . $e->getMessage());
      throw new RuntimeException((json_decode($e->getMessage())->error->message));
    }
  }

  /**
   * Delete a file from Google Drive.
   *
   * @param string $path The path of the file on Google Drive.
   * @return bool True if the deletion was successful, false otherwise.
   * @throws \RuntimeException if an error occurs during the deletion.
   */
  public static function delete($path)
  {
    if (!static::$driveService) {
      static::initializeService();
    }

    try {
      return Storage::disk('google')->delete($path);
    } catch (\Exception $e) {
      \Log::error('Google Drive Error: ' . $e->getMessage());
      throw new RuntimeException((json_decode($e->getMessage())->error->message));
    }
  }
}
