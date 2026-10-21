<?php

declare(strict_types=1);

/**
 * Spec drift checker.
 *
 * Verifies that the running code still honours docs/SPECIFICATION.md.
 * Run it from inside the application container:
 *
 *     docker exec signic-app-1 php /app/scripts/check-spec-drift.php
 *
 * Exit code 0 = no drift. Exit code 1 = drift found.
 */

$basePath = dirname(__DIR__);

require $basePath . '/vendor/autoload.php';

$app = require $basePath . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$failures = [];
$checks = 0;

/**
 * @param  array<int, string>  $failures
 */
$fail = static function (string $message) use (&$failures): void {
    $failures[] = $message;
};

// ---------------------------------------------------------------------------
// Collect the real route table.
// ---------------------------------------------------------------------------

$routes = [];

foreach (Illuminate\Support\Facades\Route::getRoutes() as $route) {
    foreach ($route->methods() as $method) {
        if ($method === 'HEAD') {
            continue;
        }

        $routes[] = [
            'method' => $method,
            'uri' => trim($route->uri(), '/'),
        ];
    }
}

$routeIndex = [];

foreach ($routes as $route) {
    $routeIndex[strtoupper($route['method']) . ' ' . $route['uri']] = true;
}

/**
 * Does a concrete request path match a registered route?
 */
$matchesRoute = static function (string $method, string $path) use ($routes): ?string {
    $path = trim($path, '/');

    foreach ($routes as $route) {
        if ($route['method'] !== strtoupper($method)) {
            continue;
        }

        $pattern = '#^' . preg_replace('#\{[^}]+\}#', '[^/]+', $route['uri']) . '$#';

        if (preg_match($pattern, $path) === 1) {
            return $route['uri'];
        }
    }

    return null;
};

/**
 * Suggest the route a broken call was probably meant to hit.
 */
$suggest = static function (string $method, string $path) use ($routes): ?string {
    $path = trim($path, '/');
    $segments = explode('/', $path);
    $leaf = end($segments);

    $candidates = [];

    foreach ($routes as $route) {
        if ($route['method'] !== strtoupper($method)) {
            continue;
        }

        $routeSegments = explode('/', $route['uri']);
        $routeLeaf = end($routeSegments);

        $score = 0;

        if ($routeLeaf === $leaf) {
            $score += 3;
        }

        $score += count(array_intersect($segments, $routeSegments));

        if ($score > 0) {
            $candidates[$route['uri']] = $score;
        }
    }

    if ($candidates === []) {
        return null;
    }

    arsort($candidates);

    return (string) array_key_first($candidates);
};

// ---------------------------------------------------------------------------
// Check 1 — every API call in the frontend resolves to a real route.
// ---------------------------------------------------------------------------

$frontendFiles = [];

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($basePath . '/resources/js', FilesystemIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
    if ($file->isFile() && in_array($file->getExtension(), ['vue', 'js'], true)) {
        $frontendFiles[] = $file->getPathname();
    }
}

sort($frontendFiles);

$apiBase = '/';

foreach ($frontendFiles as $file) {
    $contents = (string) file_get_contents($file);
    $relative = 'app/resources/js/' . str_replace($basePath . '/resources/js/', '', $file);

    preg_match_all(
        '/axios\.(get|post|put|patch|delete)\(\s*([`\'"])(.*?)\2/s',
        $contents,
        $matches,
        PREG_SET_ORDER
    );

    foreach ($matches as $match) {
        $method = strtoupper($match[1]);
        $raw = $match[3];

        // Resolve the ${API_BASE} template used by useAuth.js.
        $path = str_replace('${API_BASE}', $apiBase, $raw);
        $path = str_replace('${', '', $path);
        $path = str_replace('}', '', $path);

        if (! str_starts_with($path, '/')) {
            $path = '/' . $path;
        }

        // Skip calls that interpolate a runtime value.
        if (str_contains($path, '$') || str_contains($path, '{')) {
            continue;
        }

        $checks++;

        $matched = $matchesRoute($method, $path);

        if ($matched === null) {
            $hint = $suggest($method, $path);

            $fail(sprintf(
                'Frontend calls a route that does not exist: %s %s   [%s]%s',
                $method,
                $path,
                $relative,
                $hint !== null ? "\n        did you mean: {$method} /{$hint}?" : ''
            ));

            continue;
        }

        // The route exists but the frontend pinned a concrete id into the
        // path instead of reading it from the context.
        if (str_contains($matched, '{') && preg_match('#/\d+/#', $path) === 1) {
            $fail(sprintf(
                'Frontend hardcodes a record id in the URL: %s %s   [%s]%s',
                $method,
                $path,
                $relative,
                "\n        the route is /{$matched} — take the id from the context instead"
            ));
        }
    }
}

// ---------------------------------------------------------------------------
// Check 2 — every Inertia::render() component has a matching Vue file.
// ---------------------------------------------------------------------------

$pagesDir = $basePath . '/resources/js/Pages';

$webRoutes = (string) file_get_contents($basePath . '/routes/web.php');

preg_match_all("/Inertia::render\(\s*'([^']+)'/", $webRoutes, $renders);

foreach ($renders[1] as $component) {
    $checks++;

    $expected = $pagesDir . '/' . $component . '.vue';

    if (is_file($expected)) {
        continue;
    }

    $alternatives = glob($pagesDir . '/' . $component . '*Page.vue') ?: [];

    $fail(sprintf(
        'Inertia component "%s" has no matching page file   [app/routes/web.php]%s',
        $component,
        $alternatives !== []
            ? "\n        the file is called: " . basename($alternatives[0])
            : ''
    ));
}

// ---------------------------------------------------------------------------
// Check 3 — the blade layout declares exactly one mount point.
// ---------------------------------------------------------------------------

foreach (['app.blade.php', 'welcome.blade.php'] as $layout) {
    $path = $basePath . '/resources/views/' . $layout;

    if (! is_file($path)) {
        continue;
    }

    $checks++;

    $contents = (string) file_get_contents($path);
    // The @inertia directive emits its own root mount point, so a literal
    // id="app" next to it is a duplicate. Total mount points = literal count
    // + one for @inertia when present.
    $count = preg_match_all('/id="app"/', $contents);
    if (str_contains($contents, '@inertia')) {
        $count++;
    }

    if ($count === 1) {
        continue;
    }

    $fail(sprintf(
        'Layout "%s" declares %d elements with id="app" (expected exactly 1)   [app/resources/views/%s]%s',
        $layout,
        $count,
        $layout,
        $count > 1 ? "\n        the @inertia directive already emits the mount point" : ''
    ));
}

// ---------------------------------------------------------------------------
// Check 4 — no dead vue-router: no <router-link> without a router plugin.
// ---------------------------------------------------------------------------

$appJs = (string) file_get_contents($basePath . '/resources/js/app.js');
$routerRegistered = str_contains($appJs, 'router/index.js') || str_contains($appJs, "use(router)");

$routerLinkUsages = [];

foreach ($frontendFiles as $file) {
    $contents = (string) file_get_contents($file);

    if (preg_match_all('/<router-link/', $contents, $found) > 0) {
        $routerLinkUsages[] = 'app/resources/js/' . str_replace($basePath . '/resources/js/', '', $file);
    }
}

$checks++;

if ($routerLinkUsages !== [] && ! $routerRegistered) {
    $fail(sprintf(
        "Frontend uses <router-link> but no router is registered   [app/resources/js/app.js]\n        used in: %s\n        fix: use Inertia's <Link href=…> instead",
        implode(', ', $routerLinkUsages)
    ));
}

$checks++;

if (is_file($basePath . '/resources/js/router/index.js') && ! $routerRegistered) {
    $fail('resources/js/router/index.js exists but is never imported   [app/resources/js/router/index.js]'
        . "\n        fix: delete it, or register it in app.js");
}

// ---------------------------------------------------------------------------
// Check 5 — every page referenced by the dead router exists.
// ---------------------------------------------------------------------------

$routerFile = $basePath . '/resources/js/router/index.js';

if (is_file($routerFile)) {
    $routerSource = (string) file_get_contents($routerFile);

    preg_match_all("/import\('(\.\.\/Pages\/[^']+\.vue)'\)/", $routerSource, $routerImports);

    foreach ($routerImports[1] as $import) {
        $checks++;

        $target = realpath($basePath . '/resources/js/Pages/' . basename($import));

        if ($target !== false) {
            continue;
        }

        $fail(sprintf(
            'The dead router imports a page that does not exist: %s   [app/resources/js/router/index.js]',
            basename($import)
        ));
    }
}

// ---------------------------------------------------------------------------
// Check 6 — frontend enum values match the backend enum values.
// ---------------------------------------------------------------------------

$enumFiles = [
    'ticket_type' => $basePath . '/app/Modules/Registration/Enums/TicketType.php',
    'attendee_status' => $basePath . '/app/Modules/Registration/Enums/AttendeeStatus.php',
    'event_status' => $basePath . '/app/Modules/Registration/Enums/EventStatus.php',
    'interest_level' => $basePath . '/app/Modules/Exhibition/Enums/InterestLevel.php',
];

$backendValues = [];

foreach ($enumFiles as $key => $path) {
    if (! is_file($path)) {
        continue;
    }

    preg_match_all("/case \w+ = '([^']+)'/", (string) file_get_contents($path), $cases);

    $backendValues[$key] = $cases[1];
}

foreach ($frontendFiles as $file) {
    $contents = (string) file_get_contents($file);
    $relative = 'app/resources/js/' . str_replace($basePath . '/resources/js/', '', $file);

    // <option value="general">General</option> style selects.
    preg_match_all('/<option\s+value="([^"]+)"\s*>([^<]*)<\/option>/', $contents, $options, PREG_SET_ORDER);

    foreach ($options as $option) {
        $value = $option[1];
        $label = trim($option[2]);

        if ($label === '' || $value === '') {
            continue;
        }

        $checks++;

        $known = false;

        foreach ($backendValues as $values) {
            if (in_array($value, $values, true)) {
                $known = true;
                break;
            }
        }

        if ($known) {
            continue;
        }

        // The value may be the right token in the wrong case.
        $upper = strtoupper($value);
        $match = null;

        foreach ($backendValues as $key => $values) {
            if (in_array($upper, $values, true)) {
                $match = $key . '.' . $upper;
                break;
            }
        }

        $fail(sprintf(
            'Frontend enum value "%s" does not match any backend enum   [%s]%s',
            $value,
            $relative,
            $match !== null
                ? "\n        the backend enum value is: {$match} — the database CHECK constraint rejects \"{$value}\""
                : ''
        ));
    }
}

// ---------------------------------------------------------------------------
// Report.
// ---------------------------------------------------------------------------

echo "SPEC DRIFT CHECK\n";
echo str_repeat('=', 62), "\n";
echo "spec:  docs/SPECIFICATION.md\n";
echo "checks run: {$checks}\n\n";

if ($failures === []) {
    echo "OK — the code matches the specification.\n";

    exit(0);
}

echo 'DRIFT FOUND — ', count($failures), " problem(s):\n\n";

foreach ($failures as $index => $failure) {
    echo '  ', $index + 1, ') ', $failure, "\n\n";
}

echo "Fix the code to match docs/SPECIFICATION.md, then re-run this script.\n";

exit(1);
