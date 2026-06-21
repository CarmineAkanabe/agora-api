{{-- resources/views/emails/verification/approved.blade.php --}}
<x-mail::message>
# Welcome to Agora, {{ $user->name }}

Your student profile has been verified successfully.

You can now browse listings, post items for sale, and make purchases on Agora.

<x-mail::button :url="'http://localhost:5173/login'">
Go to Agora
</x-mail::button>

Thanks,
The Agora Team
</x-mail::message>
