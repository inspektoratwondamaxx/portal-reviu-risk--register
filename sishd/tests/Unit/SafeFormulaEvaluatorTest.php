<?php

namespace Tests\Unit;

use App\Services\FormulaEvaluationException;
use App\Services\SafeFormulaEvaluator;
use PHPUnit\Framework\TestCase;

/**
 * Formula ASB dievaluasi tanpa eval() PHP (Bab 9 kajian) — parser buatan sendiri. Tes ini menjaga
 * dua hal: hasil hitungnya benar (termasuk urutan operasi), dan input di luar tata bahasa formula
 * ditolak alih-alih diam-diam dieksekusi.
 */
class SafeFormulaEvaluatorTest extends TestCase
{
    private SafeFormulaEvaluator $evaluator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->evaluator = new SafeFormulaEvaluator;
    }

    public function test_menghitung_aritmatika_dasar(): void
    {
        $this->assertSame(7.0, $this->evaluator->evaluate('3 + 4', []));
        $this->assertSame(6.0, $this->evaluator->evaluate('10 - 4', []));
        $this->assertSame(12.0, $this->evaluator->evaluate('3 * 4', []));
        $this->assertSame(2.5, $this->evaluator->evaluate('10 / 4', []));
    }

    public function test_menghormati_urutan_operasi_dan_kurung(): void
    {
        $this->assertSame(14.0, $this->evaluator->evaluate('2 + 3 * 4', []), 'Perkalian harus didahulukan.');
        $this->assertSame(20.0, $this->evaluator->evaluate('(2 + 3) * 4', []), 'Kurung harus mengubah urutan.');
        $this->assertSame(11.0, $this->evaluator->evaluate('2 * 3 + 10 / 2', []));
    }

    public function test_mengganti_variabel_dengan_nilainya(): void
    {
        $hasil = $this->evaluator->evaluate(
            '{luas_bangunan} * {standar_biaya_per_m2}',
            ['luas_bangunan' => 500, 'standar_biaya_per_m2' => 7_500_000],
        );

        $this->assertSame(3_750_000_000.0, $hasil);
    }

    public function test_menolak_variabel_tanpa_nilai(): void
    {
        $this->expectException(FormulaEvaluationException::class);
        $this->evaluator->evaluate('{luas} * 2', []);
    }

    public function test_menolak_pembagian_dengan_nol(): void
    {
        $this->expectException(FormulaEvaluationException::class);
        $this->evaluator->evaluate('10 / 0', []);
    }

    public function test_menolak_formula_kosong_dan_tidak_lengkap(): void
    {
        $this->expectException(FormulaEvaluationException::class);
        $this->evaluator->evaluate('   ', []);
    }

    public function test_menolak_kurung_tidak_seimbang(): void
    {
        $this->expectException(FormulaEvaluationException::class);
        $this->evaluator->evaluate('(2 + 3', []);
    }

    /** Yang terpenting: ekspresi PHP tidak boleh ikut tereksekusi seperti pada eval(). */
    public function test_menolak_karakter_di_luar_tata_bahasa_formula(): void
    {
        $this->expectException(FormulaEvaluationException::class);
        $this->evaluator->evaluate('phpinfo()', []);
    }

    public function test_menolak_pemanggilan_fungsi_lewat_variabel(): void
    {
        $this->expectException(FormulaEvaluationException::class);
        $this->evaluator->evaluate('{sistem}; DROP TABLE ssh_items;', ['sistem' => 1]);
    }

    public function test_mendaftar_nama_variabel_tanpa_butuh_nilai(): void
    {
        $this->assertSame(
            ['luas', 'tarif'],
            $this->evaluator->extractVariableNames('{luas} * {tarif} + {luas}'),
        );
    }
}
