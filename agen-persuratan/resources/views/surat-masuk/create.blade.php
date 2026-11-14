@extends('layouts.app')
@section('judul', 'Catat Surat Masuk')
@section('isi')
<h1>Catat Surat Masuk</h1>
<form class="card" method="POST" action="{{ route('surat-masuk.store') }}" enctype="multipart/form-data">@csrf
  @include('partials.form-surat', ['pihak' => 'Asal surat', 'nama' => 'asal_surat', 'denganTerima' => true, 'wajibBerkas' => true])
  @if(session('duplikat'))<p><label style="font-weight:400"><input type="checkbox" name="abaikan_duplikat" value="1"> Tetap simpan meskipun berkas duplikat</label></p>@endif
  <p><button class="btn">Simpan ke register</button> <a class="btn sek" href="{{ route('surat-masuk.index') }}">Batal</a></p>
</form>
@endsection
