<?php
ob_start();
include_once 'connectdb.php';
session_start();

// ================= SESSION CHECK =================
if (!isset($_SESSION['useremail']) || $_SESSION['role'] == "User") {
    header('location:../index.php');
    exit();
}

// ================= SETTINGS =================
$db_user = "root";
$db_pass = "";
$db_name = "custodian_db";

$baseDir    = realpath(__DIR__);
$backupDir  = $baseDir . DIRECTORY_SEPARATOR . "backup" . DIRECTORY_SEPARATOR;
$archiveDir = $backupDir . "archive" . DIRECTORY_SEPARATOR;
$systemDir  = realpath($baseDir . "/../");

// ================= PROGRESS SYSTEM =================
$progressFile = $backupDir . "progress.json";

function updateProgress($percent, $message){
    global $progressFile;
    file_put_contents($progressFile, json_encode([
        'percent'=>$percent,
        'message'=>$message,
        'time'=>time()
    ]));
}

// Ensure folders exist and are writable
if (!is_dir($backupDir)) mkdir($backupDir, 0755, true);
if (!is_dir($archiveDir)) mkdir($archiveDir, 0755, true);

if (!is_writable($backupDir)) {
    // If we can't write to the backup dir, we should log it or handle it
}


// ================= ACTION HANDLERS =================

// ================= PROGRESS API =================
if (isset($_GET['progress'])) {
    if (file_exists($progressFile)) {
        echo file_get_contents($progressFile);
    } else {
        echo json_encode(['percent'=>0,'message'=>'Starting...']);
    }
    exit();
}
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    
    // For direct downloads, we don't want JSON header yet
    if (isset($_GET['direct'])) {
        // Direct download flow
        set_time_limit(0);
        ini_set('memory_limit', '1024M');
    } else {
        // AJAX flow
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');
    }

    // Check if exec is enabled
    if (!function_exists('exec')) {
        $msg = 'The PHP exec() function is disabled on this server.';
        if (isset($_GET['direct'])) die($msg);
        echo json_encode(['status' => 'error', 'message' => $msg]);
        exit();
    }

    // Check if backup directory is writable
    if (!is_writable($backupDir)) {
        $msg = 'The backup directory is not writable: ' . $backupDir;
        if (isset($_GET['direct'])) die($msg);
        echo json_encode(['status' => 'error', 'message' => $msg]);
        exit();
    }

    if ($action == 'backup_db') {
        updateProgress(10, "Initializing database...");
        
        // Close session to prevent locking while the backup runs
        session_write_close();
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        $sqlName = "db_backup_" . date("Y-m-d_H-i-s") . ".sql";
        $sqlPath = $backupDir . $sqlName;
        
        updateProgress(30, "Connecting to database...");
        
        $mysqldump = "C:/xampp/mysql/bin/mysqldump.exe";
        if (!file_exists($mysqldump)) $mysqldump = "mysqldump";

        $db_pass_arg = ($db_pass !== "") ? "-p$db_pass" : "";
        $command = "\"$mysqldump\" --host=localhost --user=\"$db_user\" $db_pass_arg \"$db_name\" > \"$sqlPath\"";
        
        updateProgress(60, "Exporting SQL data...");
        exec($command . " 2>&1", $output, $result);

        if ($result === 0 && file_exists($sqlPath) && filesize($sqlPath) > 0) {
            updateProgress(100, "Database backup complete!");
            
            session_start();
            logActivity($pdo, "Database SQL Backup Created: " . $sqlName);
            
            if (isset($_GET['direct'])) {
                header("Location: utilities.php?download=" . urlencode($sqlName));
                exit();
            }
            
            echo json_encode(['status' => 'success', 'file' => $sqlName]);
            exit();
        } else {
            updateProgress(0, "Error: Backup failed.");
            $errorMsg = "Database backup failed. Result code: $result. ";
            if (isset($_GET['direct'])) die($errorMsg . implode("\n", $output));
            echo json_encode(['status' => 'error', 'message' => $errorMsg, 'details' => implode("\n", $output)]);
            exit();
        }
    }

    if ($action == 'backup_system') {
        if (!class_exists('ZipArchive')) {
            $msg = 'ZipArchive class not found.';
            if (isset($_GET['direct'])) die($msg);
            echo json_encode(['status' => 'error', 'message' => $msg]);
            exit();
        }

        updateProgress(5, "Counting files...");
        session_write_close();
        set_time_limit(0); 
        ini_set('memory_limit', '1024M');

        $fileName = "system_backup_" . date("Y-m-d_H-i-s") . ".zip";
        $finalZipPath = $backupDir . $fileName;
        
        $zip = new ZipArchive();
        if ($zip->open($finalZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($systemDir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::LEAVES_ONLY
            );

            $totalFiles = iterator_count($files);
            $files->rewind();

            $processed = 0;
            $startTime = time();

            foreach ($files as $name => $file) {
                $processed++;
                // Scale progress from 10% to 90%
                $percent = 10 + intval(($processed / $totalFiles) * 80);

                if ($processed % 50 == 0) { // Update every 50 files to reduce I/O
                    $elapsed = time() - $startTime;
                    $speed = $processed / max($elapsed, 1);
                    $remaining = ($totalFiles - $processed) / max($speed, 1);
                    $eta = gmdate("i:s", $remaining);
                    updateProgress($percent, "Adding files: $processed/$totalFiles | Estimated Time: $eta");
                }

                if (!$file->isDir()) {
                    $filePath = $file->getRealPath();
                    $relativePath = substr($filePath, strlen($systemDir) + 1);
                    $normalizedPath = str_replace('\\', '/', $relativePath);

                    if (
                        strpos($normalizedPath, 'ui/backup') !== false ||
                        strpos($normalizedPath, 'ui/productimage') !== false ||
                        strpos($normalizedPath, 'ui/uploads') !== false ||
                        strpos($normalizedPath, 'node_modules') !== false ||
                        strpos($normalizedPath, 'vendor') !== false
                    ) continue;

                    $zip->addFile($filePath, $relativePath);
                }
            }
            
            updateProgress(95, "Finalizing ZIP archive (this may take a moment)...");
            $zip->close();

            if (file_exists($finalZipPath) && filesize($finalZipPath) > 0) {
                updateProgress(100, "Backup complete! Preparing download...");
                session_start();
                logActivity($pdo, "System Zip Backup Created: " . $fileName);
                
                if (isset($_GET['direct'])) {
                    header("Location: utilities.php?download=" . urlencode($fileName));
                    exit();
                }
                
                echo json_encode(['status' => 'success', 'file' => $fileName]);
                exit();
            }
        }
        updateProgress(0, "Error: System backup failed.");
        $msg = 'System backup failed.';
        if (isset($_GET['direct'])) die($msg);
        echo json_encode(['status' => 'error', 'message' => $msg]);
        exit();
    }
}

// ================= DOWNLOAD HANDLER =================
if (isset($_GET['download'])) {
    $file = basename($_GET['download']);
    $filePath = $backupDir . $file;
    if (file_exists($filePath)) {
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        $contentType = ($ext === 'zip') ? 'application/zip' : 'application/octet-stream';
        if ($ext === 'sql') $contentType = 'application/sql';

        while (ob_get_level()) ob_end_clean();
        header('Content-Description: File Transfer');
        header('Content-Type: ' . $contentType);
        header('Content-Disposition: attachment; filename="' . $file . '"');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit();
    }
    die("File not found.");
}

// ================= RESTORE DATABASE =================
if (isset($_POST['btnRestore']) && isset($_FILES['restore_file'])) {
    $fileTmp = $_FILES['restore_file']['tmp_name'];
    if (pathinfo($_FILES['restore_file']['name'], PATHINFO_EXTENSION) !== 'sql') {
        $_SESSION['status'] = "Invalid file type!";
        $_SESSION['status_code'] = "danger";
    } else {
        $mysql = "C:/xampp/mysql/bin/mysql.exe";
        if (!file_exists($mysql)) $mysql = "mysql";
        $command = "\"$mysql\" -u $db_user " . ($db_pass ? "-p$db_pass " : "") . "$db_name < \"$fileTmp\"";
        exec($command . " 2>&1", $output, $result);
        if ($result === 0) {
            $_SESSION['status'] = "Database restored successfully!";
            $_SESSION['status_code'] = "success";
            logActivity($pdo, "Database Restored");
        } else {
            $_SESSION['status'] = "Restore failed!";
            $_SESSION['status_code'] = "danger";
        }
    }
    header("Location: utilities.php");
    exit();
}

// ================= RESTORE SYSTEM FILES (EXTRACT ZIP) =================
if (isset($_POST['btnRestoreSystem']) && isset($_FILES['restore_system_file'])) {
    if (!class_exists('ZipArchive')) {
        $_SESSION['status'] = "ZipArchive class not found!";
        $_SESSION['status_code'] = "danger";
    } else {
        $fileTmp = $_FILES['restore_system_file']['tmp_name'];
        if (pathinfo($_FILES['restore_system_file']['name'], PATHINFO_EXTENSION) !== 'zip') {
            $_SESSION['status'] = "Invalid file type! Please upload a ZIP file.";
            $_SESSION['status_code'] = "danger";
        } else {
            $zip = new ZipArchive;
            if ($zip->open($fileTmp) === TRUE) {
                // Extract to system directory (root of the project)
                // Warning: This will overwrite existing files
                $zip->extractTo($systemDir);
                $zip->close();
                
                $_SESSION['status'] = "System files restored/extracted successfully!";
                $_SESSION['status_code'] = "success";
                logActivity($pdo, "System Files Restored from ZIP");
            } else {
                $_SESSION['status'] = "Failed to open ZIP file!";
                $_SESSION['status_code'] = "danger";
            }
        }
    }
    header("Location: utilities.php");
    exit();
}
?>

<!-- Header -->
<?php 
if ($_SESSION['role'] == "Admin") {
    include_once "header.php";
} else {
    include_once "headeruser.php";
}
?>

<style>
    .glass-3d-popup {
        background: rgba(255, 255, 255, 0.05) !important;
        backdrop-filter: blur(25px) saturate(180%) !important;
        -webkit-backdrop-filter: blur(25px) saturate(180%) !important;
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        border-radius: 40px !important;
        box-shadow: 0 40px 100px rgba(0, 0, 0, 0.6) !important;
        padding: 50px 40px !important;
        overflow: visible !important;
    }
    .swal2-title {
        color: #fff !important;
        text-shadow: 0 10px 20px rgba(0,0,0,0.5) !important;
        font-weight: 900 !important;
        font-size: 2.2rem !important;
        letter-spacing: -1px;
        margin-bottom: 25px !important;
    }
    .loader-3d-container {
        perspective: 1200px;
        margin-top: 40px;
        margin-bottom: 30px;
        height: 180px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    #glass-circle-3d {
        transform: rotateX(25deg) rotateY(0deg);
        transform-style: preserve-3d;
        width: 150px;
        height: 150px;
        position: relative;
    }
    .glass-layer {
        position: absolute;
        inset: 0;
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 50%;
        backdrop-filter: blur(5px);
    }
    .layer-front { transform: translateZ(40px); background: rgba(255,255,255,0.08); }
    .layer-mid { transform: translateZ(0px); background: rgba(255,255,255,0.03); }
    .layer-back { transform: translateZ(-40px); background: rgba(255,255,255,0.01); }
    
    #status-text {
        font-size: 1.2rem !important;
        letter-spacing: 0.5px;
        margin-top: 15px !important;
    }
</style>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark">
                        <a href="javascript:history.back()" class="btn btn-secondary btn-sm mr-2"><i class="fas fa-arrow-left"></i></a>
                        System Utilities
                    </h1>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">

            <?php if(isset($_SESSION['status'])): ?>
                <div class="alert alert-<?=$_SESSION['status_code']?> alert-dismissible fade show">
                    <i class="icon fas <?=$_SESSION['status_code']=='success'?'fa-check':'fa-ban'?>"></i>
                    <?= $_SESSION['status'] ?>
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            <?php unset($_SESSION['status'], $_SESSION['status_code']); endif; ?>

            <div class="row">
                <!-- Database Management -->
                <div class="col-lg-4 col-md-6">
                    <div class="card card-primary card-outline h-100">
                        <div class="card-header"><h3 class="card-title"><i class="fas fa-database mr-2"></i>Database</h3></div>
                        <div class="card-body d-flex flex-column">
                            <p class="text-muted small">Export your current database to an SQL file.</p>
                            <a href="utilities.php?action=backup_db&direct=1" class="btn btn-primary btn-block mb-3">
                                <i class="fas fa-download mr-2"></i>Backup Database
                            </a>
                            <div class="mt-auto">
                                <hr>
                                <form method="post" enctype="multipart/form-data">
                                    <div class="form-group mb-2">
                                        <div class="custom-file">
                                            <input type="file" name="restore_file" class="custom-file-input" id="restoreFile" required>
                                            <label class="custom-file-label" for="restoreFile">Restore SQL...</label>
                                        </div>
                                    </div>
                                    <button name="btnRestore" class="btn btn-warning btn-block">
                                        <i class="fas fa-upload mr-2"></i>Restore Database
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- System Maintenance -->
                <div class="col-lg-4 col-md-6">
                    <div class="card card-info card-outline h-100">
                        <div class="card-header"><h3 class="card-title"><i class="fas fa-file-archive mr-2"></i>System Files</h3></div>
                        <div class="card-body d-flex flex-column">
                            <p class="text-muted small">Backup all project files (inventory_custodian folder).</p>
                            <a href="utilities.php?action=backup_system&direct=1" class="btn btn-info btn-block mb-3">
                                <i class="fas fa-archive mr-2"></i>Backup System Files
                            </a>
                            <div class="mt-auto">
                                <hr>
                                <form method="post" enctype="multipart/form-data">
                                    <div class="form-group mb-2">
                                        <div class="custom-file">
                                            <input type="file" name="restore_system_file" class="custom-file-input" id="restoreSystemFile" required>
                                            <label class="custom-file-label" for="restoreSystemFile">Restore ZIP...</label>
                                        </div>
                                    </div>
                                    <button name="btnRestoreSystem" class="btn btn-warning btn-block">
                                        <i class="fas fa-upload mr-2"></i>Extract/Restore System
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Log Management -->
                <div class="col-lg-4 col-md-12">
                    <div class="card card-dark card-outline h-100">
                        <div class="card-header"><h3 class="card-title"><i class="fas fa-history mr-2"></i>Activity & Archives</h3></div>
                        <div class="card-body">
                            <p class="text-muted small">Manage activity logs and archived data records.</p>
                            <a href="activitylog.php" class="btn btn-outline-primary btn-block mb-2">
                                <i class="fas fa-list-ul mr-2"></i>Activity Log
                            </a>
                            <a href="archive.php" class="btn btn-outline-dark btn-block">
                                <i class="fas fa-archive mr-2"></i>Archive Management
                            </a>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>
</div>

<?php include_once "footer.php"; ?>

<script>
$(document).ready(function() {
    $('#restoreFile').on('change', function() {
        var fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').html(fileName);
    });
    
    $('#restoreSystemFile').on('change', function() {
        var fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').html(fileName);
    });
});
</script>

<!-- SweetAlert2 CDN (if not yet included) -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
function startBackup(url, title) {
    let interval;
    
    Swal.fire({
        title: title,
        html: `
            <div class="loader-3d-container">
                <div id="glass-circle-3d">
                    <div class="glass-layer layer-back"></div>
                    <div class="glass-layer layer-mid"></div>
                    <div class="glass-layer layer-front"></div>
                    <svg viewBox="0 0 36 36" style="width:100%; height:100%; position:absolute; inset:0; z-index:10; filter: drop-shadow(0 15px 35px rgba(0,0,0,0.8));">
                        <defs>
                            <linearGradient id="grad-green" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" style="stop-color:#2ecc71;stop-opacity:1" />
                                <stop offset="100%" style="stop-color:#27ae60;stop-opacity:1" />
                            </linearGradient>
                        </defs>
                        <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="rgba(255,255,255,0.05)" stroke-width="4" />
                        <path id="progress-circle" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="url(#grad-green)" stroke-width="3.5" stroke-dasharray="0, 100" stroke-linecap="round" style="transition: stroke-dasharray 0.4s cubic-bezier(0.4, 0, 0.2, 1); filter: drop-shadow(0 0 15px rgba(46,204,113,0.8));" />
                    </svg>
                    <div id="progress-text-3d" style="position:absolute; top:50%; left:50%; transform:translate(-50%, -50%) translateZ(60px); font-weight:900; font-size:2.5rem; color: #fff; text-shadow: 0 10px 20px rgba(0,0,0,0.8);">0%</div>
                </div>
            </div>
            <div id="status-text-3d" class="mt-4" style="color: rgba(255,255,255,0.95); font-weight: 600; font-size: 1.2rem; text-shadow: 0 4px 10px rgba(0,0,0,0.5);">Initializing...</div>
        `,
        background: 'transparent',
        customClass: { 
            popup: 'glass-3d-popup',
            cancelButton: 'btn btn-danger btn-lg px-5 mt-4'
        },
        allowOutsideClick: false,
        showConfirmButton: false,
        showCancelButton: true,
        cancelButtonText: '<i class="fas fa-times mr-2"></i>Cancel Process',
        buttonsStyling: false,
        didOpen: () => {
            const container = document.getElementById('glass-circle-3d');
            const circle = document.getElementById('progress-circle');
            const textEl = document.getElementById('progress-text-3d');
            const statusEl = document.getElementById('status-text-3d');

            // Floating 3D animation
            let animInterval = setInterval(() => {
                if (container) {
                    const rotX = 25 + Math.sin(Date.now()/500) * 8;
                    const rotY = Math.cos(Date.now()/500) * 8;
                    container.style.transform = `rotateX(${rotX}deg) rotateY(${rotY}deg)`;
                }
            }, 50);

            // START BACKUP
            const controller = new AbortController();
            fetch(url.replace('&direct=1',''), { signal: controller.signal });

            interval = setInterval(() => {
                fetch('utilities.php?progress=1')
                .then(res => res.json())
                .then(data => {
                    if (circle) circle.setAttribute('stroke-dasharray', `${data.percent}, 100`);
                    if (textEl) textEl.innerText = `${data.percent}%`;
                    if (statusEl) statusEl.innerText = data.message;

                    if (data.percent >= 100) {
                        clearInterval(interval);
                        clearInterval(animInterval);
                        startDownload(url);
                    }
                });
            }, 500);

            // Store interval to clear on manual close
            Swal.getCancelButton().addEventListener('click', () => {
                controller.abort();
                clearInterval(interval);
                clearInterval(animInterval);
            });
        }
    });
}

// ================= DOWNLOAD WITH PROGRESS =================
function startDownload(url) {
    let xhr = new XMLHttpRequest();
    
    Swal.fire({
        title: "Downloading Backup...",
        html: `
            <div class="loader-3d-container">
                <div id="download-circle-3d">
                    <div class="glass-layer layer-back"></div>
                    <div class="glass-layer layer-mid"></div>
                    <div class="glass-layer layer-front"></div>
                    <svg viewBox="0 0 36 36" style="width:100%; height:100%; position:absolute; inset:0; z-index:10; filter: drop-shadow(0 15px 35px rgba(0,0,0,0.8));">
                        <defs>
                            <linearGradient id="grad-orange" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" style="stop-color:#ff9966;stop-opacity:1" />
                                <stop offset="100%" style="stop-color:#ff5e62;stop-opacity:1" />
                            </linearGradient>
                        </defs>
                        <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="rgba(255,255,255,0.05)" stroke-width="4" />
                        <path id="download-circle" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="url(#grad-orange)" stroke-width="3.5" stroke-dasharray="0, 100" stroke-linecap="round" style="transition: stroke-dasharray 0.1s linear; filter: drop-shadow(0 0 15px rgba(255,153,102,0.8));" />
                    </svg>
                    <div id="download-percent-3d" style="position:absolute; top:50%; left:50%; transform:translate(-50%, -50%) translateZ(60px); font-weight:900; font-size:2.5rem; color: #fff; text-shadow: 0 10px 20px rgba(0,0,0,0.8);">0%</div>
                </div>
            </div>
            <div style="display:flex; justify-content:space-between; margin-top:20px; color:rgba(255,255,255,0.8); font-size:0.9rem;">
                <span id="download-speed-3d">0.00 MB/s</span>
                <span id="download-eta-3d">Remaining: --:--</span>
            </div>
            <p id="download-status-3d" style="margin-top:10px; color:#fff; font-weight:bold;">Preparing...</p>
        `,
        background: 'transparent',
        customClass: { 
            popup: 'glass-3d-popup',
            cancelButton: 'btn btn-danger btn-lg px-5 mt-4'
        },
        allowOutsideClick: false,
        showConfirmButton: false,
        showCancelButton: true,
        cancelButtonText: '<i class="fas fa-times mr-2"></i>Cancel Download',
        buttonsStyling: false,
        didOpen: () => {
            const container = document.getElementById('download-circle-3d');
            const circle = document.getElementById('download-circle');
            const textEl = document.getElementById('download-percent-3d');
            const speedEl = document.getElementById('download-speed-3d');
            const etaEl = document.getElementById('download-eta-3d');
            const statusEl = document.getElementById('download-status-3d');

            let startTime = Date.now();
            let lastLoaded = 0;
            let lastTime = Date.now();

            // 3D floating effect
            let animInterval = setInterval(() => {
                if (container) {
                    const rotX = 25 + Math.sin(Date.now()/500) * 8;
                    const rotY = Math.cos(Date.now()/500) * 8;
                    container.style.transform = `rotateX(${rotX}deg) rotateY(${rotY}deg)`;
                }
            }, 50);

            xhr.open("GET", url, true);
            xhr.responseType = "blob";

            xhr.onprogress = function(e) {
                if (e.lengthComputable) {
                    let now = Date.now();
                    let duration = (now - startTime) / 1000;
                    let loaded = e.loaded;
                    let total = e.total;
                    
                    let timeDiff = (now - lastTime) / 1000;
                    let loadedDiff = loaded - lastLoaded;
                    let speedBps = timeDiff > 0 ? loadedDiff / timeDiff : 0;
                    let speedMBps = (speedBps / 1024 / 1024).toFixed(2);
                    
                    let avgSpeedBps = loaded / duration;
                    let remainingBytes = total - loaded;
                    let remainingSeconds = avgSpeedBps > 0 ? remainingBytes / avgSpeedBps : 0;
                    
                    let percent = (loaded / total) * 100;
                    let minutes = Math.floor(remainingSeconds / 60);
                    let seconds = Math.floor(remainingSeconds % 60);
                    let eta = (minutes < 10 ? "0" : "") + minutes + ":" + (seconds < 10 ? "0" : "") + seconds;

                    if (circle) circle.setAttribute('stroke-dasharray', `${percent}, 100`);
                    if (textEl) textEl.innerText = Math.round(percent) + "%";
                    if (speedEl) speedEl.innerText = speedMBps + " MB/s";
                    if (etaEl) etaEl.innerText = "Estimated Time: " + eta;
                    if (statusEl) statusEl.innerText = "Downloaded " + (loaded / 1024 / 1024).toFixed(2) + " MB of " + (total / 1024 / 1024).toFixed(2) + " MB";
                    
                    lastLoaded = loaded;
                    lastTime = now;
                }
            };

            xhr.onload = function() {
                clearInterval(animInterval);
                if (xhr.status === 200) {
                    let blob = xhr.response;
                    let a = document.createElement("a");
                    let downloadUrl = window.URL.createObjectURL(blob);
                    a.href = downloadUrl;
                    let fileName = url.includes("backup_db") ? "database_backup.sql" : "system_backup.zip";
                    a.download = fileName;
                    document.body.appendChild(a);
                    a.click();
                    a.remove();
                    window.URL.revokeObjectURL(downloadUrl);

                    Swal.fire({
                        icon: 'success',
                        title: 'Download Completed!',
                        text: 'Your backup file has been saved.',
                        timer: 3000,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire('Error!', 'Download failed.', 'error');
                }
            };

            xhr.onerror = function() {
                clearInterval(animInterval);
                Swal.fire('Error!', 'Network error during download.', 'error');
            };

            xhr.send();

            Swal.getCancelButton().addEventListener('click', () => {
                xhr.abort();
                clearInterval(animInterval);
            });
        }
    });
}


// ================= BUTTON HOOKS =================
document.querySelector('a[href*="backup_db"]').addEventListener('click', function(e){
    e.preventDefault();
    startBackup(this.href, 'Backing up Database...');
});

document.querySelector('a[href*="backup_system"]').addEventListener('click', function(e){
    e.preventDefault();
    startBackup(this.href, 'Backing up System Files...');
});
</script>