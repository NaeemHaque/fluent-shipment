<?php

namespace Dev\Test\Inc;

class UploadedFile
{
    public string $name;
    public string $type;
    public string $tmpName;
    public int $error;
    public int $size;

    protected array $tempCopies = [];

    public function __construct($path)
    {
        if (!file_exists($path)) {
            throw new \InvalidArgumentException("File $path does not exist");
        }

        $this->setParams($path);
    }

    protected function setParams($path)
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $this->type = finfo_file($finfo, $path);
        finfo_close($finfo);

        $this->tmpName = $path;
        $this->name = basename($path);
        $this->error = UPLOAD_ERR_OK;
        $this->size = filesize($path);
    }

    public function toArray()
    {
        // Make a temp copy so the original stays intact
        $tmpDir = sys_get_temp_dir();
        $tmpFile = tempnam($tmpDir, 'upload_');

        if (!copy($this->tmpName, $tmpFile)) {
            throw new \RuntimeException("Failed to copy file to temp location");
        }

        $this->tempCopies[] = $tmpFile;

        return [
            'name' => $this->name,
            'type' => $this->type,
            'tmp_name' => $tmpFile,
            'error' => $this->error,
            'size' => filesize($tmpFile),
        ];
    }

    public function cleanUp()
    {
        foreach ($this->tempCopies as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
        $this->tempCopies = [];
    }

    public function __destruct()
    {
        $this->cleanUp();
    }
}
