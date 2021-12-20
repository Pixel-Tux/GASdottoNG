<?php

namespace App;

use Auth;

trait ModifiableTrait
{
    public function modifiers()
    {
        return $this->morphMany('App\Modifier', 'target');
    }

    public function attachEmptyModifier($modtype)
    {
        if ($this->modifiers()->where('modifier_type_id', $modtype->id)->count() == 0) {
            $mod = new Modifier();
            $mod->modifier_type_id = $modtype->id;
            $mod->target_type = get_class($this);
            $mod->target_id = (string) $this->id;
            $mod->definition = '[]';
            $mod->save();
            return $mod;
        }
    }

    private function duplicateModifiers($inherit)
    {
        $ret = $inherit->applicableModificationTypes();

        foreach($ret as $modtype) {
            if ($this->modifiers()->where('modifier_type_id', $modtype->id)->count() == 0) {
                $replica = $inherit->modifiers()->where('modifier_type_id', $modtype->id)->first()->replicate();
                $replica->target_id = $this->id;
                $replica->target_type = get_class($this);
                $replica->save();
            }
        }

        return $ret;
    }

    public function applicableModificationTypes()
    {
        $inherit = $this->inheritModificationTypes();

        if (!is_null($inherit)) {
            $ret = $this->duplicateModifiers($inherit);
        }
        else {
            $ret = [];
            $same = $this->sameModificationTypes();

            if (!is_null($same)) {
                foreach($same->applicableModificationTypes() as $modtype) {
                    $ret[] = $modtype;
                    $this->attachEmptyModifier($modtype);
                }
            }
            else {
                $current_class = get_class($this);
                foreach(ModifierType::orderBy('name', 'asc')->get() as $modtype) {
                    if (in_array($current_class, accessAttr($modtype, 'classes'))) {
                        $ret[] = $modtype;
                        $this->attachEmptyModifier($modtype);
                    }
                }
            }
        }

        return $ret;
    }

    /*
        Questa va all'occorrenza sovrascritta se si vogliono usare gli stessi
        modificatori (con gli stessi valori) di un altro oggetto
    */
    public function inheritModificationTypes()
    {
        return null;
    }

    /*
        Questa va all'occorrenza sovrascritta se si vogliono usare gli stessi
        tipi di modificatore di un altro oggetto (ma con valori di default
        vuoti)
    */
    public function sameModificationTypes()
    {
        return null;
    }
}
