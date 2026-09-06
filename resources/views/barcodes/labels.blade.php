<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Label Barcode Produk</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 6mm 6mm 6mm 6mm;
        }

        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 0;
            color: #1a1a1a;
            font-size: 8pt;
        }

        .labels-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .labels-table td {
            width: 25%;
            vertical-align: top;
            padding: 2.5mm 2mm;
            box-sizing: border-box;
        }

        .label-card {
            border: 1px dashed #777777;
            border-radius: 4px;
            padding: 3.5mm 2.5mm;
            text-align: center;
            background: #ffffff;
            box-sizing: border-box;
            height: 31mm;
            overflow: hidden;
        }

        .tenant-title {
            font-size: 6pt;
            font-weight: bold;
            color: #666666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 1.5px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .product-name {
            font-size: 7.5pt;
            font-weight: bold;
            color: #000000;
            line-height: 1.15;
            height: 16px;
            overflow: hidden;
            margin-bottom: 2px;
            display: block;
        }

        .barcode-container {
            margin: 1.5px 0 1px 0;
            text-align: center;
        }

        .barcode-img {
            height: 24px;
            max-width: 95%;
            display: block;
            margin: 0 auto;
        }

        .barcode-code {
            font-family: 'Courier New', Courier, monospace;
            font-size: 7.5pt;
            font-weight: bold;
            letter-spacing: 1.5px;
            color: #111111;
            margin-top: 1px;
        }

        .product-price {
            font-size: 8pt;
            font-weight: bold;
            color: #000000;
            margin-top: 2px;
        }

        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    @if(empty($labels))
        <div style="text-align: center; padding: 40px; font-size: 12pt; color: #666;">
            Tidak ada produk atau barcode yang dapat dicetak.
        </div>
    @else
        <table class="labels-table">
            <tbody>
                @php
                    $chunks = array_chunk($labels, 4);
                @endphp

                @foreach($chunks as $rowIndex => $row)
                    <tr>
                        @foreach($row as $label)
                            <td>
                                <div class="label-card">
                                    <div class="tenant-title">{{ $label['tenant_name'] ?? $tenantName }}</div>
                                    <div class="product-name">{{ $label['name'] }}</div>
                                    
                                    <div class="barcode-container">
                                        <img class="barcode-img" src="data:image/png;base64,{{ $label['barcode_base64'] }}" alt="Barcode" />
                                        <div class="barcode-code">{{ $label['barcode'] }}</div>
                                    </div>

                                    @if($includePrice)
                                        <div class="product-price">Rp {{ number_format($label['price'], 0, ',', '.') }}</div>
                                    @endif
                                </div>
                            </td>
                        @endforeach

                        {{-- Isi kolom kosong jika baris terakhir kurang dari 4 item --}}
                        @for($k = count($row); $k < 4; $k++)
                            <td></td>
                        @endfor
                    </tr>

                    {{-- Page break setiap 8 baris (32 label per halaman) --}}
                    @if(($rowIndex + 1) % 8 === 0 && ($rowIndex + 1) < count($chunks))
                        </tbody>
                        </table>
                        <div class="page-break"></div>
                        <table class="labels-table">
                        <tbody>
                    @endif
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
