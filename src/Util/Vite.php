<?php declare(strict_types=1);

namespace Movary\Util;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use JsonException;
use Movary\Service\ServerSettings;
use Movary\ValueObject\Config;
use RuntimeException;

class Vite
{
    private const CURRENT_DIRECTORY_PATH = 'src/Util';
    private const BUILT_ASSETS_URL = '/build/';

    private string $hostname = '';
    private int $port = 5173;
    private string $baseUrl = '';
    private string $rootDirectory = '';
    private string $viteOutputDirectory = '';

    public function __construct(
        private readonly File $file,
        private readonly Json $json,
        private readonly Config $config,
        private readonly Client $client,
        private readonly ServerSettings $serverSettings
    ) {
        $this->hostname = $config->getAsString('VITE_SERVER_HOST', 'http://localhost');
        $this->port = $config->getAsInt('VITE_SERVER_PORT', 5173);
        $this->baseUrl = $this->hostname . ':' . $this->port;
        $this->rootDirectory = substr(__DIR__, 0, -strlen(self::CURRENT_DIRECTORY_PATH));
        $this->viteOutputDirectory = $this->rootDirectory . '/public/build';
    }

    public function getTags(string $entry, $attributes = []) : string
    {
        $fileExtension = pathinfo($entry, PATHINFO_EXTENSION);
        if ($fileExtension === 'js') {
            return $this->getJsTag($entry, $attributes) .
            $this->getPreloadTags($entry) .
            $this->getCssTags($entry);
        } else if($fileExtension === 'png' || $fileExtension === 'jpg' || $fileExtension === 'jpeg') {
            return $this->getImageTag($entry, $attributes);
        } else if($fileExtension === 'css') {
            return $this->getStyleSheetTag($entry);
        } else if($fileExtension === 'ico') {
            return $this->getIconTag($entry);
        }
    }

    private function getManifest(): array
    {
        try {
            $content = $this->file->readFile($this->rootDirectory . '/public/build/.vite/manifest.json');
            return $this->json->decode($content);
        } catch (JsonException | RuntimeException) {
            return [];
        }
    }

    private function detectHRMFile() : bool
    {
        if($this->file->fileExists($this->rootDirectory . '/public/build/hot')) {
            return true;
        } else {
            return false;
        }
    }

    public function getCompiledFilename(string $entry) : string
    {
        $manifest = $this->getManifest();
        return isset($manifest[$entry]) ? $manifest[$entry]['file'] : '';
    }

    public function getAbsoluteFilepath(string $entry) : string
    {
        return $this->viteOutputDirectory . '/' . $this->getCompiledFilename($entry);
    }

    public function getAssetUrl(string $entry) : string
    {
        return rtrim($this->serverSettings->getApplicationUrl(), '/') . self::BUILT_ASSETS_URL . $this->getCompiledFilename($entry);
    }

    public function getJsTag(string $entry, array $attributes) : string
    {
        $viteServerStatus = $this->detectHRMFile();
        $url = $viteServerStatus ? $this->baseUrl . '/' . $entry : $this->getAssetUrl($entry);
        if(empty($url)) {
            return '';
        }
        if($viteServerStatus) {
            return <<<scripttags
                <script type="module" src="$this->baseUrl/@vite/client"></script> \n
                <script type="module" src="$url"></script>
            scripttags;
        }
        $htmlAttributes = $this->processAttributes($attributes);
        return '<script type="module" src="' . $url . '" '. $htmlAttributes .'></script>';
    }

    public function getPreloadTags($entry) : string
    {
        if($this->detectHRMFile()) {
            return '';
        }

        $tags = '';
        foreach ($this->getJsImportUrls($entry) as $url) {
            $tags .= '<link rel="modulepreload" href="'. $url .'">';
        }
        return $tags;
    }

    private function getJsImportUrls(string $entry) : array
    {
        $urls = [];
        $manifest = $this->getManifest();
    
        if (!empty($manifest[$entry]['imports'])) {
            foreach ($manifest[$entry]['imports'] as $import) {
                $urls[] = $this->getAssetUrl($import);
            }
        }
        return $urls;
    }

    public function getCssTags(string $entry) : string
    {
        if($this->detectHRMFile()) {
            return '';
        }
        $tags = '';
        foreach($this->getCssPaths($entry) as $filepath) {
            $tags .= '<link rel="stylesheet" href="'. $filepath .'">';
        }
        return $tags;
    }

    private function getCssPaths(string $entry) : array
    {
        $urls = [];
        $manifest = $this->getManifest();
    
        if (!empty($manifest[$entry]['css'])) {
            foreach ($manifest[$entry]['css'] as $filepath) {
                $urls[] = rtrim($this->serverSettings->getApplicationUrl(), '/') . self::BUILT_ASSETS_URL . $filepath;
            }
        }
        return $urls;
    }

    public function getImageTag(string $entry, array $attributes) : string
    {
        $viteServerStatus = $this->detectHRMFile();
        $url = $viteServerStatus ? $this->baseUrl . '/' . $entry : $this->getAssetUrl($entry);
        if(empty($url)) {
            return '';
        }
        $htmlAttributes = $this->processAttributes($attributes);
        return '<img ' .  $htmlAttributes . 'src="'. $url .'" />';
    }

    public function getStyleSheetTag(string $entry) : string
    {
        $viteServerStatus = $this->detectHRMFile();
        $url = $viteServerStatus ? $this->baseUrl . '/' . $entry : $this->getAssetUrl($entry);
        if(empty($url)) {
            return '';
        }
        return '<link rel="stylesheet" href="'. $url .'">';
    }

    public function getIconTag(string $entry) : string
    {
        $viteServerStatus = $this->detectHRMFile();
        $url = $viteServerStatus ? $this->baseUrl . '/' . $entry : $this->getAssetUrl($entry);
        if(empty($url)) {
            return '';
        }
        return '<link rel="shortcut icon" href="'. $url .'" type="image/x-icon">';
    }
    
    /**
     * @param array $attributes
     * It looks like this:
     * $attributes = [
     *      'attribute1' => 'value1',
     *      'attribute2' => 'value2',
     *      'class' => 'myclassname'
     *  ]
     */
    private function processAttributes(array $attributes) : string
    {
        $htmlAttributes = '';
        foreach($attributes as $attributeName => $attributeValue) {
            if(empty($attributeValue)) {
                $htmlAttributes .= $attributeName;
                continue;
            } else {
                $htmlAttributes .= $attributeName . '=' . $attributeValue .' ';
            }
        }
        return $htmlAttributes;
    }
}