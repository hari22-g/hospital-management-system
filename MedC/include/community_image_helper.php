<?php

if (!function_exists('medc_normalize_community_image_key')) {
    function medc_add_local_community_image_source(array &$sources, string $localhost, string $relativePath): void
    {
        $relativePath = ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath), DIRECTORY_SEPARATOR);
        $absolutePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . $relativePath;

        if (is_file($absolutePath)) {
            $webPath = str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);
            $sources[] = $localhost . $webPath;
        }
    }

    function medc_normalize_community_image_key(string $value): string
    {
        $value = strtolower(pathinfo($value, PATHINFO_FILENAME));
        $value = str_replace('community', '', $value);

        return preg_replace('/[^a-z0-9]+/', '', $value) ?? '';
    }

    function medc_find_named_community_image(string $communityName): ?string
    {
        static $communityImages = null;

        if ($communityImages === null) {
            $communityImages = [];
            $communityDirectory = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'Communities';

            if (is_dir($communityDirectory)) {
                foreach (scandir($communityDirectory) as $fileName) {
                    if ($fileName === '.' || $fileName === '..') {
                        continue;
                    }

                    $filePath = $communityDirectory . DIRECTORY_SEPARATOR . $fileName;
                    if (!is_file($filePath)) {
                        continue;
                    }

                    $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                    if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                        continue;
                    }

                    $key = medc_normalize_community_image_key($fileName);
                    if ($key !== '' && !isset($communityImages[$key])) {
                        $communityImages[$key] = $fileName;
                    }
                }
            }
        }

        $communityKey = medc_normalize_community_image_key($communityName);

        return $communityKey !== '' ? ($communityImages[$communityKey] ?? null) : null;
    }

    function medc_get_named_community_extra_fallbacks(string $communityName): array
    {
        $communityKey = medc_normalize_community_image_key($communityName);

        $fallbacks = [
            'alzheimer' => [
                'uploads/Diseases_img/Alzheimer/alz2.jpg',
                'uploads/Diseases_img/Alzheimer/alz3.jpg',
            ],
        ];

        return $fallbacks[$communityKey] ?? [];
    }

    function medc_get_community_image_sources(array $community, string $localhost): array
    {
        $sources = [];
        $primaryImage = trim((string)($community['image'] ?? ''));

        if ($primaryImage !== '') {
            if (preg_match('/^https?:\/\//i', $primaryImage)) {
                $sources[] = $primaryImage;
            } else {
                $primaryImageFile = basename($primaryImage);
                medc_add_local_community_image_source($sources, $localhost, 'uploads/Communities/' . $primaryImageFile);
            }
        }

        $secondaryImageFile = medc_find_named_community_image((string)($community['name'] ?? ''));
        if ($secondaryImageFile !== null) {
            $sources[] = $localhost . 'uploads/Communities/' . rawurlencode($secondaryImageFile);
        }

        foreach (medc_get_named_community_extra_fallbacks((string)($community['name'] ?? '')) as $relativePath) {
            medc_add_local_community_image_source($sources, $localhost, $relativePath);
        }

        $sources[] = $localhost . 'include/homepage_slider/hello.png';

        return array_values(array_unique($sources));
    }
}
