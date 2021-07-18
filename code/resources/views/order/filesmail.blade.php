@if($contacts->isEmpty() == false)
    <?php

    if (!isset($default_subject)) {
        $default_subject = _i('Documento allegato');
    }

    if (!isset($default_text)) {
        $default_text = '';
    }

    ?>

    <hr/>

    <x-larastrap::check name="send_mail" :label="_i('Inoltra Mail')" classes="collapse_trigger" />

    <div class="collapse" data-triggerable="send_mail">
        <x-larastrap::field :label="_i('Destinatari')">
            @include('commons.manyrows', [
                'contents' => $contacts,
                'columns' => [
                    [
                        'label' => _i('Valore'),
                        'field' => 'value',
                        'type' => 'email',
                        'width' => 11,
                        'extra' => [
                            'nprefix' => 'recipient_mail_'
                        ]
                    ]
                ]
            ])
        </x-larastrap::field>

        <x-larastrap::text name="subject_mail" :label="_i('Soggetto')" :value="$default_subject" />
        <x-larastrap::textarea name="body_mail" :label="_i('Testo')" :value="$default_text" />
    </div>
@endif
