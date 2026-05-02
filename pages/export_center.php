<?php
// pages/export_center.php - Centre d'exportation
if(!isset($_SESSION['user_id'])) {
    header('Location: index.php?page=login');
    exit();
}
?>

<style>
    .export-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        margin-bottom: 20px;
        overflow: hidden;
        transition: transform 0.2s;
    }
    .export-card:hover {
        transform: translateY(-5px);
    }
    .export-card-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 15px 20px;
        font-weight: bold;
    }
    .export-card-header.excel { background: linear-gradient(135deg, #1e7e34, #28a745); }
    .export-card-header.pdf { background: linear-gradient(135deg, #dc3545, #c82333); }
    .export-card-header.csv { background: linear-gradient(135deg, #fd7e14, #e06a0a); }
    .export-card-header.info { background: linear-gradient(135deg, #17a2b8, #138496); }
    .filter-group {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 10px;
        margin-top: 15px;
    }
    .btn-excel {
        background: linear-gradient(135deg, #1e7e34, #28a745);
        border: none;
        border-radius: 8px;
        padding: 10px 20px;
        color: white;
        width: 100%;
    }
    .btn-excel:hover {
        filter: brightness(0.95);
        color: white;
    }
    .btn-pdf {
        background: linear-gradient(135deg, #dc3545, #c82333);
        border: none;
        border-radius: 8px;
        padding: 10px 20px;
        color: white;
        width: 100%;
    }
    .btn-pdf:hover {
        filter: brightness(0.95);
        color: white;
    }
    .btn-csv {
        background: linear-gradient(135deg, #fd7e14, #e06a0a);
        border: none;
        border-radius: 8px;
        padding: 10px 20px;
        color: white;
        width: 100%;
    }
    .btn-csv:hover {
        filter: brightness(0.95);
        color: white;
    }
    .btn-info-custom {
        background: linear-gradient(135deg, #17a2b8, #138496);
        border: none;
        border-radius: 8px;
        padding: 10px 20px;
        color: white;
        width: 100%;
    }
    .btn-info-custom:hover {
        filter: brightness(0.95);
        color: white;
    }
    .btn-secondary-custom {
        background: #6c757d;
        border: none;
        border-radius: 8px;
        padding: 8px 20px;
        color: white;
    }
    .btn-secondary-custom:hover {
        background: #5a6268;
        color: white;
    }
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-download"></i> <?php echo t('export_center'); ?></h2>
        <a href="?page=dashboard" class="btn btn-secondary-custom">
            <i class="fas fa-arrow-left"></i> <?php echo t('back'); ?>
        </a>
    </div>
    
    <div class="row">
        <!-- Export Interventions Excel -->
        <div class="col-md-6">
            <div class="export-card">
                <div class="export-card-header excel">
                    <i class="fas fa-file-excel"></i> <?php echo t('export_interventions_excel'); ?>
                </div>
                <div class="card-body p-4">
                    <p><?php echo t('export_interventions_desc'); ?></p>
                    <form action="../export/export_interventions_excel.php" method="GET" target="_blank">
                        <div class="filter-group">
                            <div class="row">
                                <div class="col-md-6 mb-2">
                                    <label><?php echo t('type'); ?></label>
                                    <select name="type" class="form-select">
                                        <option value="all"><?php echo t('all'); ?></option>
                                        <option value="corrective"><?php echo t('corrective'); ?></option>
                                        <option value="preventive"><?php echo t('preventive'); ?></option>
                                        <option value="emergency"><?php echo t('emergency'); ?></option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label><?php echo t('status'); ?></label>
                                    <select name="status" class="form-select">
                                        <option value="all"><?php echo t('all'); ?></option>
                                        <option value="a_faire"><?php echo t('to_do'); ?></option>
                                        <option value="en_cours"><?php echo t('in_progress'); ?></option>
                                        <option value="termine"><?php echo t('completed'); ?></option>
                                        <option value="cloturee"><?php echo t('closed'); ?></option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="submit" class="btn-excel">
                                <i class="fas fa-download"></i> <?php echo t('export_excel'); ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Export Équipements Excel -->
        <div class="col-md-6">
            <div class="export-card">
                <div class="export-card-header excel">
                    <i class="fas fa-file-excel"></i> <?php echo t('export_equipment_excel'); ?>
                </div>
                <div class="card-body p-4">
                    <p><?php echo t('export_equipment_desc'); ?></p>
                    <form action="../export/export_equipment_excel.php" method="GET" target="_blank">
                        <div class="filter-group">
                            <div class="row">
                                <div class="col-md-12 mb-2">
                                    <label><?php echo t('status'); ?></label>
                                    <select name="status" class="form-select">
                                        <option value="all"><?php echo t('all'); ?></option>
                                        <option value="active"><?php echo t('active'); ?></option>
                                        <option value="maintenance"><?php echo t('maintenance'); ?></option>
                                        <option value="broken"><?php echo t('broken'); ?></option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="submit" class="btn-excel">
                                <i class="fas fa-download"></i> <?php echo t('export_excel'); ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Export Stock Excel -->
        <div class="col-md-6">
            <div class="export-card">
                <div class="export-card-header excel">
                    <i class="fas fa-file-excel"></i> <?php echo t('export_stock_excel'); ?>
                </div>
                <div class="card-body p-4">
                    <p><?php echo t('export_stock_desc'); ?></p>
                    <form action="../export/export_stock_excel.php" method="GET" target="_blank">
                        <div class="filter-group">
                            <div class="row">
                                <div class="col-md-12 mb-2">
                                    <label><?php echo t('status'); ?></label>
                                    <select name="status" class="form-select">
                                        <option value="all"><?php echo t('all'); ?></option>
                                        <option value="critical"><?php echo t('critical_stock'); ?></option>
                                        <option value="warning"><?php echo t('to_monitor'); ?></option>
                                        <option value="ok"><?php echo t('sufficient'); ?></option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="submit" class="btn-excel">
                                <i class="fas fa-download"></i> <?php echo t('export_excel'); ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Export PDF Intervention -->
        <div class="col-md-6">
            <div class="export-card">
                <div class="export-card-header pdf">
                    <i class="fas fa-file-pdf"></i> <?php echo t('export_pdf_report'); ?>
                </div>
                <div class="card-body p-4">
                    <p><?php echo t('export_pdf_desc'); ?></p>
                    <div class="filter-group">
                        <div class="row">
                            <div class="col-md-12 mb-2">
                                <label><?php echo t('task_number_or_id'); ?></label>
                                <div class="input-group">
                                    <input type="text" id="intervention_id" class="form-control" placeholder="<?php echo t('task_number_placeholder'); ?>">
                                    <button class="btn-pdf" onclick="generatePDF()">
                                        <i class="fas fa-file-pdf"></i> <?php echo t('generate'); ?>
                                    </button>
                                </div>
                                <small class="text-muted"><?php echo t('export_pdf_help'); ?></small>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <a href="?page=interventions" class="btn btn-secondary-custom w-100">
                            <i class="fas fa-list"></i> <?php echo t('view_interventions'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Import CSV Équipements -->
        <div class="col-md-6">
            <div class="export-card">
                <div class="export-card-header csv">
                    <i class="fas fa-upload"></i> <?php echo t('import_csv'); ?>
                </div>
                <div class="card-body p-4">
                    <p><?php echo t('import_csv_desc'); ?></p>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> <?php echo t('csv_format_info'); ?>
                        <code>code;name;type;location;supplier</code>
                    </div>
                    <div class="mt-3">
                        <a href="../import/import_equipment.php" class="btn-csv w-100">
                            <i class="fas fa-upload"></i> <?php echo t('access_import'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Export CSV Interventions -->
        <div class="col-md-6">
            <div class="export-card">
                <div class="export-card-header csv">
                    <i class="fas fa-file-csv"></i> <?php echo t('export_csv_interventions'); ?>
                </div>
                <div class="card-body p-4">
                    <p><?php echo t('export_csv_desc'); ?></p>
                    <div class="mt-3">
                        <a href="../export/export_interventions_csv.php" class="btn-csv w-100">
                            <i class="fas fa-download"></i> <?php echo t('export_csv'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Export iCal Calendrier -->
        <div class="col-md-6">
            <div class="export-card">
                <div class="export-card-header info">
                    <i class="fas fa-calendar-alt"></i> <?php echo t('export_ical'); ?>
                </div>
                <div class="card-body p-4">
                    <p><?php echo t('export_ical_desc'); ?></p>
                    <div class="mt-3">
                        <button class="btn-info-custom" onclick="exportICal()">
                            <i class="fas fa-download"></i> <?php echo t('export_ical'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Section informations -->
    <div class="row mt-3">
        <div class="col-md-12">
            <div class="export-card">
                <div class="export-card-header">
                    <i class="fas fa-info-circle"></i> <?php echo t('information'); ?>
                </div>
                <div class="card-body p-4">
                    <div class="row">
                        <div class="col-md-6">
                            <h6><i class="fas fa-file-excel text-success"></i> <?php echo t('excel_exports'); ?></h6>
                            <p class="small text-muted"><?php echo t('excel_exports_desc'); ?></p>
                        </div>
                        <div class="col-md-6">
                            <h6><i class="fas fa-file-pdf text-danger"></i> <?php echo t('pdf_reports'); ?></h6>
                            <p class="small text-muted"><?php echo t('pdf_reports_desc'); ?></p>
                        </div>
                        <div class="col-md-6">
                            <h6><i class="fas fa-upload text-warning"></i> <?php echo t('csv_imports'); ?></h6>
                            <p class="small text-muted"><?php echo t('csv_imports_desc'); ?></p>
                        </div>
                        <div class="col-md-6">
                            <h6><i class="fas fa-calendar-alt text-info"></i> <?php echo t('ical_exports'); ?></h6>
                            <p class="small text-muted"><?php echo t('ical_exports_desc'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function generatePDF() {
    const id = document.getElementById('intervention_id').value;
    if(id) {
        window.open('../export/export_intervention_pdf.php?id=' + id, '_blank');
    } else {
        alert('<?php echo t('enter_id_warning'); ?>');
    }
}

function exportICal() {
    window.open('../export/ical_export.php', '_blank');
}
</script>