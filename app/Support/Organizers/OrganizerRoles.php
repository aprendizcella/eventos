<?php

declare(strict_types=1);

namespace App\Support\Organizers;

/**
 * Canonical organizer team roles.
 *
 * These are domain-owned roles scoped to an organizer's team pivot.
 * They are NOT global Spatie roles — Spatie is only used for global
 * roles like super_admin, platform_admin, and attendee.
 */
enum OrganizerRoles: string
{
    case Admin = 'admin';
    case Editor = 'editor';
    case Viewer = 'viewer';

    /**
     * All role values as a flat array (useful for validation rules).
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Role options for select inputs: [value => label].
     *
     * @return array<string, string>
     */
    public static function selectOptions(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }

    /**
     * Human-readable label for UI selects and badges.
     */
    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Editor => 'Editor',
            self::Viewer => 'Viewer',
        };
    }

    /**
     * Tailwind color classes for role badges.
     *
     * @return array{bg: string, text: string, dark_bg: string, dark_text: string}
     */
    public function badgeColors(): array
    {
        return match ($this) {
            self::Admin => [
                'bg' => 'bg-purple-100',
                'text' => 'text-purple-800',
                'dark_bg' => 'dark:bg-purple-900/30',
                'dark_text' => 'dark:text-purple-300',
            ],
            self::Editor => [
                'bg' => 'bg-blue-100',
                'text' => 'text-blue-800',
                'dark_bg' => 'dark:bg-blue-900/30',
                'dark_text' => 'dark:text-blue-300',
            ],
            self::Viewer => [
                'bg' => 'bg-gray-100',
                'text' => 'text-gray-800',
                'dark_bg' => 'dark:bg-gray-700',
                'dark_text' => 'dark:text-gray-300',
            ],
        };
    }

    /**
     * Check if this role is an admin role.
     */
    public function isAdmin(): bool
    {
        return $this === self::Admin;
    }
}
