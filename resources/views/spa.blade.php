<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>kolgaev.ru</title>
        <link href="{{ mix('/css/app.css') }}" rel="stylesheet">
    </head>

    <body class="bg-light">

        <div style="height: 100vh; font-size: 74px; justify-content: center; display: flex; align-items: center; color: #636b6f; font-family: 'Verdana', sans-serif; font-weight: 200;" id="main-loading">
            <span>k<img src="/css/main-loading.gif" style="margin: 0 5px;" />lgaev.ru</span>
        </div>

        <div id="app">
            <App/>
        </div>

        <script src="{{ mix('/js/app.js') }}"></script>

    </body>

</html>