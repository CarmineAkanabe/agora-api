<?php

namespace App\Services;

use App\Enums\VerificationStatus;
use App\Mail\VerificationApprovedMail;
use App\Mail\VerificationRejectedMail;
use App\Models\StudentProfile;
use App\Models\User;
use App\Notifications\VerificationApprovedNotification;
use App\Notifications\VerificationRejectedNotification;
use Mail;

class StudentVerificationService
{
    /**
     * Create a new class instance.
     */
    public function __construct(protected NotificationService $notificationService) {}

    public function createOrUpdateProfile(User $user, array $data): StudentProfile
    {
        $idCardPath = isset($data['id_card'])
            ? $data['id_card']->store('id_cards', 'public')
            : $user->studentProfile->id_card_path;

        $profilePicturePath = isset($data['profile_picture'])
            ? $data['profile_picture']->store('profile_pictures', 'public')
            : $user->studentProfile?->profile_picture;

        return StudentProfile::updateOrCreate(
            ['user_id' => $user->id],
            [
                'matricule'           => $data['matricule'],
                'school'              => $data['school'],
                'department'          => $data['department'],
                'level'               => $data['level'],
                'phone'               => $data['phone'],
                'whatsapp_number'     => $data['whatsapp_number'],
                'id_card_path'        => $idCardPath,
                'profile_picture'     => $profilePicturePath,
                'verification_status' => VerificationStatus::PENDING,
                'verified_at'         => null,
                'verified_by'         => null,
            ]
        );
    }

    public function approve(StudentProfile $profile, User $admin): void
    {
        $profile->update([
            'verification_status' => VerificationStatus::APPROVED,
            'verified_at'         => now(),
            'verified_by'         => $admin->id,
        ]);

        $this->notificationService->verificationApproved($profile->user);

        // $profile->user->notify(new VerificationApprovedNotification());
        Mail::to($profile->user->email)->queue(new VerificationApprovedMail($profile->user));
    }

    public function reject(StudentProfile $profile, User $admin, ?string $reason): void
    {
        $profile->update([
            'verification_status' => VerificationStatus::REJECTED,
            'verified_at'         => null,
            'verified_by'         => $admin->id,
        ]);

        $this->notificationService->verificationRejected($profile->user, $reason);

        // $profile->user->notify(new VerificationRejectedNotification($reason));
        Mail::to($profile->user->email)->queue(new VerificationRejectedMail($profile->user, $reason));
    }
}
