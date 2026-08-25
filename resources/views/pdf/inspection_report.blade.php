{{-- resources/views/pdf/inspection_report.blade.php --}}
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <style>
    * { margin:0; padding:0; box-sizing:border-box; }

    body {
      font-family: Arial, sans-serif;
      font-size: 9px;
      color: #000;
      background: #fff;
      padding: 14px 18px;
    }

    .header-table { width:100%; border-collapse:collapse; margin-bottom:4px; }
    .header-table td { vertical-align:middle; }
    .header-logo { width:54px; height:54px; }
    .header-center { text-align:center; padding:0 8px; }
    .header-center p { font-size:8.5px; font-weight:400; line-height:1.55; }
    .header-center p.bold { font-weight:700; font-size:9.5px; }

    .doc-title {
      text-align:center;
      font-size:11.5px;
      font-weight:900;
      text-decoration:underline;
      margin: 7px 0 7px;
      letter-spacing:.02em;
    }

    .info-table { width:100%; border-collapse:collapse; margin-bottom:5px; }
    .info-table td { font-size:9px; padding:2px 4px; vertical-align:middle; }
    .info-lbl { font-weight:700; width:125px; white-space:nowrap; }
    .info-colon { width:10px; text-align:center; }
    .info-val { border-bottom:1px solid #000; }
    .info-spacer { width:14px; }

    .section-header {
      border: 1.5px solid #000;
      font-weight:700;
      font-size:9px;
      padding: 3px 6px;
      margin: 5px 0 4px;
      background: #fff;
      color: #000;
    }

    .personnel-table { width:100%; border-collapse:collapse; margin-bottom:5px; }
    .personnel-table td { font-size:9px; padding:2px 4px; vertical-align:middle; }
    .p-lbl { font-weight:700; width:110px; white-space:nowrap; }
    .p-colon { width:10px; text-align:center; }
    .p-val { border-bottom:1px solid #555; }
    .p-spacer { width:14px; }

    .checklist-wrap { width:100%; border-collapse:collapse; margin-bottom:5px; }
    .checklist-wrap td.half       { width:50%; vertical-align:top; }
    .checklist-wrap td.half-right { width:50%; vertical-align:top; padding-left:5px; }

    .cl { width:100%; border-collapse:collapse; font-size:8px; }
    .cl th {
      background:#e0e0e0;
      border:1px solid #999;
      padding:3px 2px;
      font-size:7.5px;
      text-align:center;
      font-weight:700;
      line-height:1.3;
    }
    .cl th.th-item { text-align:left; padding-left:4px; }
    .cl td { border:1px solid #bbb; padding:2.5px 3px; vertical-align:middle; }
    .cl td.td-num  { text-align:right; width:16px; color:#333; padding-right:3px; }
    .cl td.td-item { text-align:left; font-size:7.8px; }
    .cl td.td-mark { text-align:center; width:30px; }

    .mark-ok    { color:#22a722; font-weight:900; font-size:10px; font-family:Arial; line-height:1; }
    .mark-empty { color:#aaa;    font-weight:400; font-size:9px;  line-height:1; }
    .mark-xx    { color:#cc0000; font-weight:700; font-size:8px;  line-height:1; }

    .remarks-table { width:100%; border-collapse:collapse; margin-bottom:5px; }
    .remarks-table td { font-size:9px; padding:2px 4px; }
    .remarks-lbl { font-weight:700; width:65px; white-space:nowrap; }
    .remarks-val { color:#22a722; font-weight:700; border-bottom:1px solid #000; }

    .sig-table { width:100%; border-collapse:collapse; margin-bottom:5px; }
    .sig-table td {
      width:25%; vertical-align:top; text-align:center;
      padding:4px 5px; border:1px solid #bbb;
    }
    .sig-role {
      font-weight:700; font-size:8px; text-transform:uppercase;
      border-bottom:1px solid #bbb; padding-bottom:2px; margin-bottom:3px;
    }
    .sig-space { height:50px; }
    .sig-img   { max-height:50px; max-width:110px; display:block; margin:0 auto; }
    .sig-name  {
      font-weight:700; font-size:8.5px; text-transform:uppercase;
      border-bottom:1px solid #000; padding-bottom:1px; margin-bottom:2px;
    }
    .sig-sub { font-size:8px; line-height:1.45; }

    .final-table { width:100%; border-collapse:collapse; margin-bottom:5px; }
    .final-table td { border:1px solid #999; padding:5px 8px; vertical-align:top; }
    .final-title {
      font-weight:700; font-size:8.5px; text-transform:uppercase;
      display:block; border-bottom:1px solid #ccc;
      padding-bottom:3px; margin-bottom:5px;
    }

    .chk-row { width:100%; border-collapse:collapse; margin-bottom:4px; }
    .chk-row td { vertical-align:middle; padding:1px 3px; font-size:8.5px; }
    .chk-cell { width:14px; }

    .chk-box {
      display:inline-block;
      width:11px; height:11px;
      border:1.5px solid #333;
      background:#fff;
      font-size:0px;
    }
    .chk-green {
      display:inline-block;
      width:11px; height:11px;
      border:1.5px solid #22a722;
      background:#22a722;
      color:#fff;
      font-size:9px; font-weight:900; font-family:Arial;
      text-align:center; line-height:10px;
    }
    .chk-red {
      display:inline-block;
      width:11px; height:11px;
      border:1.5px solid #cc0000;
      background:#cc0000;
      color:#fff;
      font-size:9px; font-weight:900; font-family:Arial;
      text-align:center; line-height:10px;
    }

    .date-row { width:100%; border-collapse:collapse; margin-bottom:5px; }
    .date-row td { vertical-align:top; padding:2px 3px; font-size:8.5px; }
    .date-icon { width:16px; font-weight:700; font-size:9px; }
    .date-lbl  { font-weight:700; font-size:8px; display:block; }
    .date-val  { font-size:8.5px; display:block; }

    .footer {
      text-align:center; font-size:7.5px; color:#555;
      font-style:italic; border-top:1px solid #ccc;
      padding-top:4px; margin-top:4px;
    }

    @page { margin:0; size: A4 portrait; }
  </style>
</head>
<body>

  {{-- ===================== HEADER ===================== --}}
  @php
    $logo1Path = public_path('images/logo1.png');
    $logo2Path = public_path('images/logo2.png');
    $logo1Data = file_exists($logo1Path) ? base64_encode(file_get_contents($logo1Path)) : '';
    $logo2Data = file_exists($logo2Path) ? base64_encode(file_get_contents($logo2Path)) : '';
  @endphp
  <table class="header-table">
    <tr>
      <td style="width:60px; text-align:left; vertical-align:middle;">
        @if($logo1Data)
          <img class="header-logo" src="data:image/png;base64,{{ $logo1Data }}" alt="4ID Logo">
        @endif
      </td>
      <td class="header-center">
        <p>REPUBLIC OF THE PHILIPPINES</p>
        <p>ARMED FORCES OF THE PHILIPPINES</p>
        <p>PHILIPPINE ARMY</p>
        <p class="bold">4TH INFANTRY (DIAMOND) DIVISION, PA</p>
        <p class="bold">10TH FIELD PROPERTY ACCOUNTABILITY OFFICE (FPAO)</p>
      </td>
      <td style="width:60px; text-align:right; vertical-align:middle;">
        @if($logo2Data)
          <img class="header-logo" src="data:image/png;base64,{{ $logo2Data }}" alt="FPAO Logo">
        @endif
      </td>
    </tr>
  </table>

  {{-- ===================== DOCUMENT TITLE ===================== --}}
  <div class="doc-title">
    INSPECTION REPORT OF SERVICEABLE AND UNSERVICEABLE FIREARMS
  </div>

  {{-- ===================== TOP INFO ===================== --}}
  <table class="info-table">
    <tr>
      <td class="info-lbl">NOMENCLATURE</td>
      <td class="info-colon">:</td>
      <td class="info-val">{{ $p->pistol_nomenclature ?? '' }}</td>
      <td class="info-spacer"></td>
      <td class="info-lbl">MAKE / MODEL</td>
      <td class="info-colon">:</td>
      <td class="info-val">{{ $p->pistol_nomenclature ?? '' }}</td>
    </tr>
    <tr>
      <td class="info-lbl">UNIT</td>
      <td class="info-colon">:</td>
      <td class="info-val">{{ $p->unit ?? '' }}</td>
      <td class="info-spacer"></td>
      <td class="info-lbl">PISTOL SERIAL NUMBER</td>
      <td class="info-colon">:</td>
      <td class="info-val">{{ $p->pistol_serial_number ?? '' }}</td>
    </tr>
    <tr>
      <td class="info-lbl">SERIAL NUMBER (AFP)</td>
      <td class="info-colon">:</td>
      <td class="info-val">{{ $p->afp_serial_number ?? '' }}</td>
      <td class="info-spacer"></td>
      <td class="info-lbl">DATE INSPECTED</td>
      <td class="info-colon">:</td>
      <td class="info-val">{{ $dateToday }}</td>
    </tr>
  </table>

  {{-- ===================== PERSONNEL INFORMATION ===================== --}}
  <div class="section-header">PERSONNEL INFORMATION</div>

  @php
    $fullName = trim(
      ($p->last_name  ?? '') . ', ' .
      ($p->first_name ?? '') . ' ' .
      (isset($p->middle_name) && $p->middle_name
        ? strtoupper(substr($p->middle_name, 0, 1)) . '.'
        : '')
    );
  @endphp

  <table class="personnel-table">
    <tr>
      <td class="p-lbl">NAME</td>
      <td class="p-colon">:</td>
      <td class="p-val">{{ $fullName }}</td>
      <td class="p-spacer"></td>
      <td class="p-lbl">ORGANIZATION / UNIT</td>
      <td class="p-colon">:</td>
      <td class="p-val">{{ $p->unit ?? '' }}</td>
    </tr>
    <tr>
      <td class="p-lbl">RANK</td>
      <td class="p-colon">:</td>
      <td class="p-val">{{ $p->rank ?? '' }}</td>
      <td class="p-spacer"></td>
      <td class="p-lbl">EMAIL</td>
      <td class="p-colon">:</td>
      <td class="p-val">{{ $p->email ?? '' }}</td>
    </tr>
    <tr>
      <td class="p-lbl">DATE OF BIRTH</td>
      <td class="p-colon">:</td>
      <td class="p-val">
        {{ $p->date_of_birth
          ? \Carbon\Carbon::parse($p->date_of_birth)->format('F d, Y')
          : '' }}
      </td>
      <td class="p-spacer"></td>
      <td class="p-lbl">AFP SERIAL #</td>
      <td class="p-colon">:</td>
      <td class="p-val">{{ $p->afp_serial_number ?? '' }}</td>
    </tr>
  </table>

  {{-- ===================== INSPECTION CHECKLIST ===================== --}}
  @php
    $allParts = [
      ['barrel',                             'Barrel'],
      ['slide',                              'Slide'],
      ['recoil_spring_assembly',             'Recoil Spring Assembly'],
      ['firing_pin',                         'Firing Pin'],
      ['spacer_sleeve',                      'Spacer Sleeve'],
      ['firing_pin_spring',                  'Firing Pin Spring'],
      ['spring_cups',                        'Spring Cups'],
      ['firing_pin_safety',                  'Firing Pin Safety'],
      ['firing_pin_safety_spring',           'Firing Pin Safety Spring'],
      ['extractor',                          'Extractor'],
      ['extractor_depressor_plunger',        'Extractor Depressor Plunger'],
      ['extractor_depressor_plunger_spring', 'Extractor Depressor Plunger Spring'],
      ['trigger_loaded_bearing',             'Spring-Loaded Bearing'],
      ['rear_sight',                         'Rear Sight'],
      ['front_sight',                        'Front Sight'],
      ['front_sight_screw',                  'Front Sight Screw'],
      ['frame',                              'Frame'],
      ['magazine_catch_spring',              'Magazine Catch Spring'],
      ['magazine_catch',                     'Magazine Catch'],
      ['slide_lock',                         'Slide Lock'],
      ['slide_cover_plate',                  'Slide Cover Plate'],
      ['connector',                          'Connector'],
      ['trigger_mechanism_housing',          'Trigger Mechanism Housing w/ Ejector'],
      ['trigger',                            'Trigger'],
      ['trigger_spring',                     'Trigger Spring'],
      ['trigger_with_trigger_bar',           'Trigger with Trigger Bar'],
      ['slide_stop_lever',                   'Slide Stop Lever'],
      ['trigger_pin',                        'Trigger Pin'],
      ['trigger_housing_pin',                'Trigger Housing Pin'],
      ['locking_block_pin',                  'Locking Block Pin'],
    ];
    $leftParts  = array_slice($allParts, 0, 15);
    $rightParts = array_slice($allParts, 15, 15);
  @endphp

  <table class="checklist-wrap">
    <tr>
      {{-- LEFT TABLE --}}
      <td class="half">
        <table class="cl">
          <thead>
            <tr>
              <th class="th-item" colspan="2">ITEMS TO INSPECT</th>
              <th>(/) SERVICEABLE</th>
              <th>(XX) REPAIR (N/A)</th>
              <th>(XXX) REPLACE (O) MISSING</th>
              <th>(D) DAMAGE</th>
            </tr>
          </thead>
          <tbody>
            @foreach($leftParts as $i => [$key, $label])
              @php $val = $inspection ? ($inspection->$key ?? 'serviceable') : 'serviceable'; @endphp
              <tr>
                <td class="td-num">{{ $i + 1 }}.</td>
                <td class="td-item">{{ $label }}</td>
                <td class="td-mark">
                  @if($val === 'serviceable')
                    <span class="mark-ok">V</span>
                  @else
                    <span class="mark-empty">O</span>
                  @endif
                </td>
                <td class="td-mark">
                  @if(in_array($val, ['repair','unserviceable']))
                    <span class="mark-xx">XX</span>
                  @else
                    <span class="mark-empty">O</span>
                  @endif
                </td>
                <td class="td-mark">
                  @if(in_array($val, ['replace','missing']))
                    <span class="mark-xx">XXX</span>
                  @else
                    <span class="mark-empty">O</span>
                  @endif
                </td>
                <td class="td-mark">
                  @if($val === 'damaged')
                    <span class="mark-xx">D</span>
                  @else
                    <span class="mark-empty">O</span>
                  @endif
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </td>

      {{-- RIGHT TABLE --}}
      <td class="half-right">
        <table class="cl">
          <thead>
            <tr>
              <th class="th-item" colspan="2">ITEMS TO INSPECT</th>
              <th>(/) SERVICEABLE</th>
              <th>(XX) REPAIR (N/A)</th>
              <th>(XXX) REPLACE (O) MISSING</th>
              <th>(D) DAMAGE</th>
            </tr>
          </thead>
          <tbody>
            @foreach($rightParts as $i => [$key, $label])
              @php $val = $inspection ? ($inspection->$key ?? 'serviceable') : 'serviceable'; @endphp
              <tr>
                <td class="td-num">{{ $i + 16 }}.</td>
                <td class="td-item">{{ $label }}</td>
                <td class="td-mark">
                  @if($val === 'serviceable')
                    <span class="mark-ok">V</span>
                  @else
                    <span class="mark-empty">O</span>
                  @endif
                </td>
                <td class="td-mark">
                  @if(in_array($val, ['repair','unserviceable']))
                    <span class="mark-xx">XX</span>
                  @else
                    <span class="mark-empty">O</span>
                  @endif
                </td>
                <td class="td-mark">
                  @if(in_array($val, ['replace','missing']))
                    <span class="mark-xx">XXX</span>
                  @else
                    <span class="mark-empty">O</span>
                  @endif
                </td>
                <td class="td-mark">
                  @if($val === 'damaged')
                    <span class="mark-xx">D</span>
                  @else
                    <span class="mark-empty">O</span>
                  @endif
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </td>
    </tr>
  </table>

  {{-- ===================== REMARKS ===================== --}}
  <table class="remarks-table">
    <tr>
      <td class="remarks-lbl">REMARKS :</td>
      <td class="remarks-val">
        {{ $inspection->remarks ?? 'Firearm is Serviceable.' }}
      </td>
    </tr>
  </table>

  {{-- ===================== SIGNATORIES ===================== --}}
  @php
    $defaultSignatories = [
      'inspected' => ['name' => 'Rennan F. Maglasang Jr', 'rank' => 'Cpl (OS) PA', 'position' => 'Armaments NCO'],
      'witnessed' => ['name' => 'Marcelito H. Anino', 'rank' => 'MAJ (QMS) PA', 'position' => '901BDE, 9ID, PA'],
      'approved'  => ['name' => 'Wenlie B. Enriola', 'rank' => 'CPT (OS) PA', 'position' => 'CO, Maintenance Coy'],
      'noted'     => ['name' => 'Darrell P. Mariano', 'rank' => 'LTC OS (GSC) PA', 'position' => 'CO, 10FSSU, SPTCOM, PA'],
    ];
    $signatureSrc = function ($storedValue, $fileName) {
      if (is_string($storedValue) && str_starts_with($storedValue, 'data:image/')) {
        return $storedValue;
      }

      $localPath = public_path('images/' . $fileName);
      if (!file_exists($localPath)) {
        return '';
      }

      $mime = match (strtolower(pathinfo($localPath, PATHINFO_EXTENSION))) {
        'jpg', 'jpeg' => 'image/jpeg',
        'gif'         => 'image/gif',
        'webp'        => 'image/webp',
        default       => 'image/png',
      };
      return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($localPath));
    };
    $signatureImages = [
      'inspected' => $signatureSrc(data_get($inspection, 'inspected_by_sig'), 'maglasang.png'),
      'witnessed' => $signatureSrc(data_get($inspection, 'witnessed_by_sig'), 'anino.png'),
      'approved'  => $signatureSrc(data_get($inspection, 'approved_by_sig'), 'enriola.png'),
      'noted'     => $signatureSrc(data_get($inspection, 'noted_by_sig'), 'mariano.png'),
    ];
  @endphp
  <table class="sig-table">
    <tr>
      <td>
        <div class="sig-role">INSPECTED BY:</div>
        @if($signatureImages['inspected'])
          <img class="sig-img" src="{{ $signatureImages['inspected'] }}" alt="Maglasang signature">
        @else
          <div class="sig-space"></div>
        @endif
        <div class="sig-name">{{ data_get($inspection, 'inspected_by_name') ?: $defaultSignatories['inspected']['name'] }}</div>
        <div class="sig-sub">{{ data_get($inspection, 'inspected_by_rank') ?: $defaultSignatories['inspected']['rank'] }}</div>
        <div class="sig-sub">{{ data_get($inspection, 'inspected_by_position') ?: $defaultSignatories['inspected']['position'] }}</div>
      </td>
      <td>
        <div class="sig-role">WITNESSED BY:</div>
        @if($signatureImages['witnessed'])
          <img class="sig-img" src="{{ $signatureImages['witnessed'] }}" alt="Anino signature">
        @else
          <div class="sig-space"></div>
        @endif
        <div class="sig-name">{{ data_get($inspection, 'witnessed_by_name') ?: $defaultSignatories['witnessed']['name'] }}</div>
        <div class="sig-sub">{{ data_get($inspection, 'witnessed_by_rank') ?: $defaultSignatories['witnessed']['rank'] }}</div>
        <div class="sig-sub">{{ data_get($inspection, 'witnessed_by_position') ?: $defaultSignatories['witnessed']['position'] }}</div>
      </td>
      <td>
        <div class="sig-role">APPROVED BY:</div>
        @if($signatureImages['approved'])
          <img class="sig-img" src="{{ $signatureImages['approved'] }}" alt="Enriola signature">
        @else
          <div class="sig-space"></div>
        @endif
        <div class="sig-name">{{ data_get($inspection, 'approved_by_name') ?: $defaultSignatories['approved']['name'] }}</div>
        <div class="sig-sub">{{ data_get($inspection, 'approved_by_rank') ?: $defaultSignatories['approved']['rank'] }}</div>
        <div class="sig-sub">{{ data_get($inspection, 'approved_by_position') ?: $defaultSignatories['approved']['position'] }}</div>
      </td>
      <td>
        <div class="sig-role">NOTED BY:</div>
        @if($signatureImages['noted'])
          <img class="sig-img" src="{{ $signatureImages['noted'] }}" alt="Mariano signature">
        @else
          <div class="sig-space"></div>
        @endif
        <div class="sig-name">{{ data_get($inspection, 'noted_by_name') ?: $defaultSignatories['noted']['name'] }}</div>
        <div class="sig-sub">{{ data_get($inspection, 'noted_by_rank') ?: $defaultSignatories['noted']['rank'] }}</div>
        <div class="sig-sub">{{ data_get($inspection, 'noted_by_position') ?: $defaultSignatories['noted']['position'] }}</div>
      </td>
    </tr>
  </table>

  {{-- ===================== FINAL STATUS ===================== --}}
@php
    $status          = strtolower(trim($inspection->status ?? 'pending'));
    $isServiceable   = $status === 'approved';
    $isNeedsRepair   = $status === 'needs_repair';
    $isUnserviceable = $status === 'unserviceable';

    $approvedCarbon = !empty($inspection->inspected_at)
        ? (function() use ($inspection) {
            try {
                return \Carbon\Carbon::parse($inspection->inspected_at);
            } catch (\Exception $e) {
                return \Carbon\Carbon::now();
            }
          })()
        : \Carbon\Carbon::now();

    $dateApproved = $approvedCarbon->format('d F Y');

    // Always compute next renewal from approved date, no status gate
    $nextRenewal = '-';
    try {
        if (!empty($inspection->next_renewal_date)) {
            $nextRenewal = \Carbon\Carbon::parse($inspection->next_renewal_date)
                ->format('d F Y');
        } elseif (!empty($p->date_of_validity)) {
            $nextRenewal = \Carbon\Carbon::parse($p->date_of_validity)
                ->format('d F Y');
        } else {
            $nextRenewal = $approvedCarbon->copy()->addYear()->format('d F Y');
        }
    } catch (\Exception $e) {
        $nextRenewal = '-';
    }
@endphp

  <table class="final-table">
    <tr>
      <td style="width:42%;">
        <span class="final-title">FINAL INSPECTION STATUS:</span>

        <table class="chk-row">
          <tr>
            <td class="chk-cell">
              @if($isServiceable)
                <span class="chk-green">V</span>
              @else
                <span class="chk-box"></span>
              @endif
            </td>
            <td style="font-weight:{{ $isServiceable ? '700' : '400' }};
                       color:{{ $isServiceable ? '#22a722' : '#000' }};
                       font-size:8.5px;">
              SERVICEABLE
            </td>
          </tr>
        </table>

        <table class="chk-row">
          <tr>
            <td class="chk-cell">
              @if($isNeedsRepair)
                <span class="chk-red">V</span>
              @else
                <span class="chk-box"></span>
              @endif
            </td>
            <td style="font-size:8.5px;">REQUIRES REPAIR</td>
          </tr>
        </table>

        <table class="chk-row">
          <tr>
            <td class="chk-cell">
              @if($isUnserviceable)
                <span class="chk-red">V</span>
              @else
                <span class="chk-box"></span>
              @endif
            </td>
            <td style="font-size:8.5px;">UNSERVICEABLE</td>
          </tr>
        </table>
      </td>

      <td style="width:58%;">
        <table class="date-row">
          <tr>
            <td class="date-icon">[*]</td>
            <td>
              <span class="date-lbl">DATE APPROVED:</span>
              <span class="date-val">{{ $dateApproved }}</span>
            </td>
          </tr>
        </table>
        <table class="date-row">
          <tr>
            <td class="date-icon">[*]</td>
            <td>
              <span class="date-lbl">NEXT RENEWAL DATE:</span>
              <span class="date-val">{{ $nextRenewal }}</span>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>

  {{-- ===================== FOOTER ===================== --}}
  <div class="footer">
    NOTE: This document is digitally generated and valid without handwritten entries.<br>
    This serves as an official record of inspection.
  </div>

</body>
</html>
