<?php
/**
 * Program Menghitung Total Pembayaran dan Diskon
 * Menghitung total belanja dengan berbagai jenis diskon
 * Dilengkapi format Rupiah dengan titik ribuan
 */

// Inisialisasi session untuk riwayat
session_start();

// Inisialisasi variabel
$total_belanja = isset($_POST['total_belanja']) ? str_replace(['.', ','], '', $_POST['total_belanja']) : 0;
$total_belanja = (float)$total_belanja;
$jenis_member = isset($_POST['jenis_member']) ? $_POST['jenis_member'] : 'non_member';
$hari_spesial = isset($_POST['hari_spesial']) ? $_POST['hari_spesial'] : 'tidak';
$diskon_persen = 0;
$diskon_nominal = 0;
$total_setelah_diskon = 0;
$total_bayar = 0;
$error = '';
$detail_diskon = [];
$input_display = '';

// Fungsi untuk format Rupiah dengan titik ribuan
function rupiah($angka) {
    // Pastikan angka numerik
    $angka = (float)$angka;
    // Format dengan titik ribuan, tanpa desimal
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

// Fungsi untuk format angka tanpa desimal
function formatAngka($angka) {
    return number_format((float)$angka, 0, ',', '.');
}

// Fungsi untuk menghitung diskon
function hitungDiskon($total, $member, $hari) {
    $diskon = 0;
    $detail = [];
    
    // 1. Diskon berdasarkan total belanja
    if ($total >= 500000) {
        $diskon += 20;
        $detail[] = ['nama' => 'Diskon Belanja > Rp 500.000', 'persen' => 20];
    } elseif ($total >= 300000) {
        $diskon += 15;
        $detail[] = ['nama' => 'Diskon Belanja > Rp 300.000', 'persen' => 15];
    } elseif ($total >= 200000) {
        $diskon += 10;
        $detail[] = ['nama' => 'Diskon Belanja > Rp 200.000', 'persen' => 10];
    } elseif ($total >= 100000) {
        $diskon += 5;
        $detail[] = ['nama' => 'Diskon Belanja > Rp 100.000', 'persen' => 5];
    }
    
    // 2. Diskon member
    if ($member == 'gold') {
        $diskon += 10;
        $detail[] = ['nama' => 'Diskon Member Gold', 'persen' => 10];
    } elseif ($member == 'silver') {
        $diskon += 5;
        $detail[] = ['nama' => 'Diskon Member Silver', 'persen' => 5];
    } elseif ($member == 'platinum') {
        $diskon += 15;
        $detail[] = ['nama' => 'Diskon Member Platinum', 'persen' => 15];
    }
    
    // 3. Diskon hari spesial
    if ($hari == 'ya') {
        $diskon += 10;
        $detail[] = ['nama' => 'Diskon Hari Spesial', 'persen' => 10];
    }
    
    // Batasi diskon maksimal 50%
    if ($diskon > 50) {
        $diskon = 50;
        $detail = [['nama' => 'Diskon Maksimal 50%', 'persen' => 50]];
    }
    
    return ['persen' => $diskon, 'detail' => $detail];
}

// Proses perhitungan jika form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['total_belanja']) && $_POST['total_belanja'] !== '') {
        // Bersihkan input dari titik dan koma
        $input_raw = $_POST['total_belanja'];
        $input_clean = str_replace(['.', ','], '', $input_raw);
        $total_belanja = (float)$input_clean;
        $input_display = $input_raw;
        
        if ($total_belanja > 0) {
            $hasil = hitungDiskon($total_belanja, $jenis_member, $hari_spesial);
            $diskon_persen = $hasil['persen'];
            $detail_diskon = $hasil['detail'];
            $diskon_nominal = $total_belanja * ($diskon_persen / 100);
            $total_setelah_diskon = $total_belanja - $diskon_nominal;
            $total_bayar = $total_setelah_diskon;
            
            // Simpan riwayat
            if (!isset($_SESSION['riwayat_pembayaran'])) {
                $_SESSION['riwayat_pembayaran'] = [];
            }
            
            $riwayat_item = [
                'total' => $total_belanja,
                'diskon_persen' => $diskon_persen,
                'diskon_nominal' => $diskon_nominal,
                'total_bayar' => $total_bayar,
                'member' => $jenis_member,
                'hari' => $hari_spesial,
                'waktu' => date('H:i:s')
            ];
            array_push($_SESSION['riwayat_pembayaran'], $riwayat_item);
            
            if (count($_SESSION['riwayat_pembayaran']) > 10) {
                array_shift($_SESSION['riwayat_pembayaran']);
            }
        } else {
            $error = 'Total belanja harus lebih dari 0!';
        }
    } else {
        $error = 'Masukkan total belanja!';
    }
}

// Hapus riwayat
if (isset($_POST['clear_history'])) {
    unset($_SESSION['riwayat_pembayaran']);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Total Pembayaran dan Diskon</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, sans-serif;
            background: linear-gradient(135deg, #2d3436 0%, #636e72 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .container {
            display: flex;
            gap: 30px;
            flex-wrap: wrap;
            justify-content: center;
            max-width: 1000px;
            width: 100%;
        }

        /* Card Utama */
        .main-card {
            background: white;
            border-radius: 25px;
            padding: 35px;
            width: 100%;
            max-width: 480px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }

        .main-card h2 {
            text-align: center;
            color: #2d3436;
            font-size: 24px;
            margin-bottom: 5px;
        }

        .main-card .subtitle {
            text-align: center;
            color: #888;
            font-size: 14px;
            margin-bottom: 25px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            color: #555;
            margin-bottom: 6px;
            font-size: 14px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s;
            background: #f8f9fa;
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: #6c5ce7;
            outline: none;
            background: white;
            box-shadow: 0 0 0 4px rgba(108, 92, 231, 0.1);
        }

        .form-group input::placeholder {
            color: #bbb;
            font-size: 15px;
        }

        /* Input dengan format Rupiah */
        .input-rupiah {
            position: relative;
        }

        .input-rupiah .rupiah-prefix {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            font-weight: 600;
            color: #6c5ce7;
            font-size: 16px;
        }

        .input-rupiah input {
            padding-left: 40px !important;
            font-weight: 600;
            font-size: 18px !important;
        }

        .btn-hitung {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #6c5ce7 0%, #a29bfe 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-hitung:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(108, 92, 231, 0.3);
        }

        .btn-hitung:active {
            transform: scale(0.98);
        }

        /* Hasil */
        .result-container {
            margin-top: 25px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 15px;
            display: <?php echo isset($total_bayar) && $total_bayar > 0 ? 'block' : 'none'; ?>;
        }

        .result-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }

        .result-item:last-child {
            border-bottom: none;
        }

        .result-item .label {
            color: #666;
            font-size: 14px;
        }

        .result-item .value {
            font-size: 16px;
            font-weight: 600;
            color: #2d3436;
        }

        .result-item .value.diskon {
            color: #e74c3c;
        }

        .result-item .value.total {
            color: #6c5ce7;
            font-size: 20px;
        }

        .result-item .value.bayar {
            color: #00b894;
            font-size: 22px;
        }

        .result-detail {
            margin-top: 10px;
            padding: 10px;
            background: #e8f0fe;
            border-radius: 10px;
            font-size: 13px;
        }

        .result-detail .detail-item {
            display: flex;
            justify-content: space-between;
            padding: 4px 0;
            color: #555;
        }

        .result-detail .detail-item .badge {
            background: #6c5ce7;
            color: white;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 11px;
        }

        /* Error */
        .error-message {
            background: #fee;
            color: #e74c3c;
            padding: 12px 15px;
            border-radius: 10px;
            margin-top: 15px;
            font-size: 14px;
            text-align: center;
            border: 1px solid #fcc;
        }

        /* Riwayat */
        .history-card {
            background: white;
            border-radius: 25px;
            padding: 25px;
            width: 100%;
            max-width: 380px;
            max-height: 550px;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
        }

        .history-card h3 {
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #2d3436;
            font-size: 18px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        .history-card h3 button {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 5px 15px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.3s;
        }

        .history-card h3 button:hover {
            background: #c0392b;
            transform: scale(1.05);
        }

        .history-item {
            padding: 12px;
            margin-top: 10px;
            background: #f8f9fa;
            border-radius: 12px;
            border-left: 4px solid #6c5ce7;
            transition: all 0.3s;
        }

        .history-item:hover {
            transform: translateX(5px);
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .history-item .main {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
        }

        .history-item .main .total {
            color: #6c5ce7;
            font-weight: 600;
        }

        .history-item .main .bayar {
            font-weight: 700;
            color: #00b894;
        }

        .history-item .detail {
            font-size: 12px;
            color: #999;
            margin-top: 5px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .history-item .detail .member-badge {
            background: #6c5ce7;
            color: white;
            padding: 1px 10px;
            border-radius: 12px;
            font-size: 10px;
        }

        .empty-history {
            text-align: center;
            color: #bbb;
            padding: 30px 0;
            font-size: 14px;
        }

        .history-card::-webkit-scrollbar {
            width: 6px;
        }

        .history-card::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .history-card::-webkit-scrollbar-thumb {
            background: #6c5ce7;
            border-radius: 10px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .container {
                flex-direction: column;
                align-items: center;
            }

            .main-card,
            .history-card {
                max-width: 100%;
                width: 100%;
            }

            .history-card {
                max-height: 300px;
            }
        }

        /* Info Box */
        .info-box {
            margin-top: 15px;
            padding: 15px;
            background: #e8f0fe;
            border-radius: 10px;
            font-size: 13px;
            color: #555;
        }

        .info-box h4 {
            color: #2d3436;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .info-box ul {
            list-style: none;
            padding: 0;
        }

        .info-box ul li {
            padding: 3px 0;
            font-size: 12px;
            color: #666;
        }

        .info-box ul li .min {
            display: inline-block;
            background: #6c5ce7;
            color: white;
            padding: 0 8px;
            border-radius: 10px;
            font-size: 10px;
            margin-right: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Card Utama -->
        <div class="main-card">
            <h2>🛒 Total Pembayaran & Diskon</h2>
            <p class="subtitle">Hitung total belanja dengan berbagai jenis diskon</p>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="total_belanja">Total Belanja (Rp)</label>
                    <div class="input-rupiah">
                        <span class="rupiah-prefix">Rp</span>
                        <input type="text" id="total_belanja" name="total_belanja" 
                               placeholder="Masukkan total belanja" 
                               value="<?php echo htmlspecialchars($input_display); ?>" 
                               oninput="formatRupiah(this)" required>
                    </div>
                    <small style="color: #999; font-size: 11px;">Contoh: 100000 atau 100.000</small>
                </div>

                <div class="form-group">
                    <label for="jenis_member">Jenis Member</label>
                    <select id="jenis_member" name="jenis_member">
                        <option value="non_member" <?php echo $jenis_member == 'non_member' ? 'selected' : ''; ?>>Non Member</option>
                        <option value="silver" <?php echo $jenis_member == 'silver' ? 'selected' : ''; ?>>Silver (5%)</option>
                        <option value="gold" <?php echo $jenis_member == 'gold' ? 'selected' : ''; ?>>Gold (10%)</option>
                        <option value="platinum" <?php echo $jenis_member == 'platinum' ? 'selected' : ''; ?>>Platinum (15%)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="hari_spesial">Hari Spesial (Diskon Tambahan 10%)</label>
                    <select id="hari_spesial" name="hari_spesial">
                        <option value="tidak" <?php echo $hari_spesial == 'tidak' ? 'selected' : ''; ?>>Tidak</option>
                        <option value="ya" <?php echo $hari_spesial == 'ya' ? 'selected' : ''; ?>>Ya</option>
                    </select>
                </div>

                <button type="submit" class="btn-hitung">💳 Hitung Total</button>
            </form>

            <?php if ($error): ?>
                <div class="error-message">⚠️ <?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Hasil -->
            <div class="result-container">
                <div class="result-item">
                    <span class="label">Total Belanja</span>
                    <span class="value"><?php echo rupiah($total_belanja); ?></span>
                </div>
                <?php if ($diskon_persen > 0): ?>
                <div class="result-item">
                    <span class="label">Diskon (<?php echo $diskon_persen; ?>%)</span>
                    <span class="value diskon">- <?php echo rupiah($diskon_nominal); ?></span>
                </div>
                <?php endif; ?>
                <div class="result-item">
                    <span class="label">Total Setelah Diskon</span>
                    <span class="value total"><?php echo rupiah($total_setelah_diskon); ?></span>
                </div>
                <div class="result-item" style="border-top: 2px solid #6c5ce7; padding-top: 12px; margin-top: 5px;">
                    <span class="label" style="font-weight: 700; color: #2d3436;">Total yang Harus Dibayar</span>
                    <span class="value bayar"><?php echo rupiah($total_bayar); ?></span>
                </div>

                <?php if (!empty($detail_diskon)): ?>
                <div class="result-detail">
                    <h4 style="font-size: 13px; color: #2d3436; margin-bottom: 5px;">📋 Rincian Diskon</h4>
                    <?php foreach($detail_diskon as $d): ?>
                    <div class="detail-item">
                        <span><?php echo $d['nama']; ?></span>
                        <span class="badge"><?php echo $d['persen']; ?>%</span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Info Box -->
            <div class="info-box">
                <h4>📖 Ketentuan Diskon</h4>
                <ul>
                    <li><span class="min">Rp 100.000</span> 5%</li>
                    <li><span class="min">Rp 200.000</span> 10%</li>
                    <li><span class="min">Rp 300.000</span> 15%</li>
                    <li><span class="min">Rp 500.000</span> 20%</li>
                    <li>Member Gold +10% | Silver +5% | Platinum +15%</li>
                    <li>Hari Spesial +10%</li>
                    <li style="color: #e74c3c;">⚠️ Maksimal diskon 50%</li>
                </ul>
            </div>
        </div>

        <!-- Riwayat -->
        <div class="history-card">
            <h3>
                📝 Riwayat Transaksi
                <form method="POST" style="display: inline;">
                    <button type="submit" name="clear_history">Hapus</button>
                </form>
            </h3>

            <?php if (isset($_SESSION['riwayat_pembayaran']) && !empty($_SESSION['riwayat_pembayaran'])): ?>
                <?php foreach(array_reverse($_SESSION['riwayat_pembayaran']) as $item): ?>
                    <div class="history-item">
                        <div class="main">
                            <span class="total"><?php echo rupiah($item['total']); ?></span>
                            <span class="bayar"><?php echo rupiah($item['total_bayar']); ?></span>
                        </div>
                        <div class="detail">
                            <span>Diskon: <?php echo $item['diskon_persen']; ?>%</span>
                            <span>Hemat: <?php echo rupiah($item['diskon_nominal']); ?></span>
                            <span>⏰ <?php echo $item['waktu']; ?></span>
                            <?php if ($item['member'] != 'non_member'): ?>
                                <span class="member-badge"><?php echo ucfirst($item['member']); ?></span>
                            <?php endif; ?>
                            <?php if ($item['hari'] == 'ya'): ?>
                                <span class="member-badge" style="background: #e17055;">🎉 Spesial</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-history">Belum ada transaksi</div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Fungsi untuk format Rupiah dengan titik ribuan
        function formatRupiah(input) {
            // Hapus semua karakter kecuali angka
            let value = input.value.replace(/[^0-9]/g, '');
            
            if (value === '') {
                input.value = '';
                return;
            }
            
            // Konversi ke number dan format dengan titik
            let number = parseInt(value);
            if (!isNaN(number)) {
                let formatted = number.toLocaleString('id-ID');
                input.value = formatted;
            }
        }

        // Tambahkan event listener untuk input
        document.addEventListener('DOMContentLoaded', function() {
            const input = document.getElementById('total_belanja');
            
            // Format saat input berubah
            input.addEventListener('input', function(e) {
                formatRupiah(this);
            });
            
            // Format saat fokus keluar
            input.addEventListener('blur', function(e) {
                if (this.value === '') return;
                // Bersihkan dan format ulang
                let value = this.value.replace(/[^0-9]/g, '');
                if (value !== '') {
                    let number = parseInt(value);
                    this.value = number.toLocaleString('id-ID');
                }
            });
        });
    </script>
</body>
</html>