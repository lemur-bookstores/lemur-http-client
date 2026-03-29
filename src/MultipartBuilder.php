<?php
/**
 * @package    LemurHttpClient
 * @category   Support
 * @author     [elkincp Chaverra]
 * @copyright  [2026] [lemur-bookstores]
 * @license    MIT
 * @since      1.0.0
 */

namespace LemurHttpClient;

/**
 * Builder for multipart/form-data fields and files.
 *
 * Allows adding fields and files for multipart requests.
 *
 * @package  LemurHttpClient
 * @since    1.0.0
 */
class MultipartBuilder
{
    private array $fields = [];

    /**
     * Adds a field to the multipart data.
     *
     * @param string $name  Field name.
     * @param mixed  $value Field value.
     * @return self         This builder instance.
     * @since 1.0.0
     */
    public function addField(string $name, $value): self
    {
        $this->fields[] = ['name' => $name, 'contents' => $value];
        return $this;
    }

    /**
     * Adds a file to the multipart data.
     *
     * @param string      $name     Field name.
     * @param string      $filePath Path to the file.
     * @param string|null $filename Optional filename to use.
     * @return self                 This builder instance.
     * @since 1.0.0
     */
    public function addFile(string $name, string $filePath, ?string $filename = null): self
    {
        $this->fields[] = [
            'name' => $name,
            'contents' => fopen($filePath, 'r'),
            'filename' => $filename ?? basename($filePath)
        ];
        return $this;
    }

    /**
     * Builds the multipart data array.
     *
     * @return array Multipart fields and files.
     * @since 1.0.0
     */
    public function build(): array
    {
        return $this->fields;
    }
}
