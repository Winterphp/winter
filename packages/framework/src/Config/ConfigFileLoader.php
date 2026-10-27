<?php

declare(strict_types=1);

namespace Winter\Config;

final class ConfigFileLoader
{
    /**
     * Charge les fichiers PHP d'un dossier de configuration (facultatif).
     *
     * Les fichiers `{clé}.php` sont toujours chargés. Les fichiers
     * `{clé}.{profil}.php` ne sont chargés que si le profil est actif.
     *
     * @param list<string> $profiles
     *
     * @return array<string, mixed>
     */
    public function load(string $directory, array $profiles = []): array
    {
        if (!is_dir($directory)) {
            return [];
        }

        $files = glob($directory . '/*.php') ?: [];
        sort($files);

        $items = [];

        foreach ($files as $file) {
            $name = basename($file, '.php');

            if (str_contains($name, '.')) {
                continue;
            }

            $items = $this->mergeFile($items, $name, $file);
        }

        foreach ($profiles as $profile) {
            if ($profile === '') {
                continue;
            }

            foreach ($files as $file) {
                $name = basename($file, '.php');

                if (!str_ends_with($name, '.' . $profile)) {
                    continue;
                }

                $key = substr($name, 0, -\strlen('.' . $profile));

                if ($key === '') {
                    continue;
                }

                $items = $this->mergeFile($items, $key, $file);
            }
        }

        return $items;
    }

    /**
     * @param array<string, mixed> $items
     *
     * @return array<string, mixed>
     */
    private function mergeFile(array $items, string $key, string $file): array
    {
        $data = require $file;

        if (!\is_array($data)) {
            throw new ConfigurationException(sprintf('Configuration file "%s" must return an array.', $file));
        }

        $items[$key] = isset($items[$key]) && \is_array($items[$key])
            ? $this->merge($items[$key], $data)
            : $data;

        return $items;
    }

    /**
     * @param array<string, mixed> $base
     * @param array<string, mixed> $override
     *
     * @return array<string, mixed>
     */
    private function merge(array $base, array $override): array
    {
        foreach ($override as $key => $value) {
            if (\is_array($value) && isset($base[$key]) && \is_array($base[$key])) {
                $base[$key] = $this->merge($base[$key], $value);

                continue;
            }

            $base[$key] = $value;
        }

        return $base;
    }
}
