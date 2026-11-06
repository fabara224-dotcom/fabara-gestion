<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title', config('saas.name').' — Logiciel de gestion pour boutiques et commerces')</title>
  <meta name="description" content="Caisse, stock avec photos, reçus pour vos clients, rapports et application mobile qui marche sans internet. Pour boutiques, shops, quincailleries, pharmacies… en Guinée et en Afrique de l'Ouest.">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('css/app.css') }}?v=1">
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
  @stack('head')
</head>
<body class="@yield('body_class')">
@yield('content')
</body>
</html>
