<?php
namespace LemurHttpClient;

class MultipartBuilder
{
    private array $fields = [];
    public function addField(string $name, $value): self
    {
        $this->fields[] = ['name' => $name, 'contents' => $value];
        return $this;
    }
    public function addFile(string $name, string $filePath, ?string $filename = null): self
    {
        $this->fields[] = [
            'name' => $name,
            'contents' => fopen($filePath, 'r'),
            'filename' => $filename ?? basename($filePath)
        ];
        return $this;
    }
    public function build(): array
    {
        return $this->fields;
    }
}
