<?php
declare(strict_types=1);

namespace Webiik\Auth\Storage;

class FileStorage implements StorageInterface
{
    /**
     * Storage path
     * @var string
     */
    private $path = './';

    /**
     * Permanent identifier file extension
     * @var string
     */
    private $ext = 'wip';

    /**
     * Delete all permanent files that have been not accessed for given ttl.
     * It's used when permanent login has unlimited ttl to prevent filling the storage with garbage.
     * @var int
     */
    private $ttl = 90 * 24 * 60 * 60;

    /**
     * @param string $path
     */
    public function setPath(string $path): void
    {
        $this->path = rtrim($path, '/') . '/';
    }

    /**
     * @param string $ext
     */
    public function setExt(string $ext): void
    {
        $this->ext = '.' . ltrim($ext, '.');
    }

    /**
     * @param int $ttl
     */
    public function setTtl(int $ttl): void
    {
        $this->ttl = $ttl;
    }

    /**
     * @param int|string $uid
     * @param string $role
     * @param string $selector
     * @param string $key
     * @param int $expiration
     */
    public function store($uid, string $role, string $selector, string $key, int $expiration): void
    {
        $data = serialize([
            'uid' => $uid,
            'role' => $role,
            'selector' => $selector,
            'key' => $key,
            'expiration' => $expiration,
        ]);
        file_put_contents($this->getFileName($selector), $data);
    }

    /**
     * @param string $selector
     * @return array
     */
    public function get(string $selector): array
    {
        $data = [];
        $filename = $this->getFileName($selector);

        if (file_exists($filename)) {
            $data = file_get_contents($filename);
            if ($data) {
                $data = unserialize($data);
            }
        }

        return is_array($data) ? $data : [];
    }

    /**
     * @param string $selector
     */
    public function delete(string $selector): void
    {
        @unlink($this->getFileName($selector));
    }

    /**
     * @param int $ttl
     */
    public function deleteExpired(int $ttl): void
    {
        $ttl = $ttl ? $ttl : $this->ttl;
        $expiration = $_SERVER['REQUEST_TIME'] - $ttl;

        foreach (new \DirectoryIterator($this->path) as $item) {
            if ($item->isFile() && $item->getExtension() == $this->ext) {
                if ($expiration > $item->getMTime()) {
                    @unlink($item->getPathname());
                }
            }
        }
    }

    /**
     * @param string $selector
     * @return string
     */
    private function getFileName(string $selector): string
    {
        return $this->path . $selector . $this->ext;
    }
}