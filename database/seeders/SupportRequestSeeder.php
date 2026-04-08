<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\SupportRequest;
use App\Models\SystemType;
use Illuminate\Database\Seeder;

class SupportRequestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $product = Product::query()->first();
        $systemType = SystemType::query()->first();

        $requests = [
            [
                'customer_name' => 'Nguyen Van An',
                'customer_phone' => '0901234567',
                'customer_email' => 'an.nguyen@example.com',
                'customer_address' => 'Thu Duc, Ho Chi Minh City',
                'request_type' => 'general_contact',
                'product_id' => null,
                'system_type_id' => null,
                'status' => 'new',
                'source' => 'website',
                'customer_message' => 'Toi muon duoc tu van giai phap dien mat troi cho nha pho.',
                'admin_note' => null,
                'request_payload' => [
                    'contact_topic' => 'tu_van_tong_quan',
                ],
                'handled_at' => null,
            ],
            [
                'customer_name' => 'Tran Thi Binh',
                'customer_phone' => '0912345678',
                'customer_email' => 'binh.tran@example.com',
                'customer_address' => 'Di An, Binh Duong',
                'request_type' => 'product_quote',
                'product_id' => $product?->id,
                'system_type_id' => null,
                'status' => 'contacted',
                'source' => 'api',
                'customer_message' => 'Gui giup bao gia va thong so chi tiet cua san pham nay.',
                'admin_note' => 'Da goi lai cho khach, dang chot cau hinh cu the.',
                'request_payload' => [
                    'quantity' => 12,
                    'preferred_contact_time' => 'afternoon',
                    'product_code' => $product?->code,
                ],
                'handled_at' => now()->subHours(6),
            ],
            [
                'customer_name' => 'Le Quoc Cuong',
                'customer_phone' => '0988123456',
                'customer_email' => 'cuong.le@example.com',
                'customer_address' => 'Bien Hoa, Dong Nai',
                'request_type' => 'system_quote',
                'product_id' => null,
                'system_type_id' => $systemType?->id,
                'status' => 'quoted',
                'source' => 'website',
                'customer_message' => 'Nha toi tien dien khoang 3 trieu moi thang, can tu van bao gia theo he.',
                'admin_note' => 'Da gui bao gia tham khao va lich hen khao sat.',
                'request_payload' => [
                    'monthly_bill' => 3000000,
                    'phase_type' => '1P',
                    'system_type_slug' => $systemType?->slug,
                ],
                'handled_at' => now()->subDay(),
            ],
        ];

        foreach ($requests as $request) {
            if ($request['request_type'] === 'product_quote' && blank($request['product_id'])) {
                continue;
            }

            if ($request['request_type'] === 'system_quote' && blank($request['system_type_id'])) {
                continue;
            }

            SupportRequest::query()->updateOrCreate(
                [
                    'customer_phone' => $request['customer_phone'],
                    'request_type' => $request['request_type'],
                ],
                $request
            );
        }
    }
}
