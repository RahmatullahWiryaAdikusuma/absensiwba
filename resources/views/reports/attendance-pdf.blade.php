<!DOCTYPE html>
<html>
<head>
    <title>Laporan Presensi</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #333; padding: 6px; text-align: left; }
        th { background-color: #f2f2f2; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h2 { margin: 0; }
        .header p { margin: 5px 0; color: #555; }
        .meta { margin-bottom: 10px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Laporan Data Presensi Karyawan</h2>
        <p>Periode: {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}</p>
        
        <p>Lokasi Kantor: {{ $officeName ?? 'Semua Kantor' }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Nama Pegawai</th>
                <th>Jam Masuk</th>
                <th>Jam Pulang</th>
                <th>Status</th>
                <th>Durasi</th>
            </tr>
        </thead>
        <tbody>
            @foreach($attendances as $index => $data)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ \Carbon\Carbon::parse($data->created_at)->format('d/m/Y') }}</td>
                <td>
                    {{ $data->user->name ?? '-' }}<br>
                    <small style="color: #666;">{{ $data->user->position->name ?? '' }}</small>
                </td>
                <td>{{ $data->start_time ?? '-' }}</td>
                <td>{{ $data->end_time ?? '-' }}</td>
                <td>
                    <span style="color: {{ $data->isLate() ? 'red' : 'green' }}">
                        {{ $data->isLate() ? 'Terlambat' : 'Tepat Waktu' }}
                    </span>
                </td>
                <td>{{ $data->workDuration() }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>