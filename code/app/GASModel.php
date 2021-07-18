<?php

namespace App;

use Illuminate\Support\Str;

use Auth;
use URL;
use Log;
use Schema;

trait GASModel
{
    private $inner_runtime_cache;

    /*
        Funzione di comodo, funge come find() ma se la classe è soft-deletable
        cerca anche tra gli elementi cancellati
    */
    public static function tFind($id, $fail = false)
    {
        $class = get_called_class();

        if (in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($class)))
            $ret = $class::where('id', $id)->withoutGlobalScopes()->withTrashed()->first();
        else
            $ret = $class::find($id);

        if ($ret == null && $fail == true)
            abort(404);

        return $ret;
    }

    public function printableName()
    {
        return $this->name;
    }

    public function getPrintableNameAttribute()
    {
        return $this->printableName();
    }

    protected function headerIcons()
    {
        $ret = '';
        $icons = $this->icons();

        if (!empty($icons)) {
            $ret .= '<div class="float-end">';

            foreach ($icons as $i) {
                $ret .= '<i class="bi-' . $i . '"></i>';
                if (substr($i, 0, 6) != 'hidden')
                    $ret .= '&nbsp;';
            }

            $ret .= '</div>';
        }

        return $ret;
    }

    public function printableHeader()
    {
        return $this->printableName() . $this->headerIcons();
    }

    public function printableDate($name)
    {
        return printableDate($this->$name);
    }

    protected function innerCache($name, $function)
    {
        if (!isset($this->inner_runtime_cache[$name])) {
            $this->inner_runtime_cache[$name] = $function($this);
        }

        return $this->inner_runtime_cache[$name];
    }

    protected function setInnerCache($name, $value)
    {
        $this->inner_runtime_cache[$name] = $value;
    }

    protected function emptyInnerCache($name = null)
    {
        if (is_null($name))
            $this->inner_runtime_cache = [];
        else
            unset($this->inner_runtime_cache[$name]);
    }

    private function relatedController()
    {
        $class = get_class($this);
        list($namespace, $class) = explode('\\', $class);

        return Str::plural($class).'Controller';
    }

    public function getDisplayURL()
    {
        $controller = $this->relatedController();
        $action = sprintf('%s@index', $controller);

        return URL::action($action).'#'.$this->id;
    }

    public function getShowURL()
    {
        $controller = $this->relatedController();
        $action = sprintf('%s@show', $controller);

        return URL::action($action, $this->id);
    }

    public function getROShowURL()
    {
        $controller = $this->relatedController();
        $action = sprintf('%s@show_ro', $controller);

        try {
            return URL::action($action, $this->id);
        }
        catch(\Exception $e) {
            return null;
        }
    }

    public function testAndSet($request, $name, $field = null)
    {
        if (is_null($field))
            $field = $name;

        if ($request->has($name))
            $this->$field = $request->input($name);
    }

    /*
        Questa va all'occorrenza sovrascritta
    */
    public static function commonClassName()
    {
        return 'Oggetto';
    }

    /*
        Questa va all'occorrenza sovrascritta
    */
    public function getPermissionsProxies()
    {
        return null;
    }

    /*
        Questa va all'occorrenza sovrascritta
    */
    public function scopeEnabled($query)
    {
        return $query->whereNotNull('id');
    }

    public function scopeSorted($query)
    {
        if (Schema::hasColumn($this->table, 'name'))
            return $query->orderBy('name', 'asc');
        else if (Schema::hasColumn($this->table, 'lastname'))
            return $query->orderBy('lastname', 'asc');
        else
            return $query->orderBy('id', 'asc');
    }

    public static function iconsMap()
    {
        static $icons = null;

        if (is_null($icons)) {
            $user = Auth::user();

            /*
                La chiave di ogni array interno è il nome dell'icona FontAwesome
                da usare per la relativa icona.
                Per avere il filtro ma non l'icona aggiungere il prefisso
                "hidden-" al nome
            */
            $icons = [
                'Supplier' => [
                    'pencil' => (object) [
                        'test' => function ($obj) use ($user) {
                            return $user->can('supplier.modify', $obj);
                        },
                        'text' => _i('Puoi modificare il fornitore'),
                    ],
                    'card-list' => (object) [
                        'test' => function ($obj) use ($user) {
                            return $user->can('supplier.orders', $obj);
                        },
                        'text' => _i('Puoi aprire nuovi ordini per il fornitore'),
                    ],
                    'arrow-down' => (object) [
                        'test' => function ($obj) use ($user) {
                            return $user->can('supplier.shippings', $obj);
                        },
                        'text' => _i('Gestisci le consegne per il fornitore'),
                    ],
                ],
                'Attachment' => [
                    'image' => (object) [
                        'test' => function ($obj) {
                            return $obj->isImage();
                        },
                        'text' => _i('Immagine'),
                    ],
                    'shield-x' => (object) [
                        'test' => function ($obj) {
                            return ($obj->users()->count() != 0);
                        },
                        'text' => _i('Accesso limitato'),
                    ],
                ],
                'Product' => [
                    'slash-circle' => (object) [
                        'test' => function ($obj) {
                            return $obj->active == false;
                        },
                        'text' => _i('Disabilitato'),
                    ],
                    'hidden-circle' => (object) [
                        'test' => function ($obj) {
                            return $obj->active == true;
                        },
                        'text' => _i('Attivo'),
                    ],
                    'star' => (object) [
                        'test' => function ($obj) {
                            return !empty($obj->discount) && $obj->discount != 0;
                        },
                        'text' => _i('Scontato'),
                    ]
                ],
                'Aggregate' => [
                    'card-list' => (object) [
                        'test' => function ($obj) use ($user) {
                            return $user->can('supplier.orders', $obj);
                        },
                        'text' => _i('Puoi modificare'),
                    ],
                    'arrow-down' => (object) [
                        'test' => function ($obj) use ($user) {
                            return $user->can('supplier.shippings', $obj);
                        },
                        'text' => _i('Gestisci le consegne'),
                    ],
                    'play' => (object) [
                        'test' => function ($obj) {
                            return $obj->status == 'open';
                        },
                        'text' => _i('Prenotazioni Aperte'),
                    ],
                    'pause' => (object) [
                        'test' => function ($obj) {
                            return $obj->status == 'suspended';
                        },
                        'text' => _i('In Sospeso'),
                    ],
                    'stop-fill' => (object) [
                        'test' => function ($obj) {
                            return $obj->status == 'closed';
                        },
                        'text' => _i('Prenotazioni Chiuse'),
                    ],
                    'skip-forward' => (object) [
                        'test' => function ($obj) {
                            return $obj->status == 'shipped';
                        },
                        'text' => _i('Consegnato'),
                    ],
                    'eject' => (object) [
                        'test' => function ($obj) {
                            return $obj->status == 'archived';
                        },
                        'text' => _i('Archiviato'),
                    ],
                    'plus-circle' => (object) [
                        'test' => function ($obj) {
                            return ($obj->status == 'closed' && $obj->hasPendingPackages());
                        },
                        'text' => _i('Confezioni Da Completare'),
                    ]
                ],
                'Order' => [
                    'card-list' => (object) [
                        'test' => function ($obj) use ($user) {
                            return $user->can('supplier.orders', $obj);
                        },
                        'text' => _i("Puoi modificare l'ordine"),
                    ],
                    'arrow-down' => (object) [
                        'test' => function ($obj) use ($user) {
                            return $user->can('supplier.shippings', $obj);
                        },
                        'text' => _i("Gestisci le consegne per l'ordine"),
                    ],
                    'play' => (object) [
                        'test' => function ($obj) {
                            return $obj->status == 'open';
                        },
                        'text' => _i('Prenotazioni Aperte'),
                    ],
                    'pause' => (object) [
                        'test' => function ($obj) {
                            return $obj->status == 'suspended';
                        },
                        'text' => _i('In Sospeso'),
                    ],
                    'stop-fill' => (object) [
                        'test' => function ($obj) {
                            return $obj->status == 'closed';
                        },
                        'text' => _i('Prenotazioni Chiuse'),
                    ],
                    'skip-forward' => (object) [
                        'test' => function ($obj) {
                            return $obj->status == 'shipped';
                        },
                        'text' => _i('Consegnato'),
                    ],
                    'eject' => (object) [
                        'test' => function ($obj) {
                            return $obj->status == 'archived';
                        },
                        'text' => _i('Archiviato'),
                    ],
                    'plus-circle' => (object) [
                        'test' => function ($obj) {
                            return ($obj->keep_open_packages && $obj->status == 'closed' && $obj->pendingPackages()->isEmpty() == false);
                        },
                        'text' => _i('Confezioni Da Completare'),
                    ]
                ],
                'AggregateBooking' => [
                    'clock' => (object) [
                        'test' => function ($obj) {
                            return $obj->status != 'shipped';
                        },
                        'text' => _i('Da consegnare'),
                    ],
                    'check' => (object) [
                        'test' => function ($obj) {
                            return $obj->status == 'shipped';
                        },
                        'text' => _i('Consegnato'),
                    ],
                    'save' => (object) [
                        'test' => function ($obj) {
                            return $obj->status == 'saved';
                        },
                        'text' => _i('Salvato'),
                    ],
                ],
                'Receipt' => [
                    'arrow-right' => (object) [
                        'test' => function ($obj) {
                            return true;
                        },
                        'text' => _i('In Uscita'),
                    ],
                    'envelope' => (object) [
                        'test' => function ($obj) {
                            return $obj->mailed;
                        },
                        'text' => _i('Inoltrata'),
                    ],
                ],
                'Invoice' => [
                    'clock' => (object) [
                        'test' => function ($obj) {
                            return $obj->status == 'pending';
                        },
                        'text' => _i('In Attesa'),
                    ],
                    'pin-angle' => (object) [
                        'test' => function ($obj) {
                            return $obj->status == 'to_verify';
                        },
                        'text' => _i('Da Verificare'),
                    ],
                    'search' => (object) [
                        'test' => function ($obj) {
                            return $obj->status == 'verified';
                        },
                        'text' => _i('Verificata'),
                    ],
                    'check' => (object) [
                        'test' => function ($obj) {
                            return $obj->status == 'payed';
                        },
                        'text' => _i('Pagata'),
                    ],
                ],
                'Booking' => [
                    'clock' => (object) [
                        'test' => function ($obj) {
                            return $obj->status != 'shipped';
                        },
                        'text' => _i('Da consegnare'),
                    ],
                    'check' => (object) [
                        'test' => function ($obj) {
                            return $obj->status == 'shipped';
                        },
                        'text' => _i('Consegnato'),
                    ],
                    'save' => (object) [
                        'test' => function ($obj) {
                            return $obj->status == 'saved';
                        },
                        'text' => _i('Salvato'),
                    ],
                ],
                'User' => [
                ],
            ];

            if ($user->can('supplier.add', $user->gas)) {
                $icons['Supplier']['hand-thumbs-down'] = (object) [
                    'test' => function ($obj) {
                        return !is_null($obj->suspended_at);
                    },
                    'text' => _i('Sospeso'),
                ];
                $icons['Supplier']['slash-circle'] = (object) [
                    'test' => function ($obj) {
                        return !is_null($obj->deleted_at);
                    },
                    'text' => _i('Eliminato'),
                ];
            }

            if ($user->can('users.admin', $user->gas)) {
                $icons['User']['hand-thumbs-down'] = (object) [
                    'test' => function ($obj) {
                        return !is_null($obj->suspended_at);
                    },
                    'text' => _i('Sospeso'),
                ];

                $icons['User']['slash-circle'] = (object) [
                    'test' => function ($obj) {
                        return !is_null($obj->deleted_at);
                    },
                    'text' => _i('Cessato'),
                ];
            }

            if ($user->can('movements.admin', $user->gas) || $user->can('movements.view', $user->gas)) {
                $icons['User']['wallet'] = (object) [
                    'test' => function ($obj) {
                        return $obj->current_balance_amount < 0;
                    },
                    'text' => _i('Credito < 0'),
                ];

                /*
                    Se la gestione delle quote di iscrizione è abilitata, viene
                    attivata la relativa icona per distinguere gli utenti che non
                    l'hanno pagata o rinnovata
                */
                if ($user->gas->getConfig('annual_fee_amount') != 0) {
                    $icons['User']['currency-euro'] = (object) [
                        'test' => function ($obj) {
                            return $obj->fee_id == 0;
                        },
                        'text' => _i('Quota non Pagata'),
                    ];
                }
            }

            /*
                Poiché fatture in ingresso (Invoice) e in uscita (Receipt) sono
                visualizzate nello stesso elenco, se queste ultime sono attive
                abilito delle icone distintive per permettere di riconoscerle
                al volo
            */
            if ($user->gas->hasFeature('extra_invoicing')) {
                $icons['Invoice']['arrow-left'] = (object) [
                    'test' => function ($obj) {
                        return true;
                    },
                    'text' => _i('In Entrata'),
                ];
            }

            if (Gas::count() > 1) {
                $icons['Aggregate']['share'] = (object) [
                    'test' => function ($obj) {
                        return $obj->gas()->count() > 1;
                    },
                    'text' => _i('Multi-GAS'),
                ];
            }
        }

        return $icons;
    }

    private static function selectiveIconsMap()
    {
        static $icons = null;

        if (is_null($icons)) {
            $icons = [
                'Product' => [
                    'th' => (object) [
                        'text' => _i('Categoria'),
                        'assign' => function ($obj) {
                            return ['hidden-cat-' . $obj->category_id];
                        },
                        'options' => function($objs) {
                            $categories = $objs->pluck('category_id')->toArray();
                            $categories = array_unique($categories);

                            return Category::whereIn('id', $categories)->orderBy('name', 'asc')->get()->reduce(function($carry, $item) {
                                $carry['hidden-cat-' . $item->id] = $item->name;
                                return $carry;
                            }, []);
                        }
                    ]
                ],
                'User' => [
                    'person-circle' => (object) [
                        'text' => _i('Ruolo'),
                        'assign' => function ($obj) {
                            $ret = [];
                            foreach($obj->roles as $r) {
                                $ret[] = 'hidden-person-circle-' . $r->id;
                            }
                            return $ret;
                        },
                        'options' => function($objs) {
                            $user = Auth::user();

                            return Role::whereNotIn('id', [$user->gas->roles['user'], $user->gas->roles['friend']])->get()->reduce(function($carry, $item) {
                                $carry['hidden-person-circle-' . $item->id] = $item->name;
                                return $carry;
                            }, []);
                        }
                    ]
                ]
            ];
        }

        return $icons;
    }

    public function icons()
    {
        $class = get_class($this);
        list($namespace, $class) = explode('\\', $class);

        $map = self::iconsMap();
        $ret = [];

        if (isset($map[$class])) {
            foreach ($map[$class] as $icon => $condition) {
                $t = $condition->test;
                if ($t($this)) {
                    $ret[] = $icon;
                }
            }
        }

        $map = self::selectiveIconsMap();

        if (isset($map[$class])) {
            foreach ($map[$class] as $icon => $condition) {
                $assign = $condition->assign;
                $ret = array_merge($ret, $assign($this));
            }
        }

        return $ret;
    }

    public static function iconsLegend($class, $contents = null)
    {
        $map = self::iconsMap();
        $ret = [];

        if (isset($map[$class])) {
            foreach ($map[$class] as $icon => $condition) {
                $ret[$icon] = $condition->text;
            }
        }

        if ($contents != null) {
            $map = self::selectiveIconsMap();

            if (isset($map[$class])) {
                foreach ($map[$class] as $icon => $condition) {
                    $options = $condition->options;
                    $options = $options($contents);
                    if (!empty($options)) {
                        $description = (object) [
                            'label' => $condition->text,
                            'items' => $options
                        ];
                        $ret[$icon] = $description;
                    }
                }
            }
        }

        return $ret;
    }
}
