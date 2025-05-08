<?php

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Str;

function siteAsset($resourcePath, $buildDirectory = ".", $manifestFile = 'site-manifest.json') : ?string
{

    $resourceUrl = Vite::useManifestFilename($manifestFile)->asset($resourcePath, $buildDirectory);

    $resourceUrl = Str::replaceFirst("/./", "/", $resourceUrl);

    return $resourceUrl;
}

function appAsset($resourcePath, $buildDirectory = ".", $manifestFile = 'app-manifest.json') : ?string
{
    $resourceUrl = Vite::useManifestFilename($manifestFile)->asset($resourcePath, $buildDirectory);

    $resourceUrl = Str::replaceFirst("/./", "/", $resourceUrl);

    return $resourceUrl;
}
