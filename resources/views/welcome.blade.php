<!DOCTYPE html>
<html lang="en" class="">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $appBase = rtrim(request()->getBasePath(), '/');
        $appBase = ($appBase === '' || $appBase === '/') ? '' : $appBase;
    @endphp
    <meta name="app-base" content="{{ $appBase }}">
    <meta name="app-url" content="{{ config('app.url') }}">
    <title>Hospital Management System</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 dark:bg-gray-900 antialiased">
    <div id="app"></div>
</body>
</html>
