function normalizeNames(items) {
    if (!items?.length) {
        return [];
    }

    return items.map((item) => (typeof item === 'string' ? item : item?.name)).filter(Boolean);
}

export function buildAuthUser(user, roles = [], permissions = []) {
    const normalizedRoles = normalizeNames(roles.length ? roles : user?.roles);
    const normalizedPermissions = normalizeNames(permissions.length ? permissions : user?.permissions);

    if (!user) {
        return null;
    }

    return {
        ...user,
        roles: normalizedRoles,
        permissions: normalizedPermissions,
    };
}

export function hasRole(user, role) {
    if (!user) {
        return false;
    }

    return normalizeNames(user.roles).includes(role);
}

export function isSuperAdmin(user) {
    return hasRole(user, 'super-admin');
}

export function hasAnyRole(user, roles) {
    if (!roles?.length || !user) {
        return false;
    }

    return roles.some((role) => hasRole(user, role));
}

export function can(user, permission) {
    if (!user) {
        return false;
    }

    if (isSuperAdmin(user)) {
        return true;
    }

    return normalizeNames(user.permissions).includes(permission);
}

export function canAny(user, permissions = []) {
    if (!user) {
        return false;
    }

    if (isSuperAdmin(user)) {
        return true;
    }

    if (!permissions.length) {
        return false;
    }

    return permissions.some((permission) => can(user, permission));
}

export function canAll(user, permissions = []) {
    if (!user) {
        return false;
    }

    if (isSuperAdmin(user)) {
        return true;
    }

    if (!permissions.length) {
        return false;
    }

    return permissions.every((permission) => can(user, permission));
}

export function getItemPermissions(item) {
    if (item.permissions?.length) {
        return item.permissions;
    }

    if (item.permissionAny?.length) {
        return item.permissionAny;
    }

    if (item.permission) {
        return [item.permission];
    }

    return [];
}

export function getItemPermissionMode(item) {
    if (item.permissionMode) {
        return item.permissionMode;
    }

    if (item.permissionAny?.length) {
        return 'any';
    }

    return 'any';
}

export function canAccessMenuItem(item, user) {
    if (isSuperAdmin(user)) {
        return true;
    }

    if (item.publicAuthenticated) {
        return !!user;
    }

    const permissions = getItemPermissions(item);

    if (!permissions.length) {
        return false;
    }

    if (getItemPermissionMode(item) === 'all') {
        return canAll(user, permissions);
    }

    return canAny(user, permissions);
}

export function filterMenuByPermissions(items, user) {
    return items
        .map((item) => {
            if (item.children?.length) {
                const children = filterMenuByPermissions(item.children, user);

                if (!children.length) {
                    return null;
                }

                return {
                    ...item,
                    children,
                };
            }

            if (canAccessMenuItem(item, user)) {
                return {
                    ...item,
                    children: [],
                };
            }

            return null;
        })
        .filter(Boolean);
}

export function checkRouteAccess(meta, user) {
    if (!meta) {
        return true;
    }

    if (isSuperAdmin(user)) {
        return true;
    }

    if (meta.role && !hasRole(user, meta.role)) {
        return false;
    }

    if (meta.roles?.length && !hasAnyRole(user, meta.roles)) {
        return false;
    }

    const permissions = meta.permissions?.length
        ? meta.permissions
        : meta.permissionAny?.length
            ? meta.permissionAny
            : meta.permission
                ? [meta.permission]
                : [];

    if (!permissions.length) {
        return true;
    }

    const mode = meta.permissionMode ?? (meta.permissionAny ? 'any' : meta.permission ? 'all' : 'any');

    return mode === 'all'
        ? canAll(user, permissions)
        : canAny(user, permissions);
}

export function usePermissions(getUser) {
    return {
        hasRole: (role) => hasRole(getUser(), role),
        hasAnyRole: (roles) => hasAnyRole(getUser(), roles),
        can: (permission) => can(getUser(), permission),
        canAny: (permissions) => canAny(getUser(), permissions),
        canAll: (permissions) => canAll(getUser(), permissions),
    };
}
