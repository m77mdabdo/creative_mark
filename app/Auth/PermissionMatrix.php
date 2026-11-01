<?php

namespace App\Auth;

use App\Enums\Role;

/**
 * Approved RBAC matrix (Phase E/F §8, Gate 4). Deny by default: a role has only what is listed here.
 * Permission names are fixed in code; roles are synced from this table by RbacSeeder.
 * "Own" scopes (sales agent, consultant) are enforced in policies and query scopes, not here.
 */
final class PermissionMatrix
{
    private const SA = Role::SuperAdmin;

    private const CM = Role::ContentManager;

    private const ED = Role::Editor;

    private const SEO = Role::SeoManager;

    private const SM = Role::SalesManager;

    private const AG = Role::SalesAgent;

    private const CO = Role::Consultant;

    private const VW = Role::Viewer;

    /** @return array<string, list<Role>> */
    public static function all(): array
    {
        return [
            'admin.access' => [self::SA, self::CM, self::ED, self::SEO, self::SM, self::AG, self::CO, self::VW],
            'pages.edit' => [self::SA, self::CM, self::ED, self::SEO],
            'pages.manage' => [self::SA, self::CM],
            'pages.publish' => [self::SA, self::CM],
            'content.edit' => [self::SA, self::CM, self::ED],
            'content.publish' => [self::SA, self::CM],
            'media.upload' => [self::SA, self::CM, self::ED],
            'media.delete' => [self::SA, self::CM],
            'site.design' => [self::SA, self::CM],
            'translations.edit' => [self::SA, self::CM, self::ED, self::SEO],
            'translations.approve' => [self::SA, self::CM],
            'seo.edit' => [self::SA, self::CM, self::SEO],
            'leads.view' => [self::SA, self::SM, self::AG],
            'leads.update' => [self::SA, self::SM, self::AG],
            'leads.assign' => [self::SA, self::SM],
            'leads.export' => [self::SA, self::SM],
            'leads.files' => [self::SA, self::SM, self::AG],
            'consultations.view' => [self::SA, self::SM, self::AG, self::CO],
            'consultations.update' => [self::SA, self::SM, self::AG, self::CO],
            'privacy.requests' => [self::SA],
            'analytics.view' => [self::SA, self::CM, self::SEO, self::SM, self::VW],
            'audit.view' => [self::SA, self::CM],
            'settings.general' => [self::SA, self::CM],
            'settings.security' => [self::SA],
            'users.manage' => [self::SA],
        ];
    }

    /** @return list<string> */
    public static function permissionsFor(Role $role): array
    {
        return array_keys(array_filter(self::all(), fn (array $roles) => in_array($role, $roles, true)));
    }
}
