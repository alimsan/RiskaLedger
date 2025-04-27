<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Nota Pembelian {{ $tenant_name }}</title>
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
            <h2>NOTA PEMBELIAN</h2>
            <h3>{{ $tenant_name }}</h3>
        </div>

        <div class="content">
            <table>
                <tr>
                    <td width="30%"><strong>Tanggal Transaksi</strong></td>
                    <td>: {{ $transaction_date }}</td>
                </tr>
                <tr>
                    <td><strong>No. Transaksi</strong></td>
                    <td>: {{ $transaction_id }}</td>
                </tr>
                <tr>
                    <td><strong>Metode Pembayaran</strong></td>
                    <td>: {{ $payment_method }}</td>
                </tr>
                @if($is_receivable && $vendor)
                <tr>
                    <td><strong>Vendor</strong></td>
                    <td>: {{ $vendor }}</td>
                </tr>
                @endif
                @if($notes)
                <tr>
                    <td><strong>Catatan</strong></td>
                    <td>: {{ $notes }}</td>
                </tr>
                @endif
            </table>

            <table>
                <thead>
                    <tr>
                        <th style="width: 10%">No</th>
                        <th style="width: 40%">Item</th>
                        <th style="width: 15%">Jumlah</th>
                        <th style="width: 15%">Harga</th>
                        <th style="width: 20%">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @php $i = 1; @endphp
                    @foreach($items as $item)
                    <tr>
                        <td>{{ $i++ }}</td>
                        <td>{{ $item['name'] }}</td>
                        <td>{{ $item['quantity'] }}</td>
                        <td>Rp {{ number_format($item['price'], 0, ',', '.') }}</td>
                        <td>Rp {{ number_format($item['price'] * $item['quantity'], 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4" style="text-align: right;"><strong>Sub Total</strong></td>
                        <td>Rp {{ number_format($total, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td colspan="4" style="text-align: right;"><strong>Pajak</strong></td>
                        <td>-</td>
                    </tr>
                    <tr>
                        <td colspan="4" style="text-align: right;"><strong>Total</strong></td>
                        <td>Rp {{ number_format($total, 0, ',', '.') }}</td>
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
