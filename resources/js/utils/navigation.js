import { sidebarMenu } from '@/config/sidebarMenu';
import { canAccessMenuItem, checkRouteAccess } from '@/utils/permissions';

export function findFirstAccessibleRoute(items, user) {
    for (const item of items) {
        if (item.children?.length) {
            const childRoute = findFirstAccessibleRoute(item.children, user);

            if (childRoute) {
                return childRoute;
            }

            continue;
        }

        if (item.placeholder || item.publicAuthenticated) {
            continue;
        }

        const path = item.to ?? item.path;

        if ((item.routeName || path) && canAccessMenuItem(item, user)) {
            return {
                name: item.routeName,
                path,
            };
        }
    }

    return null;
}

export function resolveDefaultRoute(user) {
    const first = findFirstAccessibleRoute(sidebarMenu, user);

    if (first?.name) {
        return { name: first.name };
    }

    if (first?.path) {
        return { path: first.path };
    }

    return { name: 'no-access' };
}

export function resolvePostLoginRedirect(user, redirectTarget, router) {
    if (redirectTarget) {
        try {
            const resolved = router.resolve(redirectTarget);

            if (
                resolved.matched.length
                && resolved.name !== 'login'
                && resolved.name !== 'not-found'
                && resolved.name !== 'home'
                && checkRouteAccess(resolved.meta, user)
            ) {
                return resolved;
            }
        } catch {
            // Ignore invalid redirect targets.
        }
    }

    return resolveDefaultRoute(user);
}
