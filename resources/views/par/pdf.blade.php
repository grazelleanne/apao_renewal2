<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $par->par_number }}</title>
    @include('par._receipt_styles')
    <style>@page{margin:8mm}body{margin:0}.par-receipt{border:0;min-height:auto;padding:7mm}</style>
</head>
<body>@include('par._receipt')</body>
</html>