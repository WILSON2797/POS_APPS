<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'POS Apps') }}</title>
    <script>
      const userMode = localStorage.getItem('coreui-free-vue-admin-template-theme');
      const systemDarkMode = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
      if (userMode === 'dark' || (userMode !== 'light' && systemDarkMode)) {
        document.documentElement.dataset.coreuiTheme = 'dark';
      }
    </script>
    @vite(['resources/scss/style.scss', 'resources/js/app.js'])
    @inertiaHead
  </head>
  <body>
    @inertia
  </body>
</html>
