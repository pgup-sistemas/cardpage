<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name')) · Card</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen flex items-center justify-center px-4 py-8 antialiased"
      style="background-color: #F0F0EE; font-family: 'Inter', sans-serif;">

    <div class="w-full max-w-[400px] bg-white rounded-[14px] shadow-[0_4px_24px_rgba(0,0,0,.10)] p-8">
        {{ $slot }}
    </div>

    <script>lucide.createIcons();</script>
    @livewireScripts
</body>
</html>
