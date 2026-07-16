<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">

<style>
*{
    margin:0;
    padding:0;
    /* box-sizing:border-box; */
}

body{
    font-family: DejaVu Sans, sans-serif;
    font-size:8px;
    color:#222;
}

.page{
    border:2px solid #000;
    padding:4px;
}

.inner-content{
    padding:2px;
}

table{
    width:100%;
    border-collapse:collapse;
}

.green-header{
    background:#24552d;
    color:white;
    font-weight:bold;
    text-align:center;
    padding:4px;
    font-size:8px;
    margin-top:5px;
    margin-bottom:4px;
}

.line{
    border-bottom:1px solid #444;
    height:12px;
}

.label{
    width:110px;
    font-weight:bold;
    vertical-align:bottom;
    padding-right:4px;
    font-size:8px;
}

.value{
    border-bottom:1px solid #444;
    height:13px;
    font-size:8px;
}

.small{
    font-size:8px;
}

.logo-row{
    display:flex;
    align-items:right;
    gap:4px;
}
.logo{
    width:80px;
}
.logo-sm{
    width:80px;
}

.header-title{
    text-align:center;
    font-size:20px;
    font-weight:bold;
    letter-spacing:2px;
    margin-bottom:4px;
}

.header-right{
    text-align:right;
}

.report-meta{
    font-size:9px;
}

.report-no{
    color:#a00000;
    font-size:13px;
    font-weight:bold;
    font-family: 'Times New Roman', Times, serif;
}

.header-line{
    border-bottom:1px solid #444;
    display:inline-block;
    min-width:70px;
    margin-left:4px;
    font-size:8px;
}
.parts-table{
    width:100%;
    border-collapse:collapse;
    font-size:7px;
    margin-top:2px;
}

.parts-table th{
    background:#24552d;
    color:white;
    padding:2px;
    border:1px solid #333;
    font-size:6px;
}

.parts-table td{
    border:1px solid #444;
    padding:2px;
    height:10px;
    font-size:6px;
}

.box{
    display:inline-block;
    width:10px;
    height:10px;
    border:1px solid #000;
    text-align:center;
    line-height:10px;
    font-size:10px;
    margin-right:3px;
    vertical-align:middle;
}

.cb-label{
    font-size:8px;
    line-height:12px;
    vertical-align:middle;
}

</style>

</head>

<body>

<div class="page">

<div class="inner-content">

<!-- ================= HEADER ================= -->

<div class="header-title">LEADSTECH CORPORATION</div>

<table>

<tr>

<td width="45%" style="vertical-align:middle;text-align:center;">

<div class="logo-row" style="justify-content:center;">

@php
$logo1 = public_path('images/leads.png');
$logo2 = public_path('images/leads-logo.png');
@endphp

@if(file_exists($logo1))
<img src="{{ $logo1 }}" class="logo">
@endif

@if(file_exists($logo2))
<img src="{{ $logo2 }}" class="logo-sm">
@endif

</div>

</td>

<td width="55%" style="vertical-align:middle;">

<div class="header-right">

<table style="width:100%;">

<tr>

<td style="text-align:right;">

<span class="report-meta"><b>SERVICE REPORT</b></span>

</td>

<td style="text-align:left;  padding-left:12px;">

<span class="report-no">No.    {{ $report->id }}</span>

</td>

</tr>

<tr>

<td style="text-align:right;padding-top:2px;">

<span class="report-meta"><b>DATE</b></span>

</td>

<td style="text-align:left;padding-top:2px;">

<span class="header-line">{{ now()->format('m/d/Y') }}</span>

</td>

</tr>

</table>

</div>

</td>

</tr>

</table>

<!-- ================= CUSTOMER INFO ================= -->

<div class="green-header">

Customer Information

</div>

<table>

<tr>

<td class="label">

Customer Name

</td>

<td class="value">

{{ $report->ticket?->tractor?->name ?? $report->customer_name ?? '' }}

</td>

</tr>

<tr>

<td colspan="2" style="height:5px;"></td>

</tr>

<tr>

<td class="label">

Address

</td>

<td class="value">

{{ $report->customer_address ?? '' }}

</td>

</tr>

<tr>

<td colspan="2" style="height:5px;"></td>

</tr>

<tr>

<td class="label">

Contact No.

</td>

<td class="value">

{{ $report->contact_no ?? '' }}

</td>

</tr>

</table>
<!-- ================= UNIT INFORMATION ================= -->

<div class="green-header">
    Unit Information
</div>

<table>

    <tr>

        <td width="16%" class="label">
            Type of Unit
        </td>

        <td width="34%" class="value">
            Four Wheel Tractor
        </td>

        <td width="16%" class="label" style="padding-left:10px;">
            Machine Hours
        </td>

        <td width="34%" class="value">
            {{ $report->machine_hours ?? '' }}
        </td>

    </tr>

    <tr>
        <td colspan="4" style="height:5px;"></td>
    </tr>

    <tr>

        <td class="label">
            Model
        </td>

        <td class="value">
            {{ $report->tractor_model ?? '' }}
        </td>

        <td class="label" style="padding-left:10px;">
            Serial Number
        </td>

        <td class="value">
            {{ $report->serial_number ?? '' }}
        </td>

    </tr>

</table>


<!-- ================= TYPE OF SERVICE ================= -->

@php

$performed = $report->service_performed ?? [];

if(!is_array($performed)){
    $performed = [];
}

$isWarranty = strtolower($report->warranty_type ?? '') == 'yes';

// Auto-detect type of job from subject
$subjectLower = strtolower($report->subject ?? '');
$isRepair = str_starts_with($subjectLower, 'repair');
$isChangeOil = str_contains($subjectLower, 'pms') || str_contains($subjectLower, 'change oil');
$isTraining = str_starts_with($subjectLower, 'training') || str_starts_with($subjectLower, 'request training');

@endphp


<div style="margin-top:5px;">
<div style="margin-top:5px;">
    Type of Service
</div>

<table>

<tr>


<td width="45%">

<span class="box">{{ $isWarranty ? '✓' : '' }}</span><span class="cb-label"> Warranty</span>

</td>

<td width="55%">

<span class="box">{{ !$isWarranty ? '✓' : '' }}</span><span class="cb-label"> Non-Warranty</span>

</td>

</tr>

<tr>

<td style="padding-top:4px;">

Warranty Start

</td>

<td style="padding-top:4px;">

Warranty Expiration

</td>

</tr>

<tr>

<td>

<div class="line">

{{ $report->warranty_start ?? '' }}

</div>

</td>

<td>

<div class="line">

{{ $report->warranty_end ?? '' }}

</div>

</td>

</tr>

</table>

</div>


<!-- ================= TYPE OF JOB ================= -->

<div class="green-header">
    Type of Job
</div>

<table style="margin-top:4px;">

<tr>

<td width="33%">

<span class="box">{{ in_array('checkup',$performed) ? '✓' : '' }}</span><span class="cb-label"> Check up</span>

</td>

<td width="33%">

<span class="box">{{ $isChangeOil ? '✓' : '' }}</span><span class="cb-label"> Change Oil</span>

</td>

<td width="34%">

<span class="box">{{ $isTraining ? '✓' : '' }}</span><span class="cb-label"> Training</span>

</td>

</tr>

<tr>

<td style="padding-top:4px;">

<span class="box">{{ in_array('troubleshoot',$performed) ? '✓' : '' }}</span><span class="cb-label"> Troubleshoot</span>

</td>

<td style="padding-top:4px;">

<span class="box">{{ in_array('adjustment',$performed) ? '✓' : '' }}</span><span class="cb-label"> Adjustment</span>

</td>

<td style="padding-top:4px;">

<span class="box">{{ $isRepair ? '✓' : '' }}</span><span class="cb-label"> Repair</span>

</td>

</tr>

</table>
<!-- ===================================================== -->
<!-- SERVICE AND PARTS INFORMATION -->
<!-- ===================================================== -->

<div class="green-header">
    Service and Parts Information
</div>

<table style="margin-top:4px;">

<tr>

<td width="26%">
<b>Customer Reported Date</b>
</td>

<td width="24%">
<div class="line">
{{ $report->ticket?->created_at?->format('m/d/Y') ?? '' }}
</div>
</td>

<td width="26%" style="padding-left:8px;">
<b>Customer Reported Time of Failure</b>
</td>

<td width="24%">
<div class="line">
{{ $report->ticket?->reported_date?->format('m/d/Y') ?? '' }}
</div>
</td>

</tr>

</table>

<div style="height:6px;"></div>

<b>Customer Complaint / Claim</b>

<div class="line" style="margin-top:4px;">
{{ $report->subject ?? '' }}
</div>

<div class="line"></div>

<div style="height:5px;"></div>

<b>Unit Detailed Activity Before Failure</b>

<div class="line" style="margin-top:4px;">
{{ $report->ticket?->description ?? '' }}
</div>

<div class="line"></div>

<div style="height:5px;"></div>

<table style="margin-top:4px;width:auto;">

<tr>

<td style="white-space:nowrap;padding-right:2px;">
<b>Repair Start Date:</b>
</td>

<td>
<div class="line" style="width:120px;">
{{ $report->repair_start_date ?? '' }}
</div>
</td>

</tr>

<tr>

<td style="white-space:nowrap;padding-right:2px;">
<b>Repair End Date:</b>
</td>

<td>
<div class="line" style="width:120px;">
{{ $report->repair_end_date ?? '' }}
</div>
</td>

</tr>

</table>

<div style="height:5px;"></div>

<b>Findings</b>

<div class="line" style="margin-top:4px;">
{{ $report->findings ?? '' }}
</div>

<div class="line"></div>

<div class="line"></div>

<div style="height:5px;"></div>

<b>Correction and Job Done</b>

<div class="line" style="margin-top:4px;">
{{ $report->job_done ?? '' }}
</div>

<div class="line"></div>

<div class="line"></div>

<div style="height:5px;"></div>

<b>Recommendation</b>

<div class="line" style="margin-top:4px;">
{{ $report->recommendation ?? '' }}
</div>

<div class="line"></div>

<div class="line"></div>


<!-- ===================================================== -->
<!-- SPARE PARTS -->
<!-- ===================================================== -->

<div class="green-header">
    Spare Parts Used
</div>

<table class="parts-table">

<thead>

<tr>

<th width="6%">No.</th>

<th width="32%">Part Name</th>

<th width="18%">Part No.</th>

<th width="10%">Qty</th>

<th width="14%">Cost</th>

<th width="20%">Remarks</th>

</tr>

</thead>

<tbody>

@php

$total = 0;

@endphp

@if(!empty($report->parts_details))

@foreach($report->parts_details as $index=>$part)

@php

$qty = $part['quantity'] ?? 1;

$cost = $part['amount'] ?? 0;

$total += $qty * $cost;

@endphp

<tr>

<td align="center">

{{ $index+1 }}

</td>

<td>

{{ $part['name'] ?? '' }}

</td>

<td>

{{ $part['part_number'] ?? '' }}

</td>

<td align="center">

{{ $qty }}

</td>

<td align="right">

{{ number_format($cost,2) }}

</td>

<td>

{{ $part['remarks'] ?? '' }}

</td>

</tr>

@endforeach

@endif


@for($i=(count($report->parts_details ?? []));$i<5;$i++)

<tr>

<td>&nbsp;</td>

<td></td>

<td></td>

<td></td>

<td></td>

<td></td>

</tr>

@endfor

</tbody>

</table>

<div style="height:8px;"></div>


<!-- ===================================================== -->
<!-- COST -->
<!-- ===================================================== -->
<div>
    STATUS
</div>

<table style="margin-top:6px;">

<tr>

<td width="25%" background="#4ebd58" style="padding:4px;">
<span class="box">{{ ($report->work_status ?? '')=="Completed" ? "✓" : "" }}</span><span class="cb-label"> Completed</span>
</td>

<td width="25%" background="#ad3838" style="padding:2px;">
<span class="box">{{ ($report->work_status ?? '')=="Pending" ? "✓" : "" }}</span><span class="cb-label"> Pending</span>
</td>




<td width="25%">
<span class="box">{{ ($report->work_condition ?? '')=="Operational" ? "✓" : "" }}</span><span class="cb-label"> Operational</span>
</td>

<td width="25%">
<span class="box">{{ ($report->work_condition ?? '')=="Non Operational" ? "✓" : "" }}</span><span class="cb-label"> Non Operational</span>
</td>


</tr>
<tr align="center">
    <td></td>
    <td align="left" style="padding-right:4px;">
        Remarks
    </td>
    <td align="left">
        Remarks
    </td>
    <td></td>
</tr>
<tr align="center">
    <td></td>
    <td align="left" style="padding-right:4px;">
        <div style="border-bottom:1px solid #000;height:14px;">
            {{ $report->remarks ?? '' }}
        </div>
    </td>
    <td align="left">
        <div style="border-bottom:1px solid #000;height:14px;">
        </div>
    </td>
    <td></td>
</tr>

</table>
<!-- ===================================================== -->
<!-- ACKNOWLEDGEMENT -->
<!-- ===================================================== -->

<div class="green-header">
    Acknowledgement
</div>

<div style="margin-top:4px;font-size:8px;line-height:12px;">

This is to certify that the above service/repair was performed and verified by the
undersigned. The customer confirms that the work indicated above has been completed
based on the findings and recommendations stated in this report.

</div>

<div style="height:8px;"></div>

<table style="width:100%;">

<tr>

<td width="45%" align="center">

<div style="border-bottom:1px solid #000;height:14px;">
{{ $report->tps->name ?? '' }}
</div>

<div style="margin-top:4px;font-size:8px;">
Technician Name & Signature
</div>

</td>

<td width="10%"></td>

<td width="45%" align="center">

<div style="border-bottom:1px solid #000;height:14px;">
{{ $report->customer_name ?? $report->submitted_by_name ?? '' }}
</div>

<div style="margin-top:4px;font-size:8px;">
Customer / Representative
</div>

</td>

</tr>

<tr >

<td style="padding-top:8px;">

<table style="width:100%;">

<tr  width="45%" align="center">

<td width="15%">



<div style="border-bottom:1px solid #000;height:14px;">
{{ now()->format('m/d/Y') }}
</div>
Date:
</td>

</tr>

</table>

</td>

<td></td>

<td style="padding-top:8px;">

<table style="width:100%;">

<tr width="45%" align="center">

<td width="15%">



<div style="border-bottom:1px solid #000;height:14px;">
{{ now()->format('m/d/Y') }}
</div>
Date:
</td>

</tr>

</table>

</td>

</tr>

</table>

</div>

</div>

</body>

</html>