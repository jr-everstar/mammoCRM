<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>
    {{ filled($title ?? null) ? $title.' - '.config('app.name', 'Laravel') : config('app.name', 'Laravel') }}
</title>

<link rel="icon" type="image/png" href="/assets/images/favicon-96x96.png" sizes="96x96" />
<link rel="icon" type="image/svg+xml" href="/assets/images/favicon.svg" />
<link rel="shortcut icon" href="/assets/images/favicon.ico" />
<link rel="apple-touch-icon" sizes="180x180" href="/assets/images/apple-touch-icon.png" />
<meta name="apple-mobile-web-app-title" content="mammoCRM" />
<link rel="manifest" href="/assets/images/site.webmanifest" />

@fonts

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance
