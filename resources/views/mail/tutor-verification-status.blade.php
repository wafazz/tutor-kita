<x-mail::message>
# Hello, {{ $tutor->name }}!

@if($status === 'verified')
Great news! Your tutor account on **TutorHUB** has been **approved** by our HQ team.

You can now log in to your dashboard and start receiving job assignments from students in your area.

<x-mail::button :url="config('app.url') . '/tutor/dashboard'">
Go to Dashboard
</x-mail::button>

Here's what you can do next:
- **Complete your profile** — add your subjects, bio, and availability
- **Wait for job offers** — HQ will match you with students
- **Accept jobs** — review and accept tutor requests

@else
We regret to inform you that your tutor application on **TutorHUB** has **not been approved** at this time.

If you believe this was a mistake or would like more information, please contact our support team.

<x-mail::button :url="config('app.url')">
Visit TutorHUB
</x-mail::button>
@endif

Thank you,<br>
**TutorHUB Team**
</x-mail::message>
