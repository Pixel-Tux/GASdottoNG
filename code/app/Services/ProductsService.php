<?php

namespace App\Services;

use App\Exceptions\AuthException;
use App\Exceptions\IllegalArgumentException;

use Auth;
use Log;
use DB;

use App\User;
use App\Supplier;
use App\Product;
use App\Role;

class ProductsService extends BaseService
{
    public function list($term = '', $all = false)
    {
        /* TODO */
    }

    public function show($id)
    {
        return Product::withTrashed()->with('variants')->with('variants.values')->findOrFail($id);
    }

    private function enforceMeasure($product, $request)
    {
        if ($product->measure->discrete) {
            $product->portion_quantity = 0;
            $product->variable = false;
        }
        else {
            $this->transformAndSetIfSet($product, $request, 'portion_quantity', 'enforceNumber');
            $product->variable = isset($request['variable']);
        }

        return $product;
    }

    private function setCommonAttributes($product, $request)
    {
        $this->setIfSet($product, $request, 'name');
        $this->setIfSet($product, $request, 'description');
        $this->transformAndSetIfSet($product, $request, 'price', 'enforceNumber');

        $this->setIfSet($product, $request, 'category_id');
        if (empty($product->category_id))
            $product->category_id = 'non-specificato';

        $this->setIfSet($product, $request, 'measure_id');
        if (empty($product->measure_id))
            $product->measure_id = 'non-specificato';

        $request['discount'] = savingPercentage($request, 'discount');
        $this->transformAndSetIfSet($product, $request, 'discount', 'normalizePercentage');

        $this->transformAndSetIfSet($product, $request, 'vat_rate_id', function($value) {
            if ($value != 0)
                return $value;
            else
                return null;
        });
    }

    public function store(array $request)
    {
        $supplier = Supplier::findOrFail($request['supplier_id']);
        $this->ensureAuth(['supplier.modify' => $supplier]);

        $product = new Product();
        $product->supplier_id = $supplier->id;

        if (!isset($request['duplicating_from'])) {
            $product->active = true;
        }

        DB::transaction(function () use ($product, $request) {
            $this->setCommonAttributes($product, $request);
            $product->save();
        });

        if (isset($request['duplicating_from'])) {
            $original_product_id = $request['duplicating_from'];
            $original_product = Product::find($original_product_id);

            foreach($original_product->variants as $old_variant) {
                $new_variant = $old_variant->replicate();
                $new_variant->id = '';
                $new_variant->product_id = $product->id;
                $new_variant->save();

                foreach($old_variant->values as $old_value) {
                    $new_value = $old_value->replicate();
                    $new_value->id = '';
                    $new_value->variant_id = $new_variant->id;
                    $new_value->save();
                }
            }
        }

        return $product;
    }

    public function update($id, array $request)
    {
        $product = $this->show($id);
        $this->ensureAuth(['supplier.modify' => $product->supplier]);

        DB::transaction(function () use ($product, $request) {
            $this->setCommonAttributes($product, $request);

            $product->active = (isset($request['active']) && $request['active'] !== false);
            $this->setIfSet($product, $request, 'supplier_code');
            $this->transformAndSetIfSet($product, $request, 'weight', 'enforceNumber');
            $this->transformAndSetIfSet($product, $request, 'package_size', 'enforceNumber');
            $this->transformAndSetIfSet($product, $request, 'multiple', 'enforceNumber');
            $this->transformAndSetIfSet($product, $request, 'min_quantity', 'enforceNumber');
            $this->transformAndSetIfSet($product, $request, 'max_quantity', 'enforceNumber');
            $this->transformAndSetIfSet($product, $request, 'max_available', 'enforceNumber');
            $product = $this->enforceMeasure($product, $request);
            $product->save();

            if (isset($request['picture'])) {
                saveFile($request['picture'], $product, 'picture');
            }
        });

        return $product;
    }

    public function picture($id)
    {
        $product = Product::findOrFail($id);
        return downloadFile($product, 'picture');
    }

    public function destroy($id)
    {
        $product = DB::transaction(function() use ($id) {
            $product = $this->show($id);
            $this->ensureAuth(['supplier.modify' => $product->supplier]);
            $product->delete();
            return $product;
        });

        return $product;
    }
}
