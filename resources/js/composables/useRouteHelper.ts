import * as routesNS from '@/routes';
import admin from '@/routes/admin';
import superadmin from '@/routes/superadmin';
import rider from '@/routes/rider';
import staff from '@/routes/staff';
import password from '@/routes/password';
import profile from '@/routes/profile';
import verification from '@/routes/verification';
import login from '@/routes/login';
import register from '@/routes/register';
import security from '@/routes/security';
import appearance from '@/routes/appearance';
import twoFactor from '@/routes/two-factor';
import userPassword from '@/routes/user-password';
import storage from '@/routes/storage';
import boost from '@/routes/boost';
import wellKnown from '@/routes/well-known';
import passkey from '@/routes/passkey';

type RouteObj = {
    url?: (...args: any[]) => string;
    method?: string | string[];
    get?: (...args: any[]) => { url: string; method: string };
    head?: (...args: any[]) => { url: string; method: string };
    post?: (...args: any[]) => { url: string; method: string };
    put?: (...args: any[]) => { url: string; method: string };
    patch?: (...args: any[]) => { url: string; method: string };
    delete?: (...args: any[]) => { url: string; method: string };
    form?: Record<string, (...args: any[]) => { action: string; method: string }>;
    definition?: { methods?: string[]; url: string };
};

const DOT = '.';

const STATIC_ROUTES: Record<string, string> = {
    home: '/',
    welcome: '/',
    login: '/login',
    logout: '/logout',
    register: '/register',
    dashboard: '/dashboard',

    // Public Events (landing page detail + formulir pendaftaran publik)
    'events.show': '/events/{slug}',
    'events.register.form': '/events/{slug}/register',
    'events.register.submit': '/events/{slug}/register',

    // Admin dashboard
    'admin.dashboard': '/admin/dashboard',
    // Admin Events Resource
    'admin.events.index': '/admin/events',
    'admin.events.create': '/admin/events/create',
    'admin.events.store': '/admin/events',
    'admin.events.show': '/admin/events/{event}',
    'admin.events.edit': '/admin/events/{event}/edit',
    'admin.events.update': '/admin/events/{event}',
    'admin.events.destroy': '/admin/events/{event}',
    // Admin Race Classes Resource
    'admin.race-classes.index': '/admin/race-classes',
    'admin.race-classes.create': '/admin/race-classes/create',
    'admin.race-classes.store': '/admin/race-classes',
    'admin.race-classes.show': '/admin/race-classes/{race_class}',
    'admin.race-classes.edit': '/admin/race-classes/{race_class}/edit',
    'admin.race-classes.update': '/admin/race-classes/{race_class}',
    'admin.race-classes.destroy': '/admin/race-classes/{race_class}',
    // Admin Registrations Resource + custom update-status
    'admin.registrations.index': '/admin/registrations',
    'admin.registrations.show': '/admin/registrations/{registration}',
    'admin.registrations.edit': '/admin/registrations/{registration}/edit',
    'admin.registrations.update': '/admin/registrations/{registration}',
    'admin.registrations.update-status': '/admin/registrations/{registration}/update-status',
    // Admin Entry List (preview + export)
    'admin.entry-list.index': '/admin/entry-list',
    'admin.entry-list.export': '/admin/entry-list/export/{format}',
    // Admin SMTP Settings
    'admin.settings.smtp.index': '/admin/settings/smtp',
    'admin.settings.smtp.store': '/admin/settings/smtp',
    'admin.settings.smtp.test': '/admin/settings/smtp/test',

    // Staff routes (mirip admin untuk operasional lapangan)
    'staff.dashboard': '/staff/dashboard',
    'staff.events.index': '/staff/events',
    'staff.events.create': '/staff/events/create',
    'staff.events.store': '/staff/events',
    'staff.events.show': '/staff/events/{event}',
    'staff.events.edit': '/staff/events/{event}/edit',
    'staff.events.update': '/staff/events/{event}',
    'staff.events.destroy': '/staff/events/{event}',
    'staff.race-classes.index': '/staff/race-classes',
    'staff.race-classes.create': '/staff/race-classes/create',
    'staff.race-classes.store': '/staff/race-classes',
    'staff.race-classes.show': '/staff/race-classes/{race_class}',
    'staff.race-classes.edit': '/staff/race-classes/{race_class}/edit',
    'staff.race-classes.update': '/staff/race-classes/{race_class}',
    'staff.race-classes.destroy': '/staff/race-classes/{race_class}',
    'staff.registrations.index': '/staff/registrations',
    'staff.registrations.show': '/staff/registrations/{registration}',
    'staff.registrations.edit': '/staff/registrations/{registration}/edit',
    'staff.registrations.update': '/staff/registrations/{registration}',
    'staff.registrations.update-status': '/staff/registrations/{registration}/update-status',
    // Staff Entry List
    'staff.entry-list.index': '/staff/entry-list',
    'staff.entry-list.export': '/staff/entry-list/export/{format}',

    // Superadmin routes
    'superadmin.dashboard': '/superadmin/dashboard',

    // Rider routes
    'rider.dashboard': '/rider/dashboard',
    'rider.registrations.index': '/rider/registrations',
    'rider.registrations.show': '/rider/registrations/{id}',

    // Settings
    'settings.profile': '/settings/profile',
    'settings.security': '/settings/security',
    'settings.appearance': '/settings/appearance',
    'verification.notice': '/verify-email',
    'password.request': '/forgot-password',
    'password.reset': '/reset-password/{token}',
};

const routes: Record<string, any> = {
    ...routesNS,
    admin,
    superadmin,
    rider,
    staff,
    password,
    profile,
    verification,
    security,
    appearance,
    twoFactor,
    userPassword,
    storage,
    boost,
    wellKnown,
    passkey,
    // Override named login/register di routesNS dengan sub-module default export yang mengandung store
    login: Object.assign({}, (routesNS as any).login, login),
    logout: (routesNS as any).logout,
    register: Object.assign({}, (routesNS as any).register, register),
    home: (routesNS as any).home,
    dashboard: (routesNS as any).dashboard,
};

function resolveRoute(parts: string[]): RouteObj | undefined {
    let cur: any = routes;
    for (const part of parts) {
        if (cur && typeof cur === 'object' && part in cur) {
            cur = cur[part];
        } else {
            return undefined;
        }
    }
    return cur as RouteObj;
}

function interpolate(url: string, args: any): string {
    if (args === undefined || args === null) return url;

    let params: Record<string, any> = {};
    if (typeof args === 'string' || typeof args === 'number') {
        const m = url.match(/\{([^}]+)\}/);
        if (m) {
            params[m[1]] = args;
        } else {
            return `${url}/${args}`;
        }
    } else if (Array.isArray(args)) {
        const matches = url.match(/\{([^}]+)\}/g) ?? [];
        matches.forEach((ph, idx) => {
            if (args[idx] !== undefined) {
                const key = ph.slice(1, -1);
                params[key] = args[idx];
            }
        });
    } else if (typeof args === 'object') {
        if ('id' in args && !url.includes('{id}')) {
            const m = url.match(/\{([^}]+)\}/);
            if (m) {
                params[m[1]] = (args as any).id;
                Object.entries(args).forEach(([k, v]) => {
                    if (k !== 'id') params[k] = v;
                });
            } else {
                params = args as Record<string, any>;
            }
        } else {
            params = args as Record<string, any>;
        }
    }

    let result = url;
    Object.entries(params).forEach(([k, v]) => {
        const re = new RegExp(`\\{${k}\\}`, 'g');
        if (re.test(result)) {
            result = result.replace(re, String(v));
        }
    });
    result = result.replace(/\/+$/, '');
    return result;
}

export function route(name: string, args?: any, options?: any): string {
    if (!name) return '/';

    const parts = name.split(DOT);
    const routeObj = resolveRoute(parts);

    if (routeObj && typeof (routeObj as any).url === 'function') {
        try {
            const urlArgs: any[] = [];
            if (args !== undefined) urlArgs.push(args);
            if (options !== undefined) urlArgs.push(options);
            return (routeObj as any).url(...urlArgs);
        } catch (e) {
            if (routeObj.definition?.url) {
                return interpolate(routeObj.definition.url, args);
            }
        }
    }

    if (routeObj?.definition?.url) {
        return interpolate(routeObj.definition.url, args);
    }

    if (name in STATIC_ROUTES) {
        return interpolate(STATIC_ROUTES[name], args);
    }

    const fallback = name.startsWith('/') ? name : `/${name.replace(/\./g, '/')}`;
    console.warn(`[route] Route "${name}" tidak ditemukan. Fallback: ${fallback}`);
    return interpolate(fallback, args);
}

route.method = (name: string): string => {
    const parts = name.split(DOT);
    const routeObj = resolveRoute(parts);
    if (!routeObj) return 'get';
    if (routeObj.method) return String(routeObj.method).toLowerCase();
    if (routeObj.definition?.methods?.length) return routeObj.definition.methods[0].toLowerCase();
    return 'get';
};

export const to = route;

export default route;
