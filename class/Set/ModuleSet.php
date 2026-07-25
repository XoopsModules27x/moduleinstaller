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

/**
 * Named collection of module dirnames for bulk testing workflows.
 */
final class ModuleSet
{
    /** @var list<string> */
    private array $modules = [];

    /**
     * @param array<int|string, mixed> $modules Raw dirnames; normalized (validated, de-duped, sorted) on construction.
     */
    public function __construct(
        private string $id,
        private string $name,
        private string $description = '',
        array $modules = [],
        private string $createdAt = '',
        private string $updatedAt = '',
    ) {
        $this->id = self::normalizeId($id);
        $this->name = \trim($name);
        $this->description = \trim($description);
        $this->modules = self::normalizeModules($modules);
        if ('' === $this->createdAt) {
            $this->createdAt = \gmdate('c');
        }
        if ('' === $this->updatedAt) {
            $this->updatedAt = $this->createdAt;
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $modules = [];
        if (isset($data['modules']) && \is_array($data['modules'])) {
            $modules = $data['modules'];
        }

        return new self(
            (string) ($data['id'] ?? ''),
            (string) ($data['name'] ?? ''),
            (string) ($data['description'] ?? ''),
            $modules,
            (string) ($data['created_at'] ?? $data['createdAt'] ?? ''),
            (string) ($data['updated_at'] ?? $data['updatedAt'] ?? ''),
        );
    }

    /**
     * @return array{id:string,name:string,description:string,modules:list<string>,created_at:string,updated_at:string}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'modules' => $this->modules,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return list<string>
     */
    public function getModules(): array
    {
        return $this->modules;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): string
    {
        return $this->updatedAt;
    }

    public function hasModule(string $dirname): bool
    {
        return \in_array(\trim($dirname), $this->modules, true);
    }

    public function countModules(): int
    {
        return \count($this->modules);
    }

    public function withName(string $name): self
    {
        $clone = clone $this;
        $clone->name = \trim($name);
        $clone->touch();

        return $clone;
    }

    public function withDescription(string $description): self
    {
        $clone = clone $this;
        $clone->description = \trim($description);
        $clone->touch();

        return $clone;
    }

    /**
     * @param list<string> $modules
     */
    public function withModules(array $modules): self
    {
        $clone = clone $this;
        $clone->modules = self::normalizeModules($modules);
        $clone->touch();

        return $clone;
    }

    public function withId(string $id): self
    {
        $clone = clone $this;
        $clone->id = self::normalizeId($id);
        $clone->touch();

        return $clone;
    }

    public function touch(): void
    {
        $this->updatedAt = \gmdate('c');
    }

    public static function normalizeId(string $id): string
    {
        $id = \mb_strtolower(\trim($id));
        $id = \preg_replace('/[^a-z0-9_-]+/', '-', $id) ?? '';

        return \trim($id, '-_');
    }

    /**
     * @param array<int|string, mixed> $modules
     * @return list<string>
     */
    public static function normalizeModules(array $modules): array
    {
        $out = [];
        foreach ($modules as $item) {
            if (\is_array($item)) {
                $item = $item['dirname'] ?? $item['name'] ?? '';
            }
            $dirname = \trim((string) $item);
            if ('' === $dirname) {
                continue;
            }
            if (! \preg_match('/^[a-zA-Z0-9_-]+$/', $dirname)) {
                continue;
            }
            $out[$dirname] = $dirname;
        }
        $list = \array_values($out);
        \sort($list, \SORT_STRING | \SORT_FLAG_CASE);

        return $list;
    }

    /**
     * Build a unique id slug from a human name.
     */
    public static function idFromName(string $name): string
    {
        $id = self::normalizeId($name);
        if ('' === $id) {
            return 'set-' . \gmdate('YmdHis');
        }

        return $id;
    }
}
