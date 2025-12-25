# Fluent Shipment - Project Index

## Overview
**Fluent Shipment** is a WordPress plugin built using the **WPFluent Framework** (v2.5.1). It provides a modern, Vue.js-based admin interface for managing shipments within WordPress.

### Key Technologies
- **Backend**: PHP 8+ with WPFluent Framework
- **Frontend**: Vue 3 + Element Plus + Vite
- **Architecture**: MVC pattern with REST API
- **Build Tool**: Vite 4.4.9
- **Package Manager**: Composer (PHP) + npm (JavaScript)

---

## Project Structure

### Root Files
- `plugin.php` - Main plugin entry point
- `composer.json` - PHP dependencies (WPFluent Framework)
- `package.json` - JavaScript dependencies (Vue, Vite, Element Plus)
- `vite.config.js` - Vite build configuration
- `README.md` - Project documentation

### Core Directories

#### `/app` - Application Core
Main application logic organized in MVC pattern:

**`App.php`** - Application facade extending WPFluent AppFacade

**`/Http`** - HTTP Layer
- **`/Controllers`**
  - `Controller.php` - Base controller class
  - `WelcomeController.php` - Welcome endpoint handler
  - `PostController.php` - Post CRUD operations
  - `UserController.php` - User CRUD operations
- **`/Middleware`**
  - `Can.php` - Permission checking middleware
- **`/Policies`**
  - `Policy.php` - Base policy class
  - `UserPolicy.php` - User authorization policies
- **`/Requests`**
  - `UserRequest.php` - Form request validation
- **`/Routes`**
  - `routes.php` - Main route registration
  - `api.php` - REST API endpoints
  - `vue.php` - Vue.js frontend routes/menu definition

**`/Hooks`** - WordPress Hooks
- `actions.php` - WordPress action hooks registration
- `filters.php` - WordPress filter hooks registration
- `includes.php` - Hook includes
- **`/Handlers`**
  - `ActivationHandler.php` - Plugin activation logic
  - `DeactivationHandler.php` - Plugin deactivation logic
  - `AdminMenuHandler.php` - Admin menu registration & asset enqueuing
  - `CPTHandler.php` - Custom Post Type registration
  - `ExceptionHandler.php` - Error handling
  - `HeartBeat.php` - WordPress heartbeat integration

**`/Models`** - Data Models
- `Model.php` - Base model extending WPFluent ORM
- `User.php` - User model with relationships

**`/Utils`** - Utility Classes
- **`/Auth`**
  - `Auth.php` - Authentication utilities
  - `Authorizer.php` - Authorization helpers
- **`/Enqueuer`**
  - `Enqueue.php` - Asset enqueuing facade
  - `Enqueuer.php` - Base enqueuer
  - `Vite.php` - Vite-specific asset handling
- **`/Support`** - Various support utilities (Ajax, Cache, Cookie, File, Hash, Locale, Mail, Media, Number, etc.)
- **`/Vue`**
  - `Router.php` - PHP-side Vue router builder
  - `Route.php` - Route definition class
  - `Group.php` - Route grouping
  - `Adder.php`, `Children.php`, `Submenu.php` - Route helpers

**`/Views`** - PHP Templates
- `admin/menu.php` - Admin menu page template

#### `/boot` - Bootstrap Files
- `app.php` - Application initialization & lifecycle hooks
- `bindings.php` - Service container bindings
- `globals.php` - Global helper functions

#### `/config` - Configuration
- `app.php` - Application configuration (name, slug, domain, REST namespace)
- `middleware.php` - Global middleware definitions
- `theme.php` - Theme configuration

#### `/resources` - Frontend Resources
- **`/admin`** - Vue.js Admin Application
  - `app.js` - Main application entry
  - `start.js` - Application bootstrap
  - **`/bootstrap`**
    - `index.js` - Bootstrap entry
    - `Application.js` - Vue app factory
    - `config.js` - Frontend config loader
    - `resources.js` - Resource management
    - `plugins/cssVar.js` - CSS variable plugin
    - `plugins/loadingOverlay.js` - Loading overlay plugin
  - **`/components`**
    - `Application.vue` - Main app layout component
    - `Menu/` - Navigation menu components
    - `Pagination.vue`, `CursorPagination.vue` - Pagination components
    - `Error.vue`, `NotFound.vue`, `Unauthorized.vue` - Error pages
    - `ThemeSwitcher.vue` - Theme switching component
  - **`/modules`**
    - `dashboard/index.vue` - Dashboard page
    - `shipments/index.vue` - Shipments management page
  - **`/router`**
    - `index.js` - Vue Router setup & route transformation
    - `routes.js` - Static route definitions
    - `middlewareHandler.js` - Route middleware execution
    - `middlewares.js` - Middleware definitions
  - **`/utils`**
    - `http/Rest.js` - REST API client (wraps wp.apiFetch)
    - `http/Ajax.js` - Ajax helper
    - `Storage.js` - Local storage utilities
    - `Cookie.js` - Cookie utilities
  - **`/composables`**
    - `useRoot.js` - Root composable
    - `useCssVar.js` - CSS variable composable
  - **`/mixins`**
    - `main.js`, `index.js` - Vue mixins
- **`/scss`** - Stylesheets
  - `admin.scss` - Main admin styles
  - `globals.scss` - Global styles
  - `vendor.scss` - Vendor styles
- **`/images`** - Image assets
  - `logo.svg`, `logo.png`, `favicon.png`
- **`/vite`** - Vite configuration helpers
  - `vite.js` - Vite helper script

#### `/assets` - Compiled Assets
- `admin/` - Compiled JavaScript & CSS
- `images/` - Static images
- `mix-manifest.json` - Vite manifest

#### `/database` - Database Layer
- `DBMigrator.php` - Database migration manager
- `Migrations/` - SQL migration files

#### `/dev` - Development Tools
- **`/cli`** - CLI commands
- **`/test`** - PHPUnit tests
- **`/factories`** - Model factories
- **`/seeders`** - Database seeders
- **`/workbench`** - Development workbench
- `phpcs.xml` - PHP CodeSniffer config
- `phpunit.xml.dist` - PHPUnit config
- `phpstan/` - PHPStan static analysis config

#### `/vendor` - Composer Dependencies
- `wpfluent/framework/` - WPFluent Framework core

#### `/language` - Translations
- Translation files for internationalization

---

## Architecture Overview

### Backend Architecture

#### Application Flow
1. **Entry Point**: `plugin.php` loads `boot/app.php` and Composer autoloader
2. **Bootstrap**: `boot/app.php` creates Application instance, registers hooks
3. **Hooks**: WordPress hooks registered in `app/Hooks/actions.php` and `filters.php`
4. **Routes**: REST API routes defined in `app/Http/Routes/api.php`
5. **Controllers**: Handle HTTP requests, validate, authorize, return responses
6. **Models**: Interact with WordPress database using ORM
7. **Policies**: Authorization logic for resource access

#### Key Components

**AdminMenuHandler**
- Registers WordPress admin menu
- Enqueues Vue.js application assets
- Localizes JavaScript variables
- Registers Vue routes from PHP

**Router System**
- PHP-side route definition (`app/Http/Routes/vue.php`)
- Converts to JSON for frontend
- Frontend transforms to Vue Router format

**Middleware**
- Global middleware (request/response processing)
- Route-specific middleware (auth, permissions)

**Validation**
- Request classes for form validation
- Custom validation rules support

### Frontend Architecture

#### Vue Application Structure
1. **Bootstrap**: `resources/admin/start.js` initializes Vue app
2. **Application**: `resources/admin/app.js` sets up app instance
3. **Router**: Dynamic route loading from server + static routes
4. **Components**: Reusable Vue components
5. **Modules**: Page-level components (dashboard, shipments)

#### Key Features

**Route System**
- Server-defined routes (PHP → JSON → Vue Router)
- Static routes (client-side)
- Route merging & deduplication
- Nested routes & submenus support
- Middleware support (before/after hooks)

**REST Client**
- Wraps WordPress `wp.apiFetch`
- Automatic nonce handling
- Response proxying with helper methods
- Middleware support

**State Management**
- Uses Vue 3 Composition API
- Composables for shared logic
- Local storage utilities

**Build System**
- Vite for fast HMR development
- SCSS compilation
- Asset optimization
- Live reload for PHP files

---

## Configuration

### Application Config (`config/app.php`)
```php
[
    'name' => 'Fluent Shipment',
    'slug' => 'fluentshipment',
    'text_domain' => 'fluentshipment',
    'hook_prefix' => 'fluentshipment',
    'rest_namespace' => 'fluentshipment',
    'rest_version' => 'v2',
    'env' => 'dev'
]
```

### Middleware Config (`config/middleware.php`)
- Global before/after middleware
- Request cleaning (removes `_locale`, `query_timestamp`)
- Cache control (LiteSpeed cache disabling)

### Vite Config (`vite.config.js`)
- Base path: `/wp-content/plugins/fluent-shipment/assets/`
- Inputs: `admin/app.js`, `admin/start.js`, `scss/admin.scss`
- Vue plugin with Element Plus auto-import
- Live reload for PHP files
- Development server on port 8880

---

## Routes & Endpoints

### REST API Routes (`app/Http/Routes/api.php`)
- `GET /welcome` → `WelcomeController@index`

### Vue Routes (`app/Http/Routes/vue.php`)
- `/` → Dashboard module (with auth middleware)
- `/shipments` → Shipments module

---

## Database

### Migration System
- SQL files in `database/Migrations/`
- Managed by `DBMigrator` class
- Uses WordPress `dbDelta` for table creation/updates
- Supports network-wide migrations

---

## Development Workflow

### PHP Development
1. Code in `/app` directory
2. Follow PSR-4 autoloading (`FluentShipment\App\`)
3. Use WPFluent Framework features
4. Run tests: `phpunit` (in `/dev` directory)

### Frontend Development
1. Run dev server: `npm run dev`
2. Edit Vue components in `/resources/admin`
3. Hot Module Replacement enabled
4. Build for production: `npm run build`

### Code Quality
- PHP CodeSniffer: `phpcs.xml`
- PHPStan: `dev/phpstan/config.neon`
- PHPUnit: `dev/phpunit.xml.dist`

---

## Key Classes & Their Responsibilities

### Backend

**Application Classes**
- `App\App` - Application facade
- `App\Hooks\Handlers\AdminMenuHandler` - Admin menu & assets
- `App\Hooks\Handlers\ActivationHandler` - Plugin activation
- `App\Http\Controllers\Controller` - Base controller
- `App\Models\Model` - Base model
- `App\Utils\Enqueuer\Enqueue` - Asset enqueuing
- `App\Utils\Vue\Router` - Vue route builder

### Frontend

**JavaScript Modules**
- `bootstrap/Application.js` - Vue app factory
- `router/index.js` - Router setup & transformation
- `utils/http/Rest.js` - REST API client
- `components/Application.vue` - Main layout
- `components/Menu/` - Navigation system

---

## Dependencies

### PHP (Composer)
- `wpfluent/framework: ^2.0` - Core framework

### JavaScript (npm)
- `vue: ^3.2.40` - Vue.js framework
- `vue-router: ^4.1.5` - Vue Router
- `element-plus: ^2.2.17` - UI component library
- `@vitejs/plugin-vue: ^4.3.4` - Vite Vue plugin
- `vite: ^4.4.9` - Build tool
- `sass: ^1.93.2` - SCSS compiler

---

## File Naming Conventions

### PHP
- Classes: PascalCase (e.g., `AdminMenuHandler.php`)
- Files match class names
- PSR-4 autoloading

### JavaScript/Vue
- Components: PascalCase (e.g., `Application.vue`)
- Utilities: camelCase (e.g., `Rest.js`)
- Modules: kebab-case directories (e.g., `modules/dashboard/`)

---

## Plugin Lifecycle

1. **Activation**: `ActivationHandler::handle()` runs migrations
2. **Load**: `plugins_loaded` action fires `fluentshipment_loaded`
3. **Admin Menu**: `AdminMenuHandler` registers menu on `admin_menu`
4. **Asset Enqueue**: Admin page loads Vue app via `Enqueue` utility
5. **Frontend Init**: Vue app mounts, router loads routes from server
6. **Deactivation**: `DeactivationHandler::handle()` cleanup

---

## Notes

- Plugin uses WPFluent Framework v2.5.1
- Framework version: 2.11.6
- Development environment configured
- REST API namespace: `fluentshipment/v2`
- Admin interface uses Vue 3 + Element Plus
- Build system: Vite with HMR support
- Code follows WordPress coding standards

---

## Current State

### Implemented
- ✅ Plugin structure & bootstrap
- ✅ Admin menu registration
- ✅ Vue.js application setup
- ✅ Router system (PHP → Vue)
- ✅ Dashboard module
- ✅ Shipments module (basic)
- ✅ REST API endpoint structure
- ✅ Asset enqueuing system

### Example/Template Code
- `PostController` - Example CRUD operations
- `UserController` - Example user management
- `UserPolicy` - Example authorization
- `UserRequest` - Example form validation

---

*Last indexed: $(date)*

