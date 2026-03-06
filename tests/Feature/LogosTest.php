<?php

uses(\Aliziodev\WilayahLogos\Tests\TestCase::class);
use Aliziodev\Wilayah\Models\Province;
use Aliziodev\Wilayah\Models\Regency;

beforeEach(function () {
    $this->seedTestData();
});

test('province memiliki macro logo path', function () {
    $province = Province::where('code', '32')->first();

    expect(method_exists($province, 'logoPath') || $province->hasMacro('logoPath'))->toBeTrue();
    $path = $province->logoPath();
    $this->assertStringContainsString('32.png', $path);
    $this->assertStringContainsString('prov', $path);
    $this->assertStringContainsString('img', $path);
});

test('province logo path thumb menggunakan folder thumbs', function () {
    $province = Province::where('code', '32')->first();
    $path = $province->logoPath('thumb');

    $this->assertStringContainsString('thumbs', $path);
    $this->assertStringContainsString('32.png', $path);
});

test('regency memiliki macro logo path', function () {
    $regency = Regency::where('code', '32.73')->first();
    $path = $regency->logoPath();

    $this->assertStringContainsString('32', $path);
    $this->assertStringContainsString('32.73.png', $path);
    $this->assertStringContainsString('kab', $path);
});

test('regency kode prov diambil dari kode kabupaten', function () {
    $regency = Regency::where('code', '32.73')->first();
    $path = $regency->logoPath();

    // Path harus mengandung kode provinsi (32) sebagai subfolder
    $this->assertStringContainsString('/32/', $path);
});

test('province logo url mengembalikan null jika file tidak ada', function () {
    $province = Province::where('code', '32')->first();

    // Dalam environment test, public_path tidak ada file PNG sungguhan
    $url = $province->logoUrl();
    expect($url)->toBeNull();
});

test('regency logo url mengembalikan null jika file tidak ada', function () {
    $regency = Regency::where('code', '32.73')->first();

    $url = $regency->logoUrl();
    expect($url)->toBeNull();
});
