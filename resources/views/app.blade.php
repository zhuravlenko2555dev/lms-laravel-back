<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('app.name') }}</title>

    <link href="https://fonts.cdnfonts.com/css/lato" rel="stylesheet">
    @vite("resources/scss/app.scss")
</head>
<body>
    <div id="app" />
    @vite("resources/ts/app.ts")
</body>
</html>
