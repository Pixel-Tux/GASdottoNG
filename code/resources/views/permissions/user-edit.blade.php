<x-larastrap::modal :title="_i('Configura Ruoli per %s', [$user->printableName()])">
    <div class="role-editor">
        @foreach($currentuser->managed_roles as $role)
            <?php

            $urole = $user->roles()->where('roles.id', $role->id)->first();
            $targets = $role->targets;
            $last_class = null;

            ?>

            <div class="row">
                <h3>{{ $role->name }}</h3>

                @foreach($targets as $target)
                    @if ($targets->count() > 1 && $last_class != get_class($target))
                        <?php $last_class = get_class($target) ?>
                        <div class="col-md-4 alert-danger">
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" class="all-{{ $user->id }}-{{ $role->id }}" data-user="{{ $user->id }}" data-role="{{ $role->id }}" data-target-id="*" data-target-class="{{ $last_class }}" {{ $urole && $urole->appliesAll($last_class) ? 'checked' : '' }}> Tutti ({{ $last_class::commonClassName() }})
                                </label>
                            </div>
                        </div>
                    @endif

                    <div class="col-md-4">
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" data-role="{{ $role->id }}" data-user="{{ $user->id }}" data-target-id="{{ $target->id }}" data-target-class="{{ get_class($target) }}" {{ $urole && $urole->appliesOnly($target) ? 'checked' : '' }}> {{ $target->printableName() }}
                            </label>
                        </div>
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>
</x-larastrap::modal>
