<?php

use Dedoc\Scramble\Http\Middleware\RestrictedDocsAccess;

return [
    /*
     * Your API path. By default, all routes starting with this path will be added to the docs.
     * If you need to change this behavior, you can add your custom routes resolver using `Scramble::routes()`.
     */
    'api_path' => 'api',

    /*
     * Your API domain. By default, app domain is used. This is also a part of the default API routes
     * matcher, so when implementing your own, make sure you use this config if needed.
     */
    'api_domain' => null,

    /*
     * The path where your OpenAPI specification will be exported.
     */
    'export_path' => 'api.json',

    'info' => [
        /*
         * API version.
         */
        'version' => env('API_VERSION', '1.0.0'),

        /*
         * Description rendered on the home page of the API documentation (`/docs/api`).
         */
        'description' => '🚀 **API RSISTANC** - Sistema de Resistencia y Análisis de Capacidades

API RESTful completa para gestión de usuarios, perfiles, contactos y auditoría de accesos.

**Características principales:**
- 👤 Gestión completa de usuarios y perfiles
- 📞 Sistema de contactos múltiples
- 🔐 Auditoría de inicios de sesión y seguridad
- 🌐 Integración con redes sociales (Google/Facebook)
- 📊 Filtros avanzados y paginación
- 🔍 Búsqueda por múltiples criterios

**Tecnologías:**
- Laravel 12.0+ con PHP 8.3+
- Laravel API Resources
- Arquitectura limpia con principios SOLID
- Base de datos relacional optimizada

**Modelos del sistema:**
- **Usuario** - Usuario principal del sistema
- **Perfil de Usuario** - Perfil detallado (nombre, edad, género, talla)
- **Contacto de Usuario** - Contactos múltiples (teléfono, dirección)
- **Cuenta Social** - Cuentas sociales vinculadas (Google/Facebook)
- **Auditoría de Acceso** - Auditoría de intentos de inicio de sesión',
    ],

    /*
     * Customize Stoplight Elements UI
     */
    'ui' => [
        /*
         * Define the title of the documentation's website. App name is used when this config is `null`.
         */
        'title' => 'Documentación API RSISTANC',

        /*
         * Define the theme of the documentation. Available options are `light` and `dark`.
         */
        'theme' => 'light',

        /*
         * Hide the `Try It` feature. Enabled by default.
         */
        'hide_try_it' => false,

        /*
         * Hide the schemas in the Table of Contents. Enabled by default.
         */
        'hide_schemas' => false,

        /*
         * URL to an image that displays as a small square logo next to the title, above the table of contents.
         */
        'logo' => '',

        /*
         * Use to fetch the credential policy for the Try It feature. Options are: omit, include (default), and same-origin
         */
        'try_it_credentials_policy' => 'include',

        /*
         * There are three layouts for Elements:
         * - sidebar - (Elements default) Three-column design with a sidebar that can be resized.
         * - responsive - Like sidebar, except at small screen sizes it collapses the sidebar into a drawer that can be toggled open.
         * - stacked - Everything in a single column, making integrations with existing websites that have their own sidebar or other columns already.
         */
        'layout' => 'responsive',
    ],

    /*
     * The list of servers of the API. By default, when `null`, server URL will be created from
     * `scramble.api_path` and `scramble.api_domain` config variables. When providing an array, you
     * will need to specify the local server URL manually (if needed).
     *
     * Example of non-default config (final URLs are generated using Laravel `url` helper):
     *
     * ```php
     * 'servers' => [
     *     'Live' => 'api',
     *     'Prod' => 'https://scramble.dedoc.co/api',
     * ],
     * ```
     */
    'servers' => [
        'Desarrollo Local' => 'http://backend-resistanc.test/api',
        'Producción' => env('APP_URL', 'http://backend-resistanc.test/') . '/api',
    ],

    /**
     * Determines how Scramble stores the descriptions of enum cases.
     * Available options:
     * - 'description' – Case descriptions are stored as the enum schema's description using table formatting.
     * - 'extension' – Case descriptions are stored in the `x-enumDescriptions` enum schema extension.
     *
     *    @see https://redocly.com/docs-legacy/api-reference-docs/specification-extensions/x-enum-descriptions
     * - false - Case descriptions are ignored.
     */
    'enum_cases_description_strategy' => 'description',

    'middleware' => [
        'web',
        RestrictedDocsAccess::class,
    ],

    'extensions' => [],

    /*
    |--------------------------------------------------------------------------
    | Modelos para esquemas
    |--------------------------------------------------------------------------
    |
    | Lista de modelos que deben incluirse en los esquemas de la documentación.
    | Esto permite que Scramble genere automáticamente esquemas para estos modelos.
    |
    */
    'models' => [
        \App\Models\User::class,
        \App\Models\UserProfile::class,
        \App\Models\UserContact::class,
        \App\Models\SocialAccount::class,
        \App\Models\LoginAudit::class,
    ],
];
