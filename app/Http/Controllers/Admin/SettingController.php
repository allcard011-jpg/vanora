<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Rules\ImagePath;
use App\Support\Phone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    private const KEYS = [
        'store_name', 'store_address', 'store_whatsapp', 'opening_hours',
        'delivery_area', 'shipping_fee', 'payment_info_cash', 'payment_info_qris', 'qris_image_path',
    ];

    public function edit(): View
    {
        $values = collect(self::KEYS)->mapWithKeys(fn (string $key) => [$key => Setting::get($key)]);

        return view('admin.settings.edit', ['values' => $values]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'store_name' => ['required', 'string', 'max:100'],
            'store_address' => ['required', 'string', 'max:255'],
            'store_whatsapp' => ['required', 'string', 'max:30', function (string $attribute, mixed $value, \Closure $fail) {
                if (Phone::normalize($value) === null) {
                    $fail('Nomor WhatsApp tidak valid. Contoh: 081234567890.');
                }
            }],
            'opening_hours' => ['required', 'string', 'max:100'],
            'delivery_area' => ['required', 'string', 'max:100'],
            'shipping_fee' => ['required', 'integer', 'between:0,1000000'],
            'payment_info_cash' => ['required', 'string', 'max:500'],
            'payment_info_qris' => ['required', 'string', 'max:500'],
            'qris_image_path' => ['nullable', 'string', 'max:500', new ImagePath],
        ]);

        $data['store_whatsapp'] = Phone::normalize($data['store_whatsapp']);

        foreach ($data as $key => $value) {
            Setting::put($key, $value === null ? null : (string) $value);
        }

        return redirect()->route('admin.settings.edit')->with('status', 'Pengaturan disimpan.');
    }
}
