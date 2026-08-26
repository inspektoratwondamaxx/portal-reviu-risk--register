<?php

return [
    /*
     * Ambang batas deteksi kewajaran (KF-08): persentase nominal transaksi terhadap sisa pagu
     * kode rekening yang memicu flag otomatis untuk direview. Dibuat dapat dikonfigurasi
     * (bukan hardcode) sesuai catatan risiko Bab VII.2.
     */
    'ambang_kewajaran_persen' => (float) env('SPJ_AMBANG_KEWAJARAN_PERSEN', 90),

    /*
     * Target ukuran maksimum foto bukti setelah kompresi sisi Android, sesuai Bab IV.5.
     * Nilai ini dipakai backend untuk memvalidasi ukuran unggahan, bukan melakukan kompresi
     * (kompresi dilakukan di aplikasi Android sebelum unggah).
     */
    'maks_ukuran_bukti_kb' => (int) env('SPJ_MAKS_UKURAN_BUKTI_KB', 800),
];
