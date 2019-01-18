<?php

function currentLang()
{
    static $lang = '';

    if (empty($lang)) {
        /*
            Nel caso estremo in cui non ci sia alcun GAS recuperabile chiamando
            questa funzione, assumo che la lingua sia l'italiano. Ma non salvo
            questa informazione nella variabile statica, sperando che alla
            prossima iterazione possa accedere ad un GAS effettivo.
            Serve soprattutto a far funzionare gli unit test...
        */
        $gas = currentAbsoluteGas();
        if (is_null($gas))
            return 'it_IT';
        else
            $lang = currentAbsoluteGas()->getConfig('language');
    }

    return $lang;
}

function htmlLang()
{
    return str_replace('_', '-', currentLang());
}

/*
    Se vengono aggiunte nuove traduzioni, correggere anche il file
    code/config/laravel-gettext.php
*/
function getLanguages()
{
    return [
        [
            'value' => 'it_IT',
            'label' => 'Italiano'
        ],
        [
            'value' => 'en_EN',
            'label' => 'English'
        ],
        [
            'value' => 'de_DE',
            'label' => 'Deutsch'
        ],
        [
            'value' => 'fr_FR',
            'label' => 'Français'
        ],
        [
            'value' => 'nl_NL',
            'label' => 'Nederlands'
        ],
        [
            'value' => 'nb_NO',
            'label' => 'Norwegian Bokmål'
        ],
    ];
}

function localeMonths()
{
    $lang = currentLang();
    $months = [];

    switch($lang) {
        case 'it_IT':
            $months = [
                'gennaio' => 'january',
                'febbraio' => 'february',
                'marzo' => 'march',
                'aprile' => 'april',
                'maggio' => 'may',
                'giugno' => 'june',
                'luglio' => 'july',
                'agosto' => 'august',
                'settembre' => 'september',
                'ottobre' => 'october',
                'novembre' => 'november',
                'dicembre' => 'december',
            ];
            break;

        case 'en_EN':
            $months = [
                'january' => 'january',
                'february' => 'february',
                'march' => 'march',
                'april' => 'april',
                'may' => 'may',
                'june' => 'june',
                'july' => 'july',
                'august' => 'august',
                'september' => 'september',
                'october' => 'october',
                'november' => 'november',
                'december' => 'december',
            ];
            break;

        case 'de_DE':
            $months = [
                'januar' => 'january',
                'februar' => 'february',
                'märz' => 'march',
                'april' => 'april',
                'mai' => 'may',
                'juni' => 'june',
                'juli' => 'july',
                'august' => 'august',
                'september' => 'september',
                'oktober' => 'october',
                'november' => 'november',
                'dezember' => 'december',
            ];
            break;

        case 'fr_FR':
            $months = [
                'janvier' => 'january',
                'février' => 'february',
                'mars' => 'march',
                'avril' => 'april',
                'mai' => 'may',
                'juin' => 'june',
                'juillet' => 'july',
                'août' => 'august',
                'septembre' => 'september',
                'octobre' => 'october',
                'novembre' => 'november',
                'décembre' => 'december',
            ];
            break;

        case 'nb_NO':
            $months = [
                'januar' => 'january',
                'februar' => 'february',
                'märs' => 'march',
                'april' => 'april',
                'mai' => 'may',
                'juni' => 'june',
                'juli' => 'july',
                'august' => 'august',
                'september' => 'september',
                'oktober' => 'october',
                'november' => 'november',
                'desember' => 'december',
            ];
            break;

        case 'nl_NL':
            $months = [
                'januari' => 'january',
                'februari' => 'february',
                'maart' => 'march',
                'april' => 'april',
                'mei' => 'may',
                'juni' => 'june',
                'juli' => 'july',
                'augustus' => 'august',
                'september' => 'september',
                'oktober' => 'october',
                'november' => 'november',
                'december' => 'december',
            ];
            break;
    }

    return $months;
}
