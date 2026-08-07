<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $par->par_number }} · Property Acknowledgement Receipt</title>
    @include('par._receipt_styles')
    <style>
        body{margin:0;background:#171c22;font-family:Arial,sans-serif}.document-toolbar{position:sticky;top:0;z-index:5;display:flex;justify-content:space-between;align-items:center;padding:12px 20px;background:#11161d;color:#fff;border-bottom:1px solid #303947}.document-actions{display:flex;gap:9px}.document-actions a,.document-actions button{border:1px solid #556174;border-radius:5px;background:#1b2430;color:#fff;padding:9px 15px;font-weight:700;cursor:pointer;text-decoration:none}.document-actions .primary{background:#b89418;border-color:#d0a91e;color:#fff}.document-page{padding:22px}@media(max-width:640px){.document-toolbar{align-items:flex-start;gap:10px;flex-direction:column}.document-page{padding:8px}.document-actions{width:100%}.document-actions>*{flex:1}}
    </style>
</head>
<body>
<div class="document-toolbar no-print">
    <div><strong>View PAR</strong> · {{ $par->par_number }}</div>
    <div class="document-actions">
        <a href="{{ route('staff.par.pdf', $par) }}">Generate PDF</a>
        <button class="primary" type="button" onclick="window.print()">Print PAR</button>
    </div>
</div>
<main class="document-page">@include('par._receipt')</main>
</body>
</html>