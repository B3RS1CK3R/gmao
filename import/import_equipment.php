<?php
// import/import_equipment.php - Import CSV des équipements
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../index.php?page=login');
    exit();
}

require_once __DIR__ . '/../config/database.php';

$message = '';
$error = '';
$imported_count = 0;
$failed_count = 0;
$failed_rows = [];

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file'];
    
    if($file['error'] != 0) {
        $error = "Erreur lors du téléchargement du fichier";
    } elseif(pathinfo($file['name'], PATHINFO_EXTENSION) != 'csv') {
        $error = "Le fichier doit être au format CSV";
    } else {
        $handle = fopen($file['tmp_name'], 'r');
        if($handle !== false) {
            // Lire l'en-tête
            $headers = fgetcsv($handle, 0, ';');
            $expected_headers = ['code', 'name', 'type', 'location', 'supplier'];
            
            $row_number = 1;
            while(($data = fgetcsv($handle, 0, ';')) !== false) {
                $row_number++;
                
                if(count($data) < 5) {
                    $failed_count++;
                    $failed_rows[] = $row_number;
                    continue;
                }
                
                $code = trim($data[0]);
                $name = trim($data[1]);
                $type = trim($data[2] ?? '');
                $location = trim($data[3] ?? '');
                $supplier = trim($data[4] ?? '');
                
                if(empty($code) || empty($name)) {
                    $failed_count++;
                    $failed_rows[] = $row_number;
                    continue;
                }
                
                try {
                    $stmt = $pdo->prepare("INSERT INTO equipment (code, name, type, location, supplier, status) VALUES (?, ?, ?, ?, ?, 'active')");
                    $stmt->execute([$code, $name, $type, $location, $supplier]);
                    $imported_count++;
                } catch(PDOException $e) {
                    $failed_count++;
                    $failed_rows[] = $row_number;
                }
            }
            fclose($handle);
            
            if($imported_count > 0) {
                logUserAction($_SESSION['user_id'], 'import_equipment', "Import CSV: $imported_count équipements importés");
                $message = "✅ Import terminé : $imported_count équipements importés, $failed_count échecs";
            } else {
                $error = "❌ Aucun équipement importé";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Import CSV - Équipements</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #f4f6f9; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .import-card { background: white; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; overflow: hidden; }
        .import-card-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px 20px; font-weight: bold; }
    </style>
</head>
<body>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-upload"></i> Import CSV - Équipements</h2>
        <a href="../index.php?page=equipment" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Retour
        </a>
    </div>
    
    <?php if($message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if(!empty($failed_rows)): ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i> Échecs sur les lignes : <?php echo implode(', ', $failed_rows); ?>
        </div>
    <?php endif; ?>
    
    <div class="import-card">
        <div class="import-card-header">
            <i class="fas fa-info-circle"></i> Format du fichier CSV
        </div>
        <div class="card-body p-4">
            <p>Le fichier CSV doit utiliser le point-virgule (;) comme séparateur et avoir l'en-tête suivant :</p>
            <pre class="bg-light p-3 rounded">code;name;type;location;supplier</pre>
            <p>Exemple de données :</p>
            <pre class="bg-light p-3 rounded">MAC-001;Machine CNC;Fraiseuse;Atelier A;Mazak
PRE-002;Presse hydraulique;Presse;Atelier B;Hydram</pre>
            <div class="alert alert-info">
                <i class="fas fa-download"></i> <a href="exemple_equipements.csv" download> télécharger un fichier exemple</a>
            </div>
        </div>
    </div>
    
    <div class="import-card">
        <div class="import-card-header">
            <i class="fas fa-upload"></i> Importer des équipements
        </div>
        <div class="card-body p-4">
            <form method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label">Fichier CSV <span class="text-danger">*</span></label>
                    <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-upload"></i> Importer
                    </button>
                    <a href="../index.php?page=equipment" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Annuler
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>