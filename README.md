# Laravel 12 + Vue.js 3 Application

<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

A modern web application built with Laravel 12, Vue.js 3, Inertia.js, and Vite, featuring authentication scaffolding with Laravel Breeze.

## 🚀 Features

- **Laravel 12** - Latest version with modern features
- **Vue.js 3** - Composition API and modern reactivity
- **Inertia.js** - SPA-like experience without API complexity
- **Laravel Breeze** - Authentication scaffolding with Vue.js frontend
- **Vite** - Fast build tool with hot module replacement
- **Tailwind CSS** - Utility-first CSS framework
- **Modern Development Workflow** - Hot reload and fast builds

## 📋 Requirements

- PHP 8.2 or higher
- Composer
- Node.js 18+ and npm
- SQLite (or other database)

## 🛠️ Installation & Setup

### 1. Install Dependencies

```bash
# Install PHP dependencies
composer install

# Install Node.js dependencies
npm install
```

### 2. Environment Configuration

```bash
# Copy environment file (if not already done)
cp .env.example .env

# Generate application key (if not already done)
php artisan key:generate

# Configure your database in .env file
# For SQLite (default):
DB_CONNECTION=sqlite
DB_DATABASE=/path/to/database/database.sqlite
```

### 3. Database Setup

```bash
# Run migrations (optional - SQLite driver may not be available)
php artisan migrate
```

### 4. Build Assets

```bash
# Build for development
npm run dev

# Or build for production
npm run build
```

## 🏃‍♂️ Running the Application

### Development Mode

You need to run both the Laravel server and Vite development server:

```bash
# Terminal 1: Start Laravel development server
php artisan serve

# Terminal 2: Start Vite development server (for hot reload)
npm run dev
```

The application will be available at:
- **Laravel App**: http://localhost:8000
- **Vite Dev Server**: http://localhost:5173

## 🔗 Available Routes

- `/` - Redirects to dashboard (if authenticated) or login (if guest)
- `/login` - Login page (Vue.js)
- `/dashboard` - Dashboard (Vue.js, requires authentication)
- `/profile` - User profile management (Vue.js, requires authentication)

## 🎯 Key Technologies

### Backend
- **Laravel 12**: Modern PHP framework with latest features
- **Laravel Breeze**: Lightweight authentication scaffolding
- **Inertia.js**: Connects Laravel backend with Vue.js frontend

### Frontend
- **Vue.js 3**: Progressive JavaScript framework with Composition API
- **Tailwind CSS**: Utility-first CSS framework
- **Vite**: Next-generation frontend build tool

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
