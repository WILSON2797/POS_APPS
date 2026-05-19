<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'barcode'       => ['nullable', 'string', 'max:100', 'unique:products,barcode'],
            'name'          => ['required', 'string', 'max:200'],
            'category_id'   => ['required', 'exists:categories,id'],
            'cost_price'    => ['required', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'stock'         => ['required', 'integer', 'min:0'],
            'min_stock'     => ['required', 'integer', 'min:0'],
            'unit'          => ['required', 'string', 'max:20'],
            'description'   => ['nullable', 'string'],
            'image'         => ['nullable', 'image', 'max:2048'],
            'is_active'     => ['boolean'],
        ];
    }
}
