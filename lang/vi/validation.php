<?php

return [
    'required' => 'Trường :attribute là bắt buộc.',
    'string' => 'Trường :attribute phải là chuỗi ký tự.',
    'array' => 'Trường :attribute phải là một mảng.',
    'numeric' => 'Trường :attribute phải là số.',
    'integer' => 'Trường :attribute phải là số nguyên.',
    'date' => 'Trường :attribute không đúng định dạng ngày.',
    'image' => 'Trường :attribute phải là hình ảnh hợp lệ.',
    'mimes' => 'Trường :attribute phải có định dạng: :values.',
    'mimetypes' => 'Trường :attribute phải là tệp thuộc loại: :values.',
    'max' => [
        'numeric' => 'Trường :attribute không được lớn hơn :max.',
        'file' => 'Trường :attribute không được lớn hơn :max KB.',
        'string' => 'Trường :attribute không được dài quá :max ký tự.',
        'array' => 'Trường :attribute không được có quá :max phần tử.',
    ],
    'min' => [
        'numeric' => 'Trường :attribute phải lớn hơn hoặc bằng :min.',
        'file' => 'Trường :attribute phải có dung lượng tối thiểu :min KB.',
        'string' => 'Trường :attribute phải có ít nhất :min ký tự.',
        'array' => 'Trường :attribute phải có ít nhất :min phần tử.',
    ],
    'exists' => 'Trường :attribute không tồn tại trong hệ thống.',

    'attributes' => [
        'file' => 'tệp tải lên',
        'files' => 'tệp tải lên',
        'image' => 'hình ảnh',
        'images' => 'ảnh công trình',
        'images.*' => 'ảnh công trình',
    ],
];
