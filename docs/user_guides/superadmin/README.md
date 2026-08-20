# Super Admin Panel — User & Developer Guide

This document is a combined user guide and technical reference for the Super
Admin area of the application. It covers how Super Admins log in, what they
can do, the frontend architecture that powers the panel, and the conventions
a developer should follow when adding new Super Admin modules.

---

## 1. Access & Authentication

### Who is a Super Admin?

A user is considered a Super Admin **only** when their `users.group_id` value
equals `1`. Every gate in the panel relies on this single check; there are no
roles or permission flags layered on top of it.

### Middleware — `EnsureSuperAdmin`

All routes under the `/super-admin` prefix are wrapped by the
`super-admin` middleware alias, which resolves to
`App\Http\Middleware\EnsureSuperAdmin`. The middleware:

1. Redirects unauthenticated requests to the login page.
2. Returns `403 Forbidden` (JSON for API requests, otherwise an `abort(403)`)
   for any authenticated user whose `group_id` is not `1`.

The alias is registered in `bootstrap/app.php` under `withMiddleware`.

### Post-login redirection

`App\Http\Controllers\Auth\AuthenticatedSessionController::store()` inspects
the authenticated user immediately after a successful login. When
`group_id === 1`, the controller:

1. Calls `$request->session()->forget('url.intended')` so a stale intended
   URL (for example `/dashboard` cached by the `auth` middleware before
   login) cannot win over the explicit redirect.
2. Returns `redirect()->route('super-admin.dashboard')`.

For all other users the original `redirect()->intended('/admin')` behavior is
preserved.

---

## 2. Frontend Architecture

### Layout — `resources/js/Layouts/SuperAdminLayout.vue`

The layout is ported from the open-source
[TailAdmin Vue Tailwind Dashboard](https://github.com/TailAdmin/vue-tailwind-admin-dashboard)
template. It provides:

- A collapsible left sidebar with navigation items (Dashboard, Quality
  Evaluations, …) that highlight via `route().current('super-admin.*')`.
- A sticky top header exposing a `#header` slot for the page title and a
  user dropdown with a "Sign out" link.
- A mobile-friendly overlay sidebar driven by a `sidebarOpen` ref.

Every Super Admin page should be wrapped in this layout — never in
`AuthenticatedLayout` — so the chrome stays consistent.

### Shared components

| Component                                  | Purpose                                                               |
| ------------------------------------------ | --------------------------------------------------------------------- |
| `resources/js/Components/QualityFilter.vue` | Country / Brand / Branch + date-range filter used across the panel. |

#### `QualityFilter.vue`

- **Country / Brand / Branch dropdowns** are powered by
  [`SlimSelect`](https://slimselectjs.com/), which provides searchable,
  styled `<select>` widgets. Instances are rebuilt when their dependent
  options change (e.g. brands narrow when a country is picked).
- **Date range** is a single `<input>` bound to a
  [`flatpickr`](https://flatpickr.js.org/) instance configured with
  `mode: 'range'` and `dateFormat: 'Y-m-d'`. The component still emits the
  separate `start_date` and `end_date` keys that the backend expects, so it
  is a drop-in replacement for the previous two `<input type="date">`
  fields.
- The component emits `filter-changed` with the merged filter payload.
  Pages should debounce/route on this event using
  `router.get(..., { preserveState: true, replace: true })`.

---

## 3. Global Filtering & Reporting

### Hierarchical filtering — Country › Brand › Branch

Reports in the panel filter against the same hierarchy:

```
Country  ──┐
Brand    ──┼──►  Branch  ──►  QualityEvaluation
Branch   ──┘
```

Selecting a Country narrows the eligible Brands and Branches; selecting a
Brand further narrows Branches. The backend resolves these higher-level
selections back down to a set of `branch_id`s before applying them to the
evaluation query.

### `applyFilters()` in `SuperAdmin\QualityEvaluationController`

`applyFilters(Builder $query, Request $request)` is a private helper used by
both `index()` and `export()`. It accepts the following keys:

| Key          | Effect on the `quality_evaluations` query                                  |
| ------------ | -------------------------------------------------------------------------- |
| `country_id` | `whereIn('branch_id', Branch::where('country_id', …)->pluck('id'))`        |
| `brand_id`   | `whereIn('branch_id', Branch::where('brand_id', …)->pluck('id'))`          |
| `branch_id`  | `where('branch_id', …)`                                                    |
| `status`     | `where('status', …)` — `completed`, `draft`, `pending`                     |
| `type`       | `where('type', …)` — `checklist` or `regular`                              |
| `start_date` | `whereDate('created_at', '>=', …)`                                         |
| `end_date`   | `whereDate('created_at', '<=', …)`                                         |

Pagination is configured with `per_page` (clamped 5–100, default 15) and
preserves the filter querystring via `withQueryString()`.

### "Location" column

The Quality Evaluations table renders a single **Location** column shaped as
`Country › Brand › Branch`. The label is built on the frontend from the
eager-loaded relationships:

```js
const country = branch.country?.localized_name || branch.country?.name
const brand   = branch.brand?.localized_name   || branch.brand?.name
const branchName = branch.localized_name        || branch.name
return [country, brand, branchName].filter(Boolean).join(' › ')
```

`localized_name` is a model accessor that returns the Arabic value when
`app()->getLocale() === 'ar'` and falls back to English otherwise. It is
important to know which underlying column each model uses:

| Model       | English column | Arabic column |
| ----------- | -------------- | ------------- |
| `Country`   | `name`         | **`name2`**   |
| `Brand`     | `name`         | **`name2`**   |
| `Branch`    | `name`         | `name_ar`     |

For Countries and Brands the legacy `sma_*` schema stores the Arabic name in
`name2` (not `name_ar`); the model accessors handle this discrepancy so
consumers only need to read `localized_name`. When eager-loading these
relations through `with()`, always select `name2` (not `name_ar`) for
country/brand to avoid empty Arabic labels.


---

## 4. Features & Actions

### Quality Evaluations management table

Route: `super-admin.quality-evaluations.index` →
`/super-admin/quality-evaluations`.

The table lists every `QualityEvaluation` in the system. Columns:

| Column       | Notes                                                                      |
| ------------ | -------------------------------------------------------------------------- |
| Title / ID   | Title with a red dot when `warning_flag === true`; `#id` shown beneath.    |
| Evaluator    | Eager-loaded `user` (full name, falling back to username).                 |
| Location     | `Country › Brand › Branch` using `localized_name` (see §3).                |
| Type         | Pill rendering `checklist` or `regular`.                                   |
| Status       | Pill rendering `completed`, `draft`, or `pending`.                         |
| Total score  | `total_score`; for `checklist` evaluations also shows ` / max_score`.      |
| Date         | `created_at` rendered with `toLocaleString()`.                             |
| Actions      | View link → `quality-evaluations.show`; PDF link (see below).              |

Pagination uses Laravel's `links` payload and is dispatched through
`router.get(url, {}, { preserveScroll: true, preserveState: true })` so that
filter and scroll state survive page changes.

### Excel / CSV export

Route: `super-admin.quality-evaluations.export` →
`/super-admin/quality-evaluations/export`.

The "Export to Excel" button on the index page links to this route with the
current filter state appended as querystring parameters. The controller
re-uses `applyFilters()`, so the export honors exactly what the user is
viewing.

Implementation notes:

- The response is a Symfony `StreamedResponse` returned via
  `response()->stream(...)`. Rows are written to `php://output` directly,
  so the export is memory-safe — the query is consumed with `chunk(500)`.
- The first bytes of the stream are the UTF-8 byte-order mark
  `\xEF\xBB\xBF`. Excel uses the BOM to detect UTF-8 encoding; without it
  Arabic columns (Country, Brand, Branch, Title) render as mojibake when
  the file is opened on Windows.
- The `Content-Type` is `text/csv; charset=UTF-8` and the filename is
  `quality-evaluations-YYYY-MM-DD_HHMMSS.csv`.
- Columns: `ID, Title, Type, Status, Country, Brand, Branch, Evaluator,
  Total Score, Warning Flag, Created At`.

If a real `.xlsx` is later required, swap the `stream()` call for a
`maatwebsite/excel` export — `applyFilters()` will not need to change.

### PDF download

Each row's "PDF" action links to the existing
`quality-evaluations.download-pdf` route
(`App\Http\Controllers\QualityEvaluationController@downloadPdf`). That
controller:

1. Checks `auth()->user()->group_id === 1` and lets Super Admins download
   any evaluation (the same check that gates editing).
2. Generates the PDF on demand through `QualityEvaluationPdfService` if the
   stored file is missing — so the link is always usable, even for old
   evaluations.

The Super Admin panel does not need its own PDF route; reuse this one.

---

## 5. Developer Guidelines — Adding a New Super Admin Module

When adding a new module (e.g. "Audit Logs"), follow this checklist so the
new feature stays consistent with the rest of the panel.

### 5.1 Backend

1. **Routes** — register inside the existing group in `routes/web.php`:

   ```php
   Route::middleware(['auth', 'super-admin'])
       ->prefix('super-admin')
       ->name('super-admin.')
       ->group(function () {
           Route::get('audit-logs', [AuditLogController::class, 'index'])
               ->name('audit-logs.index');
       });
   ```

   Routes inherit the `super-admin` middleware automatically; do **not**
   re-apply it on individual routes.

2. **Controller** — place it under
   `app/Http/Controllers/SuperAdmin/{Name}Controller.php` and namespace it
   `App\Http\Controllers\SuperAdmin`. Import it into `routes/web.php` with
   an alias to avoid clashing with the non-admin counterpart, e.g.
   `use App\Http\Controllers\SuperAdmin\AuditLogController as SuperAdminAuditLogController;`.

3. **Filtering** — if the page consumes the Country/Brand/Branch hierarchy,
   model the controller after `SuperAdmin\QualityEvaluationController`:

   - Extract a private `applyFilters(Builder $query, Request $request)`
     helper so `index()` and any export/report endpoint share one
     implementation.
   - Eager-load `branch.country:id,name,name2` and
     `branch.brand:id,name,name2` (note: `name2`, not `name_ar`).
   - Append `localized_name` on the eager-loaded models before sending the
     payload to Inertia.

4. **Permissions** — never duplicate `EnsureSuperAdmin` logic inside the
   controller. The middleware is the single source of truth.

### 5.2 Frontend

1. **Page** — create the Inertia page at
   `resources/js/Pages/SuperAdmin/{Module}/Index.vue` and wrap the template
   in `<SuperAdminLayout>`:

   ```vue
   <template>
     <Head title="Audit Logs - Super Admin" />
     <SuperAdminLayout>
       <template #header>Audit Logs</template>
       <!-- page content -->
     </SuperAdminLayout>
   </template>
   ```

2. **Filters** — reuse `@/Components/QualityFilter.vue` whenever the module
   needs Country / Brand / Branch + date range. Bind it via
   `:initial-filters="filters"` and react to its `@filter-changed` event.

3. **Tables & buttons** — match the styling already used on the Quality
   Evaluations page (rounded `2xl` cards, `bg-gray-50` headers, rose-tinted
   PDF buttons, emerald-tinted export buttons) so the panel keeps a single
   visual language.

4. **Sidebar link** — open `resources/js/Layouts/SuperAdminLayout.vue` and
   add an entry to the `navItems` (or equivalent) array that drives the
   sidebar. Use `route('super-admin.audit-logs.index')` as the `href` and
   set the active class with
   `route().current('super-admin.audit-logs.*')` so nested pages also
   highlight the link.

5. **Build** — run `npm run build` (or `npm run dev` while iterating) and
   `php artisan route:list --name=super-admin` to confirm the new module is
   wired up correctly before opening a PR.

### 5.3 Testing checklist

When you add a feature, also add (or extend) a feature test that asserts:

- A non-Super Admin (`group_id !== 1`) hitting any new `/super-admin/*`
  route receives `403`.
- A Super Admin sees the expected Inertia component and prop shape.
- Filters narrow the result set as documented (one assertion per filter
  key is usually sufficient).
- Any export endpoint streams a UTF-8 CSV starting with the `\xEF\xBB\xBF`
  BOM and the expected header row.


## 6. Visual Design / Color System

The Super Admin panel intentionally uses a tiered, low-saturation palette so
that data tables stay readable while still giving users clear visual anchors
(page headers, filter regions, modals). Use the tokens below for any new
Super Admin page or component — do not introduce new accent colors without
updating this section first.

### 6.1 Surface tiers

The interface is built from three concentric "tiers" of background surface.
Pick the tier based on the role of the element, not on personal preference:

| Tier | Tailwind classes                                  | Used for                                                                                       |
| ---- | ------------------------------------------------- | ---------------------------------------------------------------------------------------------- |
| 1    | `bg-indigo-50` / `from-indigo-50 to-blue-50`      | Primary page accents: modal headers, filter wrapper, tab strip, "Recent flagged" card header. |
| 2    | `bg-slate-100`                                    | Data-table `<thead>` rows and any heading row that introduces structured data.                |
| 3    | `bg-slate-50`                                     | Footer / pagination strips inside cards and modals.                                            |
| 4    | `bg-white`                                        | The content area itself — table bodies, card bodies, modal bodies.                            |

### 6.2 Concrete tokens

These are the exact class strings used across the existing pages. Copy them
verbatim when extending the panel — do not change shades on a whim.

**Modal header (gradient, indigo)**

```html
<div class="flex shrink-0 items-start justify-between gap-4 border-b border-indigo-100
            bg-gradient-to-r from-indigo-50 to-blue-50 px-6 py-4">
    <h2 class="text-lg font-semibold text-indigo-900">…</h2>
    <p  class="text-xs text-indigo-700/80">…</p>
    <button class="rounded-md p-1 text-indigo-600 hover:bg-white/70 hover:text-indigo-800">…</button>
</div>
```

**Modal / card footer (slate)**

```html
<div class="flex shrink-0 flex-wrap items-center justify-between gap-3
            border-t border-slate-200 bg-slate-50 px-6 py-3">
    …
</div>
```

**Data-table head**

```html
<thead class="border-b border-slate-200 bg-slate-100 text-left text-xs
              font-semibold uppercase tracking-wider text-slate-700">
    <tr>
        <th class="px-5 py-3">Title</th>
        …
    </tr>
</thead>
<tbody class="divide-y divide-gray-100 text-gray-700">
    <tr class="transition-colors hover:bg-indigo-50/40">…</tr>
</tbody>
```

Note: drop the per-`<th>` `font-medium` utility — the boldness now lives on
the `<thead>` itself via `font-semibold`. Row hover is `hover:bg-indigo-50/40`
on every Super Admin table for consistency.

**Pagination buttons**

```html
<button
    class="rounded-md border px-3 py-1.5 text-xs font-medium transition-colors"
    :class="[
        link.active
            ? 'border-indigo-600 bg-indigo-600 text-white shadow-sm'
            : 'border-gray-200 bg-white text-gray-700 hover:border-indigo-200 hover:bg-indigo-50',
        !link.url ? 'opacity-40 cursor-not-allowed' : '',
    ]" />
```

### 6.3 Filter-highlight wrapper

Every Super Admin report page wraps `<QualityFilter>` in a
`.sa-filter-highlight` div so the filter region reads as a distinct,
actionable zone. The class is defined once in `resources/css/app.css` and
must not be re-implemented per page.

```html
<div class="sa-filter-highlight mb-4">
    <QualityFilter
        :countries="countries"
        :brands="brands"
        :branches="branches"
        :initial-filters="filters"
        @filter-changed="handleFilterChanged"
    />
</div>
```

For pages that need an even stronger callout (e.g. when the filter drives a
critical workflow), add the modifier class:

```html
<div class="sa-filter-highlight sa-filter-highlight--accent mb-4"> … </div>
```

The wrapper is intentionally direction-agnostic so it works correctly under
both `dir="ltr"` and `dir="rtl"`.

### 6.4 Status / semantic accents

Reserve saturated colors strictly for semantic meaning. Do not use them as
decorative accents.

| Meaning   | Background    | Text / Ring                             |
| --------- | ------------- | --------------------------------------- |
| Success   | `bg-emerald-50` | `text-emerald-700`                     |
| Warning   | `bg-amber-50`   | `text-amber-700`                       |
| Danger    | `bg-red-100`    | `text-red-700`, `ring-1 ring-red-200`  |
| Neutral   | `bg-gray-100`   | `text-gray-700`                        |
| Info / CTA | `bg-indigo-600` | `text-white` (primary buttons only)    |

### 6.5 Checklist when building a new Super Admin page

1. Wrap `<QualityFilter>` in `<div class="sa-filter-highlight mb-4">`.
2. Wrap the data table in `overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm`.
3. Use the table-head token from §6.2 (no `font-medium` per `<th>`).
4. Use `hover:bg-indigo-50/40` on every `<tbody>` row.
5. Use the slate footer token from §6.2 for the pagination strip.
6. Modals: indigo gradient header (§6.2) + slate footer (§6.2) + white body.
7. Reserve the colors in §6.4 for status/semantic UI only.
