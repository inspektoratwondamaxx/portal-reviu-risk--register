{{-- $pihak: label kolom asal/tujuan; $nama: nama field --}}
<div class="row">
  <div><label>Nomor surat</label><input name="nomor_surat" value="{{ old('nomor_surat') }}" required>@error('nomor_surat')<div class="err-t">{{ $message }}</div>@enderror</div>
  <div><label>Tanggal surat</label><input type="date" name="tanggal_surat" value="{{ old('tanggal_surat') }}" required>@error('tanggal_surat')<div class="err-t">{{ $message }}</div>@enderror</div>
  @isset($denganTerima)
  <div><label>Tanggal diterima</label><input type="date" name="tanggal_terima" value="{{ old('tanggal_terima', now()->toDateString()) }}" required>@error('tanggal_terima')<div class="err-t">{{ $message }}</div>@enderror</div>
  @endisset
  <div><label>{{ $pihak }}</label><input name="{{ $nama }}" value="{{ old($nama) }}" required>@error($nama)<div class="err-t">{{ $message }}</div>@enderror</div>
  <div><label>Sifat</label><select name="sifat">@foreach(\App\Models\SuratMasuk::SIFAT as $s)<option value="{{ $s }}" @selected(old('sifat','biasa')===$s)>{{ ucfirst($s) }}</option>@endforeach</select></div>
  <div><label>Kode klasifikasi arsip</label><input name="klasifikasi" value="{{ old('klasifikasi') }}" placeholder="mis. 800.1.2"></div>
  <div class="full"><label>Perihal</label><textarea name="perihal" required>{{ old('perihal') }}</textarea>@error('perihal')<div class="err-t">{{ $message }}</div>@enderror</div>
  <div class="full"><label>Ringkasan isi (opsional)</label><textarea name="ringkasan">{{ old('ringkasan') }}</textarea></div>
  <div class="full"><label>Foto / scan / PDF surat {{ $wajibBerkas ? '(wajib)' : '(opsional)' }}</label>
    <input type="file" name="berkas[]" multiple accept="image/jpeg,image/png,application/pdf" {{ $wajibBerkas ? 'required' : '' }}>
    <div class="mut" style="font-size:.85rem">PDF, JPG, atau PNG, maks. 10 MB per berkas. Di HP, pilih “Ambil foto” untuk memotret langsung.</div>
    @error('berkas')<div class="err-t">{{ $message }}</div>@enderror @error('berkas.*')<div class="err-t">{{ $message }}</div>@enderror</div>
</div>
