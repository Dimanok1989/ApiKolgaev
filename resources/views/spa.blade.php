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

        <!-- Yandex.Metrika counter -->
        {{-- <script type="text/javascript" >
            (function(m,e,t,r,i,k,a){m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
            m[i].l=1*new Date();k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)})
            (window, document, "script", "https://mc.yandex.ru/metrika/tag.js", "ym");

            ym(44940325, "init", {
                clickmap:true,
                trackLinks:true,
                accurateTrackBounce:true,
                webvisor:true
            });
        </script>
        <noscript><div><img src="https://mc.yandex.ru/watch/44940325" style="position:absolute; left:-9999px;" alt="" /></div></noscript>
        <!-- /Yandex.Metrika counter --> --}}

    </body>

</html>