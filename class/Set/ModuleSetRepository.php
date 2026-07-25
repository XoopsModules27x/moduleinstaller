<?php declare(strict_types=1);

namespace XoopsModules\Moduleinstaller\Set;

/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.
*/

/**
 * @copyright 2000-2026 XOOPS Project (https://xoops.org)
 * @license   GNU GPL 2.0 or later (https://www.gnu.org/licenses/gpl-2.0.html)
 * @author    Michael Beck (mamba)
 */

use Xmf\Yaml;

/**
 * YAML file storage for module sets under XOOPS_VAR_PATH.
 */
class ModuleSetRepository
{
    private string $storagePath;

    public function __construct(?string $storagePath = null)
    {
        if (null !== $storagePath && '' !== $storagePath) {
            $this->storagePath = \rtrim($storagePath, '/\\');
        } else {
            $base = \defined('XOOPS_VAR_PATH') ? \XOOPS_VAR_PATH : (\XOOPS_ROOT_PATH . '/../xoops_data');
            $this->storagePath = \rtrim($base, '/\\') . '/configs/moduleinstaller/sets';
        }
    }

    public function getStoragePath(): string
    {
        return $this->storagePath;
    }

    /**
     * Ensure storage directory exists.
     *
     * @throws \RuntimeException
     */
    public function ensureStorage(): void
    {
        if (\is_dir($this->storagePath)) {
            return;
        }
        if (! @\mkdir($this->storagePath, 0777, true) && ! \is_dir($this->storagePath)) {
            throw new \RuntimeException('Cannot create module set storage: ' . $this->storagePath);
        }
        // Deny web access if document root somehow overlaps
        $htaccess = $this->storagePath . '/.htaccess';
        if (! \is_file($htaccess)) {
            @\file_put_contents($htaccess, "Require all denied\nDeny from all\n");
        }
        $index = $this->storagePath . '/index.php';
        if (! \is_file($index)) {
            @\file_put_contents($index, "<?php\nheader('HTTP/1.0 403 Forbidden');\nexit;\n");
        }
    }

    /**
     * @return list<ModuleSet>
     */
    public function listAll(): array
    {
        $this->ensureStorage();
        $glob = \glob($this->storagePath . '/*.yml');
        $files = false === $glob ? [] : $glob;
        $sets = [];
        foreach ($files as $file) {
            $set = $this->readFile($file);
            if ($set instanceof \XoopsModules\Moduleinstaller\Set\ModuleSet) {
                $sets[] = $set;
            }
        }
        \usort($sets, static fn (ModuleSet $a, ModuleSet $b): int => \strcasecmp($a->getName(), $b->getName()));

        return $sets;
    }

    public function get(string $id): ?ModuleSet
    {
        $id = ModuleSet::normalizeId($id);
        if ('' === $id) {
            return null;
        }
        $path = $this->pathForId($id);
        if (! \is_file($path)) {
            return null;
        }

        return $this->readFile($path);
    }

    public function exists(string $id): bool
    {
        $id = ModuleSet::normalizeId($id);

        return '' !== $id && \is_file($this->pathForId($id));
    }

    /**
     * @throws \RuntimeException|\InvalidArgumentException
     */
    public function save(ModuleSet $set): bool
    {
        $this->ensureStorage();
        $id = $set->getId();
        if ('' === $id) {
            throw new \InvalidArgumentException('Module set id is required');
        }
        if ('' === $set->getName()) {
            throw new \InvalidArgumentException('Module set name is required');
        }

        $set->touch();
        $path = $this->pathForId($id);
        $bytes = Yaml::save($set->toArray(), $path);
        if (false === $bytes) {
            throw new \RuntimeException('Failed to write module set: ' . $path);
        }

        return true;
    }

    public function delete(string $id): bool
    {
        $id = ModuleSet::normalizeId($id);
        if ('' === $id) {
            return false;
        }
        $path = $this->pathForId($id);
        if (! \is_file($path)) {
            return false;
        }

        return @\unlink($path);
    }

    /**
     * Duplicate a set with a new id and name.
     *
     * @throws \RuntimeException|\InvalidArgumentException
     */
    public function duplicate(string $sourceId, string $newName, ?string $newId = null): ModuleSet
    {
        $source = $this->get($sourceId);
        if (! $source instanceof \XoopsModules\Moduleinstaller\Set\ModuleSet) {
            throw new \InvalidArgumentException('Source set not found: ' . $sourceId);
        }

        $name = \trim($newName);
        if ('' === $name) {
            $name = $source->getName() . ' (copy)';
        }

        $id = ModuleSet::normalizeId($newId ?? ModuleSet::idFromName($name));
        if ('' === $id) {
            $id = 'set-' . \gmdate('YmdHis');
        }
        $id = $this->uniqueId($id);

        $copy = new ModuleSet(
            $id,
            $name,
            $source->getDescription(),
            $source->getModules(),
        );
        $this->save($copy);

        return $copy;
    }

    /**
     * Create a set from currently active modules.
     *
     * @param list<string> $activeDirnames
     */
    public function createFromModules(string $name, array $activeDirnames, string $description = ''): ModuleSet
    {
        $id = $this->uniqueId(ModuleSet::idFromName($name));
        $set = new ModuleSet($id, $name, $description, $activeDirnames);
        $this->save($set);

        return $set;
    }

    public function uniqueId(string $preferred): string
    {
        $base = ModuleSet::normalizeId($preferred);
        if ('' === $base) {
            $base = 'set';
        }
        $id = $base;
        $n = 2;
        while ($this->exists($id)) {
            $id = $base . '-' . $n;
            ++$n;
        }

        return $id;
    }

    private function pathForId(string $id): string
    {
        return $this->storagePath . '/' . ModuleSet::normalizeId($id) . '.yml';
    }

    private function readFile(string $path): ?ModuleSet
    {
        $data = Yaml::read($path);
        if (! \is_array($data)) {
            return null;
        }
        // Prefer filename as id if missing/mismatched
        $basename = \basename($path, '.yml');
        if (! isset($data['id']) || '' === \trim((string) $data['id'])) {
            $data['id'] = $basename;
        }

        try {
            return ModuleSet::fromArray($data);
        } catch (\Throwable) {
            return null;
        }
    }
}
