@if($dokumen->isEmpty())<p class="mut">Belum ada berkas.</p>@else
<ul>@foreach($dokumen as $d)
  <li><a href="{{ route('dokumen.unduh', $d) }}" target="_blank" rel="noopener">{{ $d->nama_asli }}</a>
  <span class="mut">· {{ number_format($d->ukuran/1024, 0, ',', '.') }} KB · <code>{{ $d->path }}</code></span></li>
@endforeach</ul>@endif
