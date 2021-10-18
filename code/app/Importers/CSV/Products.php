<?php

namespace App\Importers\CSV;

use DB;

use App\Supplier;
use App\Product;
use App\Category;
use App\Measure;
use App\VatRate;

class Products extends CSVImporter
{
    protected function fields()
    {
        return [
            'name' => (object) [
                'label' => _i('Nome'),
                'mandatory' => true
            ],
            'description' => (object) [
                'label' => _i('Descrizione')
            ],
            'price' => (object) [
                'label' => _i('Prezzo Unitario'),
            ],
            'price_without_vat' => (object) [
                'label' => _i('Prezzo Unitario (senza IVA)'),
                'explain' => _i('Da usare in combinazione con Aliquota IVA')
            ],
            'vat' => (object) [
                'label' => _i('Aliquota IVA'),
            ],
            'category' => (object) [
                'label' => _i('Categoria'),
            ],
            'measure' => (object) [
                'label' => _i('Unità di Misura'),
            ],
            'supplier_code' => (object) [
                'label' => _i('Codice Fornitore'),
            ],
            'package_size' => (object) [
                'label' => _i('Dimensione Confezione'),
            ],
            'package_price' => (object) [
                'label' => _i('Prezzo Confezione'),
                'explain' => _i('Se specificato, il prezzo unitario viene calcolato come Prezzo Confezione / Dimensione Confezione')
            ],
            'weight' => (object) [
                'label' => _i('Peso (in KG)'),
            ],
            'min_quantity' => (object) [
                'label' => _i('Ordine Minimo'),
            ],
            'multiple' => (object) [
                'label' => _i('Ordinabile per Multipli')
            ],
        ];
    }

    private function getSupplier($request)
    {
        $supplier_id = $request->input('supplier_id');
        return Supplier::findOrFail($supplier_id);
    }

    public function testAccess($request)
    {
        return $request->user()->can('supplier.modify', $this->getSupplier($request));
    }

    public function guess($request)
    {
        $s = $this->getSupplier($request);

        return $this->storeUploadedFile($request, [
            'type' => 'products',
            'next_step' => 'select',
            'extra_fields' => ['supplier_id' => $s->id],
            'extra_description' => [_i('Le categorie e le unità di misura il cui nome non sarà trovato tra quelle esistenti saranno create.')],
            'sorting_fields' => $this->fields(),
        ]);
    }

    private function mapSelection($class, $param, $value, $field, &$product)
    {
        $test = $class::where($param, $value)->first();
        if (is_null($test)) {
            return 'temp_' . $field . '_name';
        }
        else {
            $field_name = sprintf('%s_id', $field);
            $product->$field_name = $test->id;
            return null;
        }
    }

    public function select($request)
    {
        list($reader, $columns) = $this->initRead($request);
        list($name_index, $supplier_code_index) = $this->getColumnsIndex($columns, ['name', 'supplier_code']);
        $s = $this->getSupplier($request);

        $products = $errors = [];

        foreach($reader->getRecords() as $line) {
            if (empty($line) || (count($line) == 1 && empty($line[0]))) {
                continue;
            }

            try {
                $name = $line[$name_index];

                $p = new Product();
                $p->name = $name;
                $p->category_id = $p->measure_id = 'non-specificato';
                $p->min_quantity = $p->multiple = $p->package_size = 0;
                $price_without_vat = $vat_rate = $package_price = null;

                $test_query = $s->products()->where('name', $name)->orderBy('id', 'desc');
                if ($supplier_code_index != -1 && !empty($line[$supplier_code_index])) {
                    $test_query->orWhere('supplier_code', $line[$supplier_code_index]);
                }
                $test = $test_query->first();
                $p->want_replace = is_null($test) ? 0 : $test->id;

                foreach ($columns as $index => $field) {
                    $value = trim($line[$index]);

                    if ($field == 'category') {
                        $field = $this->mapSelection(Category::class, 'name', $value, 'category', $p);
                    }
                    elseif ($field == 'measure') {
                        $field = $this->mapSelection(Measure::class, 'name', $value, 'measure', $p);
                    }
                    elseif ($field == 'price') {
                        $value = guessDecimal($value);
                    }
                    elseif ($field == 'vat') {
                        $value = guessDecimal($value);
                        if ($value == 0) {
                            $p->vat_rate_id = 0;
                            continue;
                        }
                        else {
                            $field = $this->mapSelection(VatRate::class, 'percentage', $value, 'vat_rate', $p);
                        }
                    }
                    elseif ($field == 'price_without_vat' || $field == 'package_price') {
                        $$field = guessDecimal($value);
                        continue;
                    }

                    if (!empty($value) && is_null($field) == false && $field != 'none') {
                        $p->$field = $value;
                    }
                }

                if (!empty($package_price) && !empty($p->package_size) && empty($p->price)) {
                    $p->price = $package_price / $p->package_size;
                }

                if (!empty($price_without_vat) && !empty($vat_rate)) {
                    $p->price = $price_without_vat + (($price_without_vat * $vat_rate) / 100);
                }

                $products[] = $p;
            }
            catch (\Exception $e) {
                $errors[] = join(',', $line) . '<br/>' . $e->getMessage();
            }
        }

        return ['products' => $products, 'supplier' => $s, 'errors' => $errors];
    }

    public function formatSelect($parameters)
    {
        return view('import.csvproductsselect', $parameters);
    }

    public function run($request)
    {
        DB::beginTransaction();

        $direct_fields = ['name', 'weight', 'description', 'price', 'supplier_code', 'package_size', 'min_quantity', 'multiple'];
        $data = $request->all();

        $s = $this->getSupplier($request);
        $errors = $products = $products_ids = $new_categories = $new_measures = $new_vats = [];

        foreach($data['import'] as $index) {
            try {
                if ($data['want_replace'][$index] != '0') {
                    $p = Product::find($data['want_replace'][$index]);
                }
                else {
                    $p = new Product();
                    $p->supplier_id = $s->id;
                    $p->active = true;
                }

                foreach($direct_fields as $field) {
                    $p->$field = $data[$field][$index];
                }

                $p->category_id = $this->mapNewElements($data['category_id'][$index], $new_categories, function($name) {
                    return Category::easyCreate(['name' => $name]);
                });

                $p->measure_id = $this->mapNewElements($data['measure_id'][$index], $new_measures, function($name) {
                    return Measure::easyCreate(['name' => $name]);
                });

                $p->vat_rate_id = $this->mapNewElements($data['vat_rate_id'][$index], $new_vats, function($name) {
                    $name = (float) $name;
                    $vat = new VatRate();
                    $vat->percentage = $name;
                    $vat->name = sprintf('%f %%', round($name, 2));
                    $vat->save();
                    return $vat;
                });

                $p->save();
                $products[] = $p;
                $products_ids[] = $p->id;
            }
            catch (\Exception $e) {
                $errors[] = $index . '<br/>' . $e->getMessage();
            }
        }

        if ($request->has('reset_list')) {
            $s->products()->whereNotIn('id', $products_ids)->update(['active' => false]);
        }

        DB::commit();

        return [
            'title' => _i('Prodotti importati'),
            'objects' => $products,
            'errors' => $errors,
            'extra_closing_attributes' => ['data-reload-target' => '#supplier-list']
        ];
    }
}
