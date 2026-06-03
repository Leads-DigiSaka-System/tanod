<x-mail::message>
# {{ $reportName }} — {{ $interval }} Report

Hi {{ $userName }},

Your scheduled **{{ $reportName }}** report is ready. The Excel file is attached below.

<x-mail::button url="{{ config('app.url') }}/reports">
View in Tanod
</x-mail::button>

Thanks,<br>
{{ config('app.name') }} Fleet Management
</x-mail::message>
