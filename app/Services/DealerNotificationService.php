<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Dealer;
use App\Models\DealerNotification;
use App\Models\Project;
use Illuminate\Support\Carbon;

class DealerNotificationService
{
    public function create(array $attributes): DealerNotification
    {
        return DealerNotification::query()->create($attributes);
    }

    public function notifyNewCustomerContact(Customer $customer, ?string $notes = null): ?DealerNotification
    {
        $customer->loadMissing(['dealer', 'systemType']);

        if (! $customer->dealer instanceof Dealer) {
            return null;
        }

        $systemTypeNameVi = $customer->systemType?->name_vi
            ?: $customer->systemType?->name
            ?: 'hệ chưa xác định';
        $systemTypeNameEn = $customer->systemType?->name_en
            ?: $customer->systemType?->name_vi
            ?: $customer->systemType?->name
            ?: 'unspecified system';
        $sentAt = $customer->created_at instanceof Carbon ? $customer->created_at : now();

        $contentVi = collect([
            "{$customer->name} gửi yêu cầu tư vấn {$systemTypeNameVi}.",
            filled($notes) ? "Nội dung: {$notes}." : null,
            filled($customer->contact_time) ? "Thời gian mong muốn liên hệ: {$customer->contact_time}." : null,
            'Thời gian gửi: '.$sentAt->format('d/m/Y H:i').'.',
        ])->filter()->implode(' ');

        $contentEn = collect([
            "{$customer->name} submitted a consultation request for {$systemTypeNameEn}.",
            filled($notes) ? "Message: {$notes}." : null,
            filled($customer->contact_time) ? "Preferred contact time: {$customer->contact_time}." : null,
            'Submitted at: '.$sentAt->format('Y-m-d H:i').'.',
        ])->filter()->implode(' ');

        return $this->create([
            'dealer_id' => $customer->dealer_id,
            'type' => DealerNotification::TYPE_CUSTOMER_CONTACT,
            'status' => DealerNotification::STATUS_UNREAD,
            'title_vi' => 'Khách hàng mới liên hệ',
            'title_en' => 'New customer inquiry',
            'content_vi' => $contentVi,
            'content_en' => $contentEn,
            'payload' => [
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'customer_phone' => $customer->phone,
                'system_type_id' => $customer->system_type_id,
                'system_type_name_vi' => $customer->systemType?->name_vi ?: $customer->systemType?->name,
                'system_type_name_en' => $customer->systemType?->name_en ?: $customer->systemType?->name_vi ?: $customer->systemType?->name,
                'contact_time' => $customer->contact_time,
                'notes' => $notes,
                'submitted_at' => $sentAt->toDateTimeString(),
            ],
            'related_type' => Customer::class,
            'related_id' => $customer->id,
        ]);
    }

    public function notifyProjectStatusChanged(Project $project): ?DealerNotification
    {
        $project->loadMissing('dealer');

        if (! $project->dealer instanceof Dealer) {
            return null;
        }

        if (! in_array($project->status, ['approved', 'rejected'], true)) {
            return null;
        }

        $eventAt = $project->status === 'approved'
            ? ($project->approved_at ?: $project->updated_at ?: now())
            : ($project->updated_at ?: now());

        $titleVi = $project->status === 'approved'
            ? 'Công trình được duyệt'
            : 'Công trình bị từ chối';
        $titleEn = $project->status === 'approved'
            ? 'Project approved'
            : 'Project rejected';

        $contentVi = $project->status === 'approved'
            ? "Công trình {$project->title} đã được duyệt vào ".$eventAt->format('d/m/Y H:i').'.'
            : "Công trình {$project->title} đã bị từ chối vào ".$eventAt->format('d/m/Y H:i').'.';
        $contentEn = $project->status === 'approved'
            ? "Project {$project->title} was approved on ".$eventAt->format('Y-m-d H:i').'.'
            : "Project {$project->title} was rejected on ".$eventAt->format('Y-m-d H:i').'.';

        if ($project->status === 'rejected' && filled($project->rejection_reason)) {
            $contentVi .= " Lý do: {$project->rejection_reason}.";
            $contentEn .= " Reason: {$project->rejection_reason}.";
        }

        return $this->create([
            'dealer_id' => $project->dealer_id,
            'type' => $project->status === 'approved'
                ? DealerNotification::TYPE_PROJECT_APPROVED
                : DealerNotification::TYPE_PROJECT_REJECTED,
            'status' => DealerNotification::STATUS_UNREAD,
            'title_vi' => $titleVi,
            'title_en' => $titleEn,
            'content_vi' => $contentVi,
            'content_en' => $contentEn,
            'payload' => [
                'project_id' => $project->id,
                'project_title' => $project->title,
                'project_status' => $project->status,
                'rejection_reason' => $project->rejection_reason,
                'event_at' => $eventAt->toDateTimeString(),
            ],
            'related_type' => Project::class,
            'related_id' => $project->id,
        ]);
    }
}
