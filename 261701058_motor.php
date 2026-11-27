<?php
// Kara Kutu gözlemlerinden oluşturulan motor.
declare(strict_types=1);

function adim(array $s, string $a): array {
    if ($s['bitti']) {
        return $s;
    }

    if ($a == 'M') {
        if ($s['gunluk_musteri'] >= 5) {
            return $s;
        }
        $s['gunluk_musteri']++;
        $s['musteri_sira']++;
        $m = musteri($s['no'], $s['musteri_sira']);
        $u = $m['urun'];
        $fiyat = $s['urunler'][$u]['fiyat'];
        $adet = 1;
        $satilan = 0;

        if ($m['tip'] == 'Öğrenci' || $m['tip'] == 'Burslu Öğrenci') {
            $fiyat = ceil($fiyat * 0.85);
        }
        if ($s['yogunluk']) {
            $adet = 2;
        }
        if ($s['elektrik'] > 0) {
            $s['elektrik']--;
            $s['memnuniyet'] -= 8;
        } elseif ($s['urunler'][$u]['stok'] < $adet) {
            $s['memnuniyet'] -= 6;
            $s['yogunluk'] = false;
        } elseif ($m['butce'] < $fiyat * $adet) {
            $s['memnuniyet'] -= 4;
            $s['yogunluk'] = false;
        } else {
            $satilan = $adet;
            $s['kasa'] += $fiyat * $adet;
            $s['urunler'][$u]['stok'] -= $adet;
            $s['memnuniyet']++;
            $s['seri']++;
            $s['yogunluk'] = false;
            $s['ret_sayac'][$u] = 0;
            if ($s['seri'] % 3 == 0) {
                $s['kasa'] += 10;
            }
            if ($s['urunler'][$u]['stok'] <= 3 && !$s['zam'][$u]) {
                $s['urunler'][$u]['fiyat'] += 5;
                $s['zam'][$u] = true;
            }
        }
        if ($satilan == 0) {
            $s['seri'] = 0;
            $s['gunluk_ret']++;
            $s['ret_sayac'][$u]++;
            $sonuc = 'Satış yok';
        } else {
            $sonuc = 'Satış: ' . $satilan . ' adet';
        }
        $s['log'][] = '👤 ' . $m['ad'] . ' (' . $m['tip'] . ', bütçe ' . $m['butce'] . ' TL) ' . $s['urunler'][$u]['ad'] . ' istiyor → ' . $sonuc;
        $o = olay($s['no'], $s['musteri_sira']);
        if ($o == 'elektrik') {
            $s['elektrik'] = 2;
            $s['log'][] = '⚡ Elektrik kesintisi';
        } elseif ($o == 'yogunluk') {
            $s['yogunluk'] = true;
            $s['log'][] = '👥 Öğle yoğunluğu';
        } elseif ($o == 'tedarik') {
            $s['urunler']['su']['stok'] = max(0, $s['urunler']['su']['stok'] - 2);
            $s['log'][] = '📦 Tedarik sorunu';
        } elseif ($o == 'bahsis') {
            if ($satilan > 0) {
                $s['kasa'] += 20;
            } else {
                $s['kasa'] += 5;
            }
            $s['log'][] = '😊 Memnun müşteri';
        }
    } elseif ($a == 'G') {
        if ($s['gunluk_musteri'] < 5) {
            return $s;
        }
        $s['kasa'] -= 50 + $s['gun'] * 10;
        $s['urunler']['tost']['stok'] = ceil($s['urunler']['tost']['stok'] / 2);
        $s['urunler']['hamburger']['stok'] = ceil($s['urunler']['hamburger']['stok'] / 2);
        if ($s['gunluk_ret'] == 0) {
            $s['memnuniyet'] += 3;
        } elseif ($s['gunluk_ret'] >= 2) {
            $s['memnuniyet'] -= 5;
        }
        $s['log'][] = '🌙 Gün ' . $s['gun'] . ' kapandı';
        $s['gunluk_musteri'] = 0;
        $s['gunluk_ret'] = 0;
        $s['seri'] = 0;
        $s['elektrik'] = 0;
        $s['yogunluk'] = false;
        if ($s['gun'] == 5) {
            $s['bitti'] = true;
            if ($s['kasa'] >= 1000 && $s['memnuniyet'] >= 50) {
                $s['kazandi'] = true;
            }
        } else {
            $s['gun']++;
        }
    } else {
        foreach ($s['urunler'] as $u => $urun) {
            for ($adet = 1; $adet <= 10; $adet++) {
                if ($a == 'T-' . $u . '-' . $adet) {
                    $maliyet = $urun['fiyat'] * $adet * 2;
                    if ($adet >= 5) {
                        $maliyet = $maliyet * 0.8;
                    }
                    if ($s['kasa'] >= $maliyet) {
                        $s['kasa'] -= $maliyet;
                        $s['urunler'][$u]['stok'] += $adet;
                        $s['log'][] = '🚚 ' . $urun['ad'] . ' tedarik: ' . $adet . ' adet';
                    }
                    return $s;
                }
            }
        }
    }
    $s['memnuniyet'] = max(0, min(100, $s['memnuniyet']));
    return $s;
}
