<!DOCTYPE html>
<html lang="{{ htmlLang() }}">
    <head>
        <meta charset="utf-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">

        <title>{{ currentAbsoluteGas()->name }} | GASdotto</title>
        <link rel="alternate" type="application/rss+xml" title="{{ _i('Ordini Aperti') }}" href="{{ route('rss') }}"/>

        <link rel="stylesheet" type="text/css" href="{{ mix('/css/gasdotto.css') }}">

        <meta name="csrf-token" content="{{ csrf_token() }}"/>
        <meta name="absolute_url" content="{{ url('/') }}"/>
        <meta name="current_currency" content="{{ currentAbsoluteGas()->currency }}"/>
    </head>
    <body>
        <div id="preloader">
            <img src="{{ asset('images/loading.svg') }}" alt="{{ _i('Caricamento in corso') }}">
        </div>

        <nav class="navbar navbar-default navbar-fixed-top navbar-inverse">
            <div class="container-fluid">
                <div class="navbar-header">
                    <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#main-navbar" aria-expanded="false">
                        <span class="sr-only">Toggle navigation</span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                    </button>
                    @if(!isset($MainMenu))
                        <a class="navbar-brand hidden-md hidden-sm" href="{{ route('dashboard') }}">GASdotto</a>
                    @endif
                </div>

                <div class="collapse navbar-collapse" id="main-navbar">
                    @if(isset($MainMenu))
                        <ul class="nav navbar-nav">
                            @include(config('laravel-menu.views.bootstrap-items'), ['items' => $MainMenu->roots()])
                        </ul>
                    @endif

                    @if(Auth::check())
                        <ul class="nav navbar-nav navbar-right">
                            <li class="hidden-xs">
                                <a href="#" data-toggle="modal" data-target="#feedback-modal"><span class="glyphicon glyphicon-bullhorn" aria-hidden="true"></span></a>
                            </li>
                            <li>
                                <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();"><span class="glyphicon glyphicon-off" aria-hidden="true"></span></a>
                                <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                                    {{ csrf_field() }}
                                </form>
                            </li>
                        </ul>
                    @endif
                </div>
            </div>
        </nav>

        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12" id="main-contents">
                    @include('commons.flashing')
                    @yield('content')
                </div>
            </div>
        </div>

        <div id="postponed"></div>
        <div id="bottom-stop"></div>

        <div class="modal fade" id="service-modal" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-extra-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title">&nbsp;</h4>
                    </div>
                    <div class="modal-body">
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="feedback-modal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title">{{ _i('Feedback') }}</h4>
                    </div>

                    <div class="modal-body">
                        <p>
                            {{ _i('GASdotto è sviluppato con modello open source! Puoi contribuire mandando una segnalazione o una richiesta:') }}
                        </p>
                        <p>
                            <a href="https://github.com/madbob/GASdottoNG/" target="_blank">https://github.com/madbob/GASdottoNG/</a><br>
                            <a href="mailto:info@gasdotto.net">info@gasdotto.net</a>
                        </p>
                        <p>
                            {{ _i('o facendo una donazione:') }}
                        </p>
                        <p>
                            <a href="https://paypal.me/m4db0b" target="_blank"><img src="https://www.gasdotto.net/images/paypal.png" border="0"></a>
                        </p>
                        <p>
                            {{ _i('Attenzione: per problemi sui contenuti di questo sito (fornitori, ordini, prenotazioni...) fai riferimento agli amministrazioni del tuo GAS.') }}
                        </p>

                        @if(currentLang() != 'it_IT')
                            <p>
                                {!! _i('Se vuoi contribuire alla traduzione nella tua lingua, visita <a href="https://hosted.weblate.org/projects/gasdottong/translations/">questa pagina</a>.') !!}
                            </p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        @if(Session::has('prompt_message'))
            <div class="modal fade" id="prompt-message-modal" tabindex="-1" role="dialog">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                            <h4 class="modal-title">{{ _i('Attenzione') }}</h4>
                        </div>
                        <div class="modal-body">
                            <p>
                                {!! Session::get('prompt_message') !!}
                            </p>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-default" data-dismiss="modal">{{ _i('Chiudi') }}</button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <script type="application/javascript" src="{{ mix('/js/gasdotto.js') }}"></script>
        <script type="application/javascript" src="{{ asset('/js/lang/bootstrap-datepicker.' . htmlLang() . '.min.js') }}"></script>
        <script type="application/javascript" src="{{ asset('/js/lang/bootstrap-table-' . htmlLang() . '.js') }}"></script>
        <script type="application/javascript" src="{{ asset('/js/lang/' . htmlLang() . '.js') }}"></script>

        <!-- Piwik -->
        <script type="text/javascript">
            var _paq = _paq || [];
            _paq.push(["setDocumentTitle", document.domain + "/" + document.title]);
            _paq.push(['disableCookies']);
            _paq.push(['trackPageView']);
            _paq.push(['enableLinkTracking']);
            (function() {
                var u="//stats.madbob.org/";
                _paq.push(['setTrackerUrl', u+'piwik.php']);
                _paq.push(['setSiteId', '11']);
                var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];
                g.type='text/javascript'; g.async=true; g.defer=true; g.src=u+'piwik.js'; s.parentNode.insertBefore(g,s);
            })();
        </script>
        <noscript><p><img src="//stats.madbob.org/piwik.php?idsite=11&rec=1" style="border:0;" alt="" /></p></noscript>
        <!-- End Piwik Code -->
    </body>
</html>
