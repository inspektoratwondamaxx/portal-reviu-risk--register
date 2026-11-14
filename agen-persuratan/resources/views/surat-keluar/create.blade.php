@extends('layouts.app')
@section('judul', 'Catat Surat Keluar')
@section('isi')
<h1>Catat Surat Keluar</h1>
<form class="card" method="POST" action="{{ route('surat-keluar.store') }}" enctype="multipart/form-data">@csrf
  @include('partials.form-surat', ['pihak' => 'Tujuan surat', 'nama' => 'tujuan', 'wajibBerkas' => false])
  <p><button class="btn">Simpan ke register</button> <a class="btn sek" href="{{ route('surat-keluar.index') }}">Batal</a></p>
</form>
@endsection
