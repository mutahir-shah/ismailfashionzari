<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Log;

class ProductImportImageService
{
    public const BATCH_SIZE = 10;
    private const MAX_BYTES = 5242880;

    public function manifestPath(string $token): string
    {
        if (!preg_match('/^[a-f0-9]{40}$/', $token)) {
            throw new \InvalidArgumentException('Invalid image import token.');
        }

        return storage_path('app/product-imports/'.$token.'.json');
    }

    public function createManifest(array $items): ?string
    {
        if ($items === []) {
            return null;
        }

        $token = sha1(implode('|', array_column($items, 'key')).'|'.microtime(true));
        $directory = dirname($this->manifestPath($token));
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        file_put_contents($this->manifestPath($token), json_encode([
            'items' => array_values($items), 'completed' => 0, 'failed' => 0,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);

        return $token;
    }

    public function process(string $token, int $limit = self::BATCH_SIZE): array
    {
        $path = $this->manifestPath($token);
        if (!is_file($path)) {
            throw new \RuntimeException('Image import was not found or has expired.');
        }
        $manifest = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        $processed = 0;
        foreach ($manifest['items'] as &$item) {
            if ($processed >= max(1, min(20, $limit)) || ($item['status'] ?? 'pending') === 'completed') {
                continue;
            }
            $processed++;
            try {
                $this->sync($item);
                $item['status'] = 'completed';
                unset($item['error']);
            } catch (\Throwable $exception) {
                $item['status'] = 'failed';
                $item['error'] = $exception->getMessage();
                Log::warning('Product CSV image import failed', ['key' => $item['key'], 'error' => $exception->getMessage()]);
            }
        }
        unset($item);
        $manifest['completed'] = count(array_filter($manifest['items'], fn ($item) => ($item['status'] ?? null) === 'completed'));
        $manifest['failed'] = count(array_filter($manifest['items'], fn ($item) => ($item['status'] ?? null) === 'failed'));
        $manifest['pending'] = count($manifest['items']) - $manifest['completed'] - $manifest['failed'];
        file_put_contents($path, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);

        return $manifest;
    }

    private function sync(array $item): void
    {
        $product = Product::where('code', $item['product_code'])->firstOrFail();
        $extension = $this->download($item['source'], $item['filename_base']);
        $filename = $item['filename_base'].'.'.$extension;
        $images = array_values(array_filter(explode(',', (string) $product->image), fn ($name) => $name !== 'zummXD2dvAtI.png'));
        $images = array_values(array_filter($images, fn ($name) => !str_starts_with($name, $item['filename_base'].'.')));
        $images[] = $filename;
        $product->image = implode(',', array_unique($images));
        $product->save();
    }

    private function download(string $source, string $filenameBase): string
    {
        $directory = public_path('images/product');
        $types = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
        foreach ($types as $mime => $extension) {
            $cached = $directory.'/'.$filenameBase.'.'.$extension;
            if (is_file($cached) && filesize($cached) <= self::MAX_BYTES && mime_content_type($cached) === $mime) {
                return $extension;
            }
        }

        $temporary = tempnam(sys_get_temp_dir(), 'csv_img_');
        try {
            if (!filter_var($source, FILTER_VALIDATE_URL)) {
                $public = realpath(public_path());
                $local = realpath(public_path($source));
                if (!$local || !str_starts_with($local, $public) || !is_file($local)) {
                    throw new \RuntimeException('Local image is outside the public directory.');
                }
                if (filesize($local) > self::MAX_BYTES || !copy($local, $temporary)) {
                    throw new \RuntimeException('Local image exceeds the size limit or cannot be read.');
                }
            } else {
                $this->downloadRemote($source, $temporary);
            }
            $mime = mime_content_type($temporary);
            if (!isset($types[$mime])) {
                throw new \RuntimeException('Image MIME type is not allowed: '.$mime);
            }
            if (!is_dir($directory)) mkdir($directory, 0755, true);
            $destination = $directory.'/'.$filenameBase.'.'.$types[$mime];
            if (!is_file($destination) && !copy($temporary, $destination)) {
                throw new \RuntimeException('Unable to persist downloaded image.');
            }
            return $types[$mime];
        } finally {
            if (is_file($temporary)) @unlink($temporary);
        }
    }

    private function downloadRemote(string $url, string $temporary): void
    {
        $current = $url;
        for ($hop = 0; $hop <= 3; $hop++) {
            $parts = parse_url($current);
            if (!in_array($parts['scheme'] ?? '', ['http', 'https'], true) || empty($parts['host'])) {
                throw new \RuntimeException('Only HTTP and HTTPS image URLs are supported.');
            }
            $addresses = gethostbynamel($parts['host']) ?: [];
            if ($addresses === []) throw new \RuntimeException('Image host cannot be resolved.');
            foreach ($addresses as $address) {
                if (!filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    throw new \RuntimeException('Private or reserved image destinations are denied.');
                }
            }
            $ip = $addresses[0];
            $port = $parts['port'] ?? ($parts['scheme'] === 'https' ? 443 : 80);
            $handle = fopen($temporary, 'wb');
            $curl = curl_init($current);
            $options = [CURLOPT_FILE => $handle, CURLOPT_TIMEOUT => 15, CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_FOLLOWLOCATION => false, CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_RESOLVE => ["{$parts['host']}:{$port}:{$ip}"], CURLOPT_NOPROGRESS => false,
                CURLOPT_PROGRESSFUNCTION => fn ($resource, $total, $now) => $now > self::MAX_BYTES ? 1 : 0];
            if ($caBundle = $this->resolveCaBundlePath()) {
                $options[CURLOPT_CAINFO] = $caBundle;
            }
            curl_setopt_array($curl, $options);
            $ok = curl_exec($curl);
            $curlError = curl_error($curl);
            $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $redirect = curl_getinfo($curl, CURLINFO_REDIRECT_URL);
            curl_close($curl); fclose($handle);
            if (in_array($status, [301, 302, 303, 307, 308], true) && $redirect) {
                if ($hop === 3) throw new \RuntimeException('Too many image redirects.');
                $current = $redirect; continue;
            }
            if (!$ok || $status < 200 || $status >= 300 || filesize($temporary) > self::MAX_BYTES) {
                throw new \RuntimeException('Image download failed or exceeded 5 MB'.($curlError ? ": {$curlError}" : '.'));
            }
            return;
        }
    }

    public function resolveCaBundlePath(): ?string
    {
        $configured = trim((string) ini_get('curl.cainfo'));
        if ($configured !== '' && is_file($configured)) {
            return $configured;
        }

        $candidates = [
            dirname(PHP_BINARY, 4).DIRECTORY_SEPARATOR.'etc'.DIRECTORY_SEPARATOR.'ssl'.DIRECTORY_SEPARATOR.'cacert.pem',
            base_path('vendor/rmccue/requests/certificates/cacert.pem'),
            base_path('vendor/razorpay/razorpay/libs/Requests-2.0.4/certificates/cacert.pem'),
        ];
        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        if ($configured !== '') {
            throw new \RuntimeException("Configured cURL CA bundle does not exist: {$configured}");
        }

        return null;
    }
}
