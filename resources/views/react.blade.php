<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>kolgaev.ru</title>
        <link rel="manifest" href="/build/manifest.json" />
        <link href="{{ elixir('main.css') }}" rel="stylesheet">
    </head>

    <body>

        <div id="root"></div>

        <script src="{{ elixir('main.js') }}"></script>

    </body>

</html>