<?php

namespace App\Enums;

enum ProjectMemberAccess: string
{
    case Viewer = 'viewer';
    case Contributor = 'contributor';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Viewer => 'Viewer',
            self::Contributor => 'Contributor',
            self::Admin => 'Admin',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Viewer => 'Lihat project & task (tanpa edit)',
            self::Contributor => 'Edit status & deskripsi pada task yang ditugaskan ke Anda',
            self::Admin => 'Tambah task & edit semua task (tanpa hapus task)',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Viewer => 'slate',
            self::Contributor => 'brand',
            self::Admin => 'indigo',
        };
    }

    /** @return list<array{value: string, label: string, description: string, color: string}> */
    public static function options(): array
    {
        return array_map(
            fn (self $access) => [
                'value' => $access->value,
                'label' => $access->label(),
                'description' => $access->description(),
                'color' => $access->color(),
            ],
            self::cases()
        );
    }
}
