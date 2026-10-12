<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['id_user'])) {
    header("Location: index.php");
    exit;
}

$nama = $_SESSION['nama'];
$role = $_SESSION['role'];
$id_user = $_SESSION['id_user'];

if ($role == "admin") {
    $q_user = mysqli_query($conn, "SELECT COUNT(*) AS total FROM user");
    $total_user = mysqli_fetch_assoc($q_user)['total'];

    $q_sampah = mysqli_query($conn, "SELECT COUNT(*) AS total FROM sampah");
    $total_sampah = mysqli_fetch_assoc($q_sampah)['total'];

    $q_transaksi = mysqli_query($conn, "SELECT COUNT(*) AS total FROM transaksi");
    $total_transaksi = mysqli_fetch_assoc($q_transaksi)['total'];

    $q_tarik = mysqli_query($conn, "SELECT COUNT(*) AS total FROM tarik_saldo");
    $total_tarik = mysqli_fetch_assoc($q_tarik)['total'];

    // Grafik 6 bulan terakhir. Hanya transaksi yang sudah disetujui.
    $grafik_raw = [];
    $grafik_query = mysqli_query($conn, "
        SELECT DATE_FORMAT(tanggal, '%Y-%m') AS bulan, COUNT(*) AS jumlah
        FROM transaksi
        WHERE status='disetujui'
          AND tanggal >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 5 MONTH), '%Y-%m-01')
        GROUP BY DATE_FORMAT(tanggal, '%Y-%m')
        ORDER BY bulan ASC
    ");
    while ($row = mysqli_fetch_assoc($grafik_query)) {
        $grafik_raw[$row['bulan']] = (int)$row['jumlah'];
    }

    $grafik_label = [];
    $grafik_data = [];
    $dt = new DateTime('first day of this month');
    $bulan_id = [
        '01'=>'Jan','02'=>'Feb','03'=>'Mar','04'=>'Apr','05'=>'Mei','06'=>'Jun',
        '07'=>'Jul','08'=>'Agu','09'=>'Sep','10'=>'Okt','11'=>'Nov','12'=>'Des'
    ];
    for ($i = 5; $i >= 0; $i--) {
        $d = clone $dt;
        $d->modify("-$i month");
        $key = $d->format('Y-m');
        $grafik_label[] = $bulan_id[$d->format('m')] . ' ' . $d->format('Y');
        $grafik_data[] = $grafik_raw[$key] ?? 0;
    }

} elseif ($role == "siswa") {
    $q_saldo = mysqli_query($conn, "SELECT saldo FROM user WHERE id_user = '$id_user'");
    $saldo = (int)mysqli_fetch_assoc($q_saldo)['saldo'];

    $q_setor = mysqli_query($conn, "SELECT COALESCE(SUM(berat_kg),0) AS total FROM transaksi WHERE id_user='$id_user' AND tipe='setor' AND status='disetujui'");
    $total_setor = mysqli_fetch_assoc($q_setor)['total'];

    $q_transaksi = mysqli_query($conn, "SELECT COUNT(*) AS total FROM transaksi WHERE id_user='$id_user'");
    $total_transaksi = mysqli_fetch_assoc($q_transaksi)['total'];

} elseif ($role == "guru") {
    $q_saldo = mysqli_query($conn, "SELECT saldo FROM user WHERE id_user='$id_user'");
    $saldo_guru = (int)mysqli_fetch_assoc($q_saldo)['saldo'];

    $q_setor = mysqli_query($conn, "SELECT COALESCE(SUM(berat_kg),0) AS total FROM transaksi WHERE id_user='$id_user' AND tipe='setor' AND status='disetujui'");
    $total_setor_guru = mysqli_fetch_assoc($q_setor)['total'];

    $q_siswa = mysqli_query($conn, "SELECT COUNT(*) AS total FROM user WHERE role='siswa'");
    $total_siswa = mysqli_fetch_assoc($q_siswa)['total'];

    $q_monitoring = mysqli_query($conn, "SELECT COUNT(*) AS total FROM transaksi JOIN user ON transaksi.id_user=user.id_user WHERE user.role='siswa' AND transaksi.tipe='setor' AND transaksi.status='disetujui'");
    $total_monitoring = mysqli_fetch_assoc($q_monitoring)['total'];

} else {
    $q_saldo = mysqli_query($conn, "SELECT saldo FROM user WHERE id_user='$id_user'");
    $saldo_pengepul = (int)mysqli_fetch_assoc($q_saldo)['saldo'];

    $q_stok = mysqli_query($conn, "SELECT COALESCE(SUM(stok_kg),0) AS total FROM sampah");
    $total_stok = mysqli_fetch_assoc($q_stok)['total'];

    $q_pembelian = mysqli_query($conn, "SELECT COUNT(*) AS total FROM transaksi WHERE id_user='$id_user' AND tipe='jual_pengepul' AND status='disetujui'");
    $total_pembelian = mysqli_fetch_assoc($q_pembelian)['total'];

    $q_total_pembelian = mysqli_query($conn, "SELECT COALESCE(SUM(total_rp),0) AS total FROM transaksi WHERE id_user='$id_user' AND tipe='jual_pengepul' AND status='disetujui'");
    $total_uang_pembelian = mysqli_fetch_assoc($q_total_pembelian)['total'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard <?= ucfirst($role) ?></title>
    <link rel="stylesheet" href="style.css">
    <?php if ($role == 'admin'): ?><script src="https://cdn.jsdelivr.net/npm/chart.js"></script><?php endif; ?>
</head>
<body>
<div class="container">
    <aside class="sidebar">
        <div class="brand">♻️ BANK SAMPAH</div>
        <div class="profile">
            <div class="avatar"><?= strtoupper(substr($nama,0,1)) ?></div>
            <div><b><?= htmlspecialchars($nama) ?></b><small><?= ucfirst($role) ?></small></div>
        </div>
        <div class="menu">
            <a href="dashboard.php" class="active">🏠 Dashboard</a>
            <?php if ($role == 'admin'): ?>
                <p>ADMIN</p>
                <a href="admin/user.php">👥 Data User</a>
                <a href="admin/sampah.php">♻️ Data Sampah</a>
                <a href="admin/transaksi.php">📋 Data Transaksi</a>
                <a href="admin/tarik_saldo.php">💰 Penarikan Saldo</a>
                <a href="admin/laporan.php">📊 Laporan</a>
                <a href="admin/pengaturan.php">⚙️ Pengaturan</a>
            <?php elseif ($role == 'siswa'): ?>
                <p>SISWA</p>
                <a href="siswa/setor.php">♻️ Setor Sampah</a>
                <a href="siswa/riwayat.php">📋 Riwayat Transaksi</a>
                <a href="siswa/saldo.php">💰 Saldo Saya</a>
                <a href="siswa/tarik.php">💸 Tarik Saldo</a>
            <?php elseif ($role == 'guru'): ?>
                <p>GURU</p>
                <a href="guru/setor.php">♻️ Setor Sampah</a>
                <a href="guru/riwayat.php">📋 Riwayat Setoran</a>
                <a href="guru/siswa.php">👨‍🎓 Data Siswa</a>
                <a href="guru/monitoring.php">📊 Monitoring</a>
                <a href="guru/saldo.php">💰 Saldo Saya</a>
                <a href="guru/tarik.php">💸 Tarik Saldo</a>
            <?php else: ?>
                <p>PENGEPUL</p>
                <a href="pengepul/sampah.php">📦 Data Sampah</a>
                <a href="pengepul/pembelian.php">🛒 Pembelian Sampah</a>
                <a href="pengepul/riwayat.php">📋 Riwayat Transaksi</a>
                <a href="pengepul/laporan.php">📊 Laporan Pembelian</a>
                <a href="pengepul/saldo.php">💰 Saldo Saya</a>
            <?php endif; ?>
        </div>
        <a href="logout.php" class="logout">🚪 Logout</a>
    </aside>

    <main class="main">
        <header>
            <div><h1>Dashboard</h1><p>Selamat datang, <b><?= htmlspecialchars($nama) ?></b> 👋</p></div>
            <span class="role"><?= strtoupper($role) ?></span>
        </header>

        <?php if ($role == 'admin'): ?>
            <div class="welcome admin"><h2>Dashboard Administrator 👑</h2><p>Kelola seluruh sistem Bank Sampah melalui halaman administrator.</p></div>
            <div class="cards">
                <div class="card"><span>👥</span><div><small>Total User</small><h2><?= $total_user ?></h2></div></div>
                <div class="card"><span>♻️</span><div><small>Jenis Sampah</small><h2><?= $total_sampah ?></h2></div></div>
                <div class="card"><span>📋</span><div><small>Transaksi</small><h2><?= $total_transaksi ?></h2></div></div>
                <div class="card"><span>💰</span><div><small>Penarikan</small><h2><?= $total_tarik ?></h2></div></div>
            </div>
            <div class="activity">
                <h2>Grafik Transaksi</h2>
                <div style="height:360px;"><canvas id="grafikTransaksi"></canvas></div>
            </div>
        <?php elseif ($role == 'siswa'): ?>
            <div class="welcome"><h2>Dashboard Siswa 🎓</h2><p>Kelola setoran sampah dan saldo Anda.</p></div>
            <div class="cards">
                <div class="card"><span>💰</span><div><small>Saldo Saya</small><h2>Rp <?= number_format($saldo,0,',','.') ?></h2></div></div>
                <div class="card"><span>♻️</span><div><small>Total Setoran Disetujui</small><h2><?= $total_setor ?> Kg</h2></div></div>
                <div class="card"><span>📋</span><div><small>Total Transaksi</small><h2><?= $total_transaksi ?></h2></div></div>
            </div>
        <?php elseif ($role == 'guru'): ?>
            <div class="welcome"><h2>Dashboard Guru 👨‍🏫</h2><p>Pantau aktivitas Bank Sampah dan data siswa.</p></div>
            <div class="cards">
                <div class="card"><span>💰</span><div><small>Saldo Saya</small><h2>Rp <?= number_format($saldo_guru,0,',','.') ?></h2></div></div>
                <div class="card"><span>♻️</span><div><small>Setoran Disetujui</small><h2><?= $total_setor_guru ?> Kg</h2></div></div>
                <div class="card"><span>👨‍🎓</span><div><small>Data Siswa</small><h2><?= $total_siswa ?></h2></div></div>
                <div class="card"><span>📊</span><div><small>Monitoring</small><h2><?= $total_monitoring ?></h2></div></div>
            </div>
        <?php else: ?>
            <div class="welcome pengepul"><h2>Dashboard Pengepul ♻️</h2><p>Kelola pembelian dan data sampah dari Bank Sampah.</p></div>
            <div class="cards">
                <div class="card"><span>💰</span><div><small>Saldo Saya</small><h2>Rp <?= number_format($saldo_pengepul,0,',','.') ?></h2></div></div>
                <div class="card"><span>📦</span><div><small>Stok Sampah</small><h2><?= $total_stok ?> Kg</h2></div></div>
                <div class="card"><span>🛒</span><div><small>Pembelian Disetujui</small><h2><?= $total_pembelian ?></h2></div></div>
                <div class="card"><span>💳</span><div><small>Total Pembelian</small><h2>Rp <?= number_format($total_uang_pembelian,0,',','.') ?></h2></div></div>
            </div>
        <?php endif; ?>

        <div class="activity">
            <h2>Aktivitas Terbaru</h2>
            <?php
            if ($role == 'admin') {
                $aktivitas_sql = "SELECT transaksi.*, user.nama, sampah.jenis_sampah FROM transaksi JOIN user ON transaksi.id_user=user.id_user JOIN sampah ON transaksi.id_sampah=sampah.id_sampah ORDER BY transaksi.tanggal DESC LIMIT 5";
            } elseif ($role == 'siswa' || $role == 'guru' || $role == 'pengepul') {
                $aktivitas_sql = "SELECT transaksi.*, user.nama, sampah.jenis_sampah FROM transaksi JOIN user ON transaksi.id_user=user.id_user JOIN sampah ON transaksi.id_sampah=sampah.id_sampah WHERE transaksi.id_user='$id_user' ORDER BY transaksi.tanggal DESC LIMIT 5";
            }
            $aktivitas = mysqli_query($conn, $aktivitas_sql);
            ?>
            <?php if (mysqli_num_rows($aktivitas) > 0): ?>
                <div class="table-wrap"><table>
                    <tr><th>No</th><th>Nama</th><th>Jenis Sampah</th><th>Transaksi</th><th>Berat</th><th>Total</th><th>Status</th><th>Tanggal</th></tr>
                    <?php $no=1; while($row=mysqli_fetch_assoc($aktivitas)): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= htmlspecialchars($row['nama']) ?></td>
                            <td><?= htmlspecialchars($row['jenis_sampah']) ?></td>
                            <td><?php if($row['tipe']=='setor'): ?><span class="badge setor">♻️ Setor</span><?php else: ?><span class="badge jual">🛒 Jual ke Pengepul</span><?php endif; ?></td>
                            <td><?= $row['berat_kg'] ?> Kg</td>
                            <td>Rp <?= number_format($row['total_rp'],0,',','.') ?></td>
                            <td>
                                <?php if($row['status']=='pending'): ?><span class="badge pending">⏳ Menunggu ACC</span>
                                <?php elseif($row['status']=='disetujui'): ?><span class="badge disetujui">✅ Disetujui</span>
                                <?php else: ?><span class="badge ditolak">❌ Ditolak</span><?php endif; ?>
                            </td>
                            <td><?= $row['tanggal'] ?></td>
                        </tr>
                    <?php endwhile; ?>
                </table></div>
            <?php else: ?><div class="empty">Belum ada aktivitas transaksi.</div><?php endif; ?>
        </div>
    </main>
</div>

<?php if ($role == 'admin'): ?>
<script>
const ctx = document.getElementById('grafikTransaksi');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($grafik_label) ?>,
        datasets: [{
            label: 'Transaksi Disetujui',
            data: <?= json_encode($grafik_data) ?>,
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: { beginAtZero: true, ticks: { precision: 0 } }
        }
    }
});
</script>
<?php endif; ?>

</body>
</html>
