<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  @vite(['resources/css/app.css', 'resources/js/app.ts'])
  <x-inertia::head>
    <title>{{config('app-name')}}</title>
</x-inertia::head>
</head>
<body class='antialised'>
  <x-inertia::app/>  
</body>
</html>