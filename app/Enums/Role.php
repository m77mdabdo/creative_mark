<?php

namespace App\Enums;

/** The eight staff roles (Phase E/F §8). Names are stored in spatie's roles table. */
enum Role: string
{
    case SuperAdmin = 'super_admin';
    case ContentManager = 'content_manager';
    case Editor = 'editor';
    case SeoManager = 'seo_manager';
    case SalesManager = 'sales_manager';
    case SalesAgent = 'sales_agent';
    case Consultant = 'consultant';
    case Viewer = 'viewer';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::ContentManager => 'Content Manager',
            self::Editor => 'Editor',
            self::SeoManager => 'SEO Manager',
            self::SalesManager => 'Sales Manager',
            self::SalesAgent => 'Sales Agent',
            self::Consultant => 'Consultant',
            self::Viewer => 'Viewer',
        };
    }

    /** MFA is mandatory for roles that publish or export personal data (§8 rule 4). */
    public function requiresMfa(): bool
    {
        return in_array($this, [self::SuperAdmin, self::ContentManager, self::SalesManager], true);
    }
}
