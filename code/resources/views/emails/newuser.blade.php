<p>
    {{ _i('Nuovo utente registrato su %s:', $user->gas->name) }}
</p>
<p>
    {{ $user->printableName() }}<br>
    {{ $user->email }}<br>

    @foreach($user->getContactsByType(['phone', 'mobile']) as $phone)
        {{ $phone }}<br>
    @endforeach
</p>

@if($user->pending)
    <p>
        {{ _i('Il nuovo utente è in attesa di revisione: consulta il pannello di amministrazione per approvarlo o eliminarlo.') }}<br>
        {{ route('users.index') }}
    </p>
@endif
