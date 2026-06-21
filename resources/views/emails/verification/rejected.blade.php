{{-- resources/views/emails/verification/rejected.blade.php --}}
<x-mail::message>
# Verification Unsuccessful

Hi {{ $user->name }},

Unfortunately your student profile verification was not approved.

@if($reason)
**Reason:** {{ $reason }}
@endif

Please re-submit your profile with a clearer student ID card photo.

<x-mail::button :url="'http://localhost:5173/pending-verification'">
Update Profile
</x-mail::button>

Thanks,
The Agora Team
</x-mail::message>
