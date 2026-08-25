@php
    $firearmTotal = (float) $par->firearm_unit_cost * (int) $par->firearm_quantity;
    $ammoTotal = (float) $par->ammunition_unit_cost * (int) $par->ammunition_quantity;

    $total = $firearmTotal + $ammoTotal;
    $tax = $total * 0.0176;
    $net = $total - $tax;

    $personnel = $par->personnel;

    /*
    |--------------------------------------------------------------------------
    | SIGNATURE NORMALIZER
    |--------------------------------------------------------------------------
    | Supports:
    | - complete data:image/png;base64,...
    | - raw base64
    | - /storage/... paths
    | - http(s) URLs
    */

    $normalizeSignature = function ($signature) {
        if (!$signature) {
            return null;
        }

        $signature = trim(trim($signature), "\"'");

        if ($signature === '') {
            return null;
        }

        if (
            str_starts_with($signature, 'data:image/') ||
            str_starts_with($signature, 'http://') ||
            str_starts_with($signature, 'https://') ||
            str_starts_with($signature, '/')
        ) {
            return $signature;
        }

        if (
            str_starts_with($signature, 'storage/') ||
            str_starts_with($signature, 'images/')
        ) {
            return '/' . $signature;
        }

        return 'data:image/png;base64,' . preg_replace('/\s+/', '', $signature);
    };

    /*
    |--------------------------------------------------------------------------
    | RECEIVED BY SIGNATURE
    |--------------------------------------------------------------------------
    | Priority:
    | 1. PAR receiver signature
    | 2. Personnel signature captured during New Registration
    */

    $receivedSignatureSrc = $normalizeSignature(
        $par->receiver_signature
            ?: optional($personnel)->signature
    );

    /*
    |--------------------------------------------------------------------------
    | APPROVED BY / ISSUED BY SIGNATURES
    |--------------------------------------------------------------------------
    */

    $localSignature = function ($fileName) {
        $path = public_path('images/' . $fileName);
        if (!is_file($path)) return null;
        $mime = mime_content_type($path) ?: 'image/png';
        return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path));
    };

    $approvedSignatureSrc = $normalizeSignature($par->approved_by_signature)
        ?: $localSignature('SINGUEO EVAGELINE.png');

    $issuedSignatureSrc = $normalizeSignature($par->issued_by_signature)
        ?: $localSignature('ROSEMARIE VILBAR.png');

    $items = $par->equipment_items ?: [
        '4 pcs Back Straps',
        '4 pcs Magazine (17 rds Cap)',
        '1 set Cleaning Kit',
        '1 pc Speed Loader',
        '1 pc User’s Manual',
        '1 pc Gun Case',
        '1 pc Holster w/Hanger',
        '1 pc Magazine Pouch 3 magazine Capacity',
    ];

    $logoPath = public_path('images/logo.png');

    $logoData = is_file($logoPath)
        ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
        : '';

    /*
    |--------------------------------------------------------------------------
    | FIREARM MAKE / MODEL
    |--------------------------------------------------------------------------
    */

    $lastSegment = trim(
        collect(explode(',', $par->firearm))->last()
    );

    $segmentParts = preg_split('/\s+/', $lastSegment);

    $model = count($segmentParts) > 1
        ? array_pop($segmentParts)
        : '';

    $make = implode(' ', $segmentParts) ?: $lastSegment;

    /*
    |--------------------------------------------------------------------------
    | FOOTER LOGOS
    |--------------------------------------------------------------------------
    */

    $footerLogos = [
        'pgs'  => public_path('images/footer/pgs.png'),
        'seal' => public_path('images/footer/seal.png'),
        'ac'   => public_path('images/footer/ac.png'),
        'atr'  => public_path('images/footer/atr.png'),
    ];

    $footerLogoData = [];

    foreach ($footerLogos as $key => $path) {
        $footerLogoData[$key] = is_file($path)
            ? 'data:image/png;base64,' . base64_encode(file_get_contents($path))
            : null;
    }
@endphp

<p class="par-number">
    <strong>PAR No.:</strong>
    <span>{{ $par->par_number }}</span>
</p>

@if($logoData)
    <img class="par-watermark" src="{{ $logoData }}" alt="">
@endif

<table class="par-items">
    <thead>
        <tr>
            <th>Quantity</th>
            <th>Unit</th>
            <th>Description</th>
            <th>SERIAL NUMBER</th>
            <th>UNIT COST</th>
        </tr>
    </thead>

    <tbody>
        <tr class="main-item">
            <td>{{ $par->firearm_quantity }}</td>
            <td>ea</td>

            <td class="description">
                {{ $par->firearm }} with<br>
                the following accessories:

                <ul>
                    @foreach($items as $equipment)
                        <li>{{ $equipment }}</li>
                    @endforeach
                </ul>

                @if($par->remarks)
                    <div class="remarks">
                        {{ $par->remarks }}
                    </div>
                @endif
            </td>

            <td>
                {{ $par->firearm_serial_number ?: '—' }}
            </td>

            <td>
                ₱ {{ number_format((float) $par->firearm_unit_cost, 2) }}
            </td>
        </tr>

        @if($par->ammunition_quantity > 0)
            <tr>
                <td>{{ $par->ammunition_quantity }}</td>
                <td>rds</td>

                <td class="description">
                    Ctg. 9mm, Ball
                    (₱{{ number_format((float) $par->ammunition_unit_cost, 2) }}/rd)
                </td>

                <td>—</td>

                <td>
                    ₱ {{ number_format($ammoTotal, 2) }}
                </td>
            </tr>
        @endif

        <tr class="total">
            <td colspan="4">TOTAL</td>
            <td>₱ {{ number_format($total, 2) }}</td>
        </tr>

        <tr class="total">
            <td colspan="4">LESS: Withholding Tax (1.76%)</td>
            <td>₱ {{ number_format($tax, 2) }}</td>
        </tr>

        <tr class="total">
            <td colspan="4">NET TOTAL</td>
            <td>₱ {{ number_format($net, 2) }}</td>
        </tr>
    </tbody>
</table>

<table class="par-details">
    <tr>
        <td>
            <p>
                <strong>Make:</strong>
                <span>{{ strtoupper($make) }}</span>
            </p>

            <p>
                <strong>Model:</strong>
                <span>{{ $par->firearm }}</span>
            </p>

            <p>
                <strong>Serial Number:</strong>
                <span>{{ $par->firearm_serial_number ?: '—' }}</span>
            </p>
        </td>

        <td class="approval">
            <strong>APPROVED:</strong>

            <div class="signature-space">
                @if($approvedSignatureSrc)
                    <img
                        src="{{ $approvedSignatureSrc }}"
                        alt="Approved signature"
                    >
                @endif
            </div>

            <b>
                {{ strtoupper($par->approved_by ?: 'MS EVANGELINE M SINGUEO, Ph.D.') }}
            </b>

            <small>
                Chief APAO, PA
            </small>

            <p>
                Date Approved:
                <span>
                    {{ $par->issued_date?->format('M d, Y') }}
                </span>
            </p>
        </td>
    </tr>
</table>

<table class="par-signatures">
    <tr>
        <td>
            <h3>RECEIVED BY</h3>

            <div class="receiver-signature-row">
                <span class="receiver-signature-label">
                    SIGNATURE:
                </span>

                <span class="signature-line">
                    @if($receivedSignatureSrc)
                        <img
                            src="{{ $receivedSignatureSrc }}"
                            alt="Receiver signature"
                        >
                    @endif
                </span>
            </div>

            <b>
                {{ strtoupper(
                    trim(
                        (optional($personnel)->rank ? optional($personnel)->rank . ' ' : '') .
                        (optional($personnel)->full_name ?? '')
                    )
                ) }}
            </b>

            <small>
                (RANK) (NAME) (MI) (LNAME) (AFPSN) (BR of SVC)
            </small>

            <p>
                Unit Assignment:
                <strong>
                    {{ $par->unit ?: '—' }}
                </strong>
            </p>

            <p>
                Date of Birth:
                <strong>
                    {{
                        optional($personnel)->date_of_birth
                            ? \Carbon\Carbon::parse($personnel->date_of_birth)->format('d F Y')
                            : '—'
                    }}
                </strong>
            </p>

            <p>
                Valid up to:
                <strong>
                    {{ $par->valid_until?->format('M d, Y') ?: '—' }}
                </strong>
            </p>
        </td>

        <td>
            <h3>ISSUED BY</h3>

            <div class="signature-space">
                @if($issuedSignatureSrc)
                    <img
                        src="{{ $issuedSignatureSrc }}"
                        alt="Issuer signature"
                    >
                @endif
            </div>

            <b>
                {{ strtoupper($par->issued_by ?: 'MS ROSEMARIE O VILBAR') }}
            </b>

            <small>
                Signature Over Printed Name
            </small>

            <p class="office">
                <strong>
                    Chief, PAOGS, APAO PA
                </strong>
                <br>
                Position/Office
            </p>

            <p>
                Date Issued:
                <strong>
                    {{ $par->issued_date?->format('M d, Y') }}
                </strong>
            </p>
        </td>
    </tr>
</table>

@if($par->previousPar)
    <p class="replacement-note">
        Replacement for {{ $par->previousPar->par_number }}
        — {{ $par->replacement_reason }}
    </p>
@endif

<footer>
    <div class="footer-badges">
        @if($footerLogoData['pgs'])
            <img src="{{ $footerLogoData['pgs'] }}" alt="PGS">
        @else
            <span class="badge-fallback">PGS</span>
        @endif

        @if($footerLogoData['seal'])
            <img src="{{ $footerLogoData['seal'] }}" alt="">
        @endif

        @if($footerLogoData['ac'])
            <img src="{{ $footerLogoData['ac'] }}" alt="AC">
        @else
            <span class="badge-fallback">AC</span>
        @endif
    </div>

    <strong>
        HONOR.PATRIOTISM. DUTY.
    </strong>

    <div class="footer-badges footer-badges-right">
        @if($footerLogoData['atr'])
            <img src="{{ $footerLogoData['atr'] }}" alt="atr">
        @else
            <span class="badge-fallback">atr</span>
        @endif

        <span>
            ISO 9001:2015<br>
            CERTIFIED
        </span>
    </div>
</footer>
