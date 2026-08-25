<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>SPJ {{ $periode->kampung->nama_kampung }} - {{ $namaBulan }} {{ $periode->tahun_anggaran }}</title>
<style>
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #1a1a1a; }
    h1 { font-size: 14px; margin-bottom: 2px; }
    h2 { font-size: 12px; margin-top: 18px; margin-bottom: 6px; }
    .subjudul { font-size: 10px; color: #444; margin-bottom: 14px; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
    th, td { border: 1px solid #999; padding: 4px 6px; text-align: left; vertical-align: top; }
    th { background: #eee; }
    .angka { text-align: right; white-space: nowrap; }
    .total-row td { font-weight: bold; background: #f5f5f5; }
    .footer { margin-top: 24px; font-size: 9px; color: #666; }
</style>
</head>
<body>
    <h1>SURAT PERTANGGUNGJAWABAN (SPJ) DANA DESA</h1>
    <div class="subjudul">
        Kampung: {{ $periode->kampung->nama_kampung }} ({{ $periode->kampung->kode_kampung }}) —
        Kecamatan {{ $periode->kampung->kecamatan }}<br>
        Periode: {{ $namaBulan }} {{ $periode->tahun_anggaran }} &middot;
        Status: {{ $periode->status }} &middot;
        Dicetak: {{ now()->format('d-m-Y H:i') }}
    </div>

    <h2>Buku Kas Umum — Rekapitulasi Realisasi Belanja</h2>
    <table>
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Kode Rek.</th>
                <th>Kegiatan / Uraian</th>
                <th class="angka">Nominal (Rp)</th>
                <th class="angka">Saldo Berjalan (Rp)</th>
                <th>Bukti</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($baris as $b)
            <tr>
                <td>{{ $b['tanggal'] }}</td>
                <td>{{ $b['kode_rekening'] }}</td>
                <td>{{ $b['kegiatan'] }}<br><em>{{ $b['uraian'] }}</em></td>
                <td class="angka">{{ number_format($b['nominal'], 2, ',', '.') }}</td>
                <td class="angka">{{ number_format($b['saldo_berjalan'], 2, ',', '.') }}</td>
                <td>{{ $b['jumlah_bukti'] }} foto</td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="3">TOTAL REALISASI BELANJA</td>
                <td class="angka">{{ number_format($totalBelanja, 2, ',', '.') }}</td>
                <td colspan="2"></td>
            </tr>
        </tbody>
    </table>

    <h2>Rekapitulasi per Kode Rekening</h2>
    <table>
        <thead>
            <tr><th>Kode</th><th>Uraian</th><th class="angka">Total (Rp)</th></tr>
        </thead>
        <tbody>
            @foreach ($rekap as $r)
            <tr>
                <td>{{ $r['kode'] }}</td>
                <td>{{ $r['uraian'] }}</td>
                <td class="angka">{{ number_format($r['total'], 2, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Dokumen dihasilkan otomatis oleh Sistem Informasi SPJ Dana Desa Digital.
        Lampiran bukti transaksi (foto + metadata GPS) tersimpan terpisah pada object storage
        dan dapat diverifikasi melalui dashboard web sesuai transaksi terkait.
    </div>
</body>
</html>
