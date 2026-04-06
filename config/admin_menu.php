<?php

return [
    /*
    |--------------------------------------------------------------------------
    | User Menu Visibility Configuration
    |--------------------------------------------------------------------------
    |
    | Danh sách cấu hình ẩn/hiện các trang trong thanh sidebar của Admin.
    | Nếu MỞ comment (xóa khỏi mảng) thì trang đó sẽ được HIỂN THỊ.
    | Nếu CÓ trong mảng này thì trang đó sẽ bị ẨN khỏi menu.
    |
    */

    'hidden_resources' => [
        // --- Quản lý nghiệp vụ chính ---
        \App\Filament\Resources\Dealers\DealerResource::class,
        \App\Filament\Resources\Leads\LeadResource::class,
        \App\Filament\Resources\Customers\CustomerResource::class,

        // --- Quản lý Nội dung ---
        \App\Filament\Resources\Posts\PostResource::class,
        \App\Filament\Resources\Tags\TagResource::class,

        // --- Hệ thống ---
        \App\Filament\Resources\AdminUsers\AdminUserResource::class,
        \App\Filament\Resources\Roles\RoleResource::class,
        \App\Filament\Resources\Provinces\ProvinceResource::class,

        // --- Cài đặt & Lịch sử ---
        // \App\Filament\Pages\SystemSettings::class,

        // Công trình
        \App\Filament\Resources\Projects\ProjectResource::class,

        //Hệ

        \App\Filament\Resources\SystemTypes\SystemTypeResource::class,
        
    ],
];
