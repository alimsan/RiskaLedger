<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Nota Piutang</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
        }
        .header {
            background-color: {{ $nota_color }};
            color: white;
            padding: 15px;
            text-align: center;
            margin-bottom: 20px;
        }
        .content {
            padding: 0 15px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th {
            background-color: {{ $nota_color }};
            color: white;
            font-weight: bold;
            text-align: left;
            padding: 10px;
        }
        td {
            padding: 8px;
            text-align: left;
        }
        .footer {
            margin-top: 20px;
            padding: 15px;
            border-top: 1px solid #ddd;
        }
        .summary {
            margin-top: 20px;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        .summary table {
            border: none;
            width: 40%;
            float: right;
        }
        .summary table td {
            border: none;
            padding: 5px;
        }
        .summary table tr.total td {
            border-top: 1px solid #ddd;
            font-weight: bold;
        }
        .signatures {
            margin-top: 50px;
            display: flex;
            justify-content: space-between;
        }
        .signatures div {
            text-align: center;
            width: 45%;
        }
        .signature-line {
            border-bottom: 1px solid #333;
            margin-bottom: 10px;
            height: 40px;
        }
        .thank-you {
            background-color: {{ $nota_color }};
            color: white;
            padding: 15px;
            text-align: center;
            margin-top: 20px;
            font-size: 18px;
        }
        .contact {
            text-align: center;
            padding: 10px;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h3>{{ $tenant_name }}</h3>
        </div>

        <div class="content">
            <table>
                <tr>
                    <td width="30%"><strong>Tanggal Transaksi</strong></td>
                    <td>: {{ now()->format('d-m-Y H:i:s') }}</td>
                </tr>
                <tr>
                    <td><strong>Jumlah Piutang</strong></td>
                    <td>: {{ count($items) }}</td>
                </tr>
            </table>

            <table>
                <thead>
                    <tr>
                        <th style="width: 5%">No</th>
                        <th style="width: 30%">Vendor</th>
                        <th style="width: 15%">No. Transaksi</th>
                        <th style="width: 15%">Tanggal</th>
                        <th style="width: 10%">Jumlah</th>
                        <th style="width: 25%">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $i = 1;
                        $grandTotal = 0;
                    @endphp
                    @foreach($items as $item)
                    <tr>
                        <td>{{ $i++ }}</td>
                        <td>{{ $item['vendor'] }}</td>
                        <td>{{ $item['transaction_id'] }}</td>
                        <td>{{ $item['transaction_date'] }}</td>
                        <td>{{ $item['quantity'] }}</td>
                        <td>Rp {{ number_format($item['total'], 0, ',', '.') }}</td>
                    </tr>
                    @php $grandTotal += $item['total']; @endphp
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="5" style="text-align: right;"><strong>Total Keseluruhan</strong></td>
                        <td>Rp {{ number_format($grandTotal, 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>

            <div class="signatures">
                <div>
                    <div class="signature-line"></div>
                    <p>Tanda Terima</p>
                </div>
                <div>
                    <div class="signature-line"></div>
                    <p>Hormat Kami</p>
                </div>
            </div>
        </div>

        <div class="thank-you">
            TERIMA KASIH ATAS KUNJUNGAN ANDA
        </div>

        <div class="contact">
            <p>Telepon: {{ $tenant_phone }} &nbsp;&nbsp; Alamat: {{ $tenant_address }}</p>
        </div>
    </div>
</body>
</html>

