<?php

namespace Tes\LaravelGoogleDriveStorage\Interfaces;

interface LaravelGoogleDriveInterface
{
  public static function uploadFile($file, $folderId);
  public static function createFolder($name);
  public static function search($name, $typeSearch);
  public static function listFilesInFolder($folderId);
  public static function getFileMetadata($fileId);
  public static function updateFileMetadata($fileId, $newName);
  public static function donwload($path);
  public static function url($path);
  public static function delete($path);
}
