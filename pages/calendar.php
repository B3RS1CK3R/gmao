<?php
// pages/calendar.php - Planning visuel type calendrier
// Alignement avec la charte graphique du projet

if(!isset($_SESSION['user_id'])) {
    header('Location: index.php?page=login');
    exit();
}

// Récupérer toutes les interventions avec date
$stmt = $pdo->query("
    SELECT i.*, e.name as equipment_name, e.code as equipment_code,
        t.firstname, t.lastname, t.specialty
    FROM interventions i 
    JOIN equipment e ON i.equipment_id = e.id 
    LEFT JOIN technicians t ON i.intervenant_id = t.id
    WHERE i.intervention_date IS NOT NULL
    ORDER BY i.intervention_date ASC
");
$interventions = $stmt->fetchAll();

// Récupérer les techniciens pour le filtre
$technicians = $pdo->query("SELECT id, firstname, lastname FROM technicians WHERE status = 'active' ORDER BY lastname")->fetchAll();

// Couleurs par priorité
$color_map = [
    'critical' => '#dc3545',
    'high' => '#fd7e14',
    'medium' => '#ffc107',
    'low' => '#28a745'
];

// Event styles
$event_class_map = [
    'critical' => 'fc-event-critical',
    'high' => 'fc-event-high',
    'medium' => 'fc-event-medium',
    'low' => 'fc-event-low'
];

$unassigned_text = t('unassigned');
?>

<!-- FullCalendar Dependencies -->
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales/fr.js"></script>
<script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js"></script>

<style>
    .calendar-container {
        background: white;
        border-radius: 15px;
        padding: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    .fc-event { cursor: pointer; transition: transform 0.2s, filter 0.2s; border: none !important; border-radius: 6px !important; padding: 2px 4px !important; font-size: 12px !important; }
    .fc-event:hover { transform: scale(1.02); filter: brightness(0.95); }
    .fc-event-critical { background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); border-left: 3px solid white !important; }
    .fc-event-high { background: linear-gradient(135deg, #fd7e14 0%, #e06a0a 100%); border-left: 3px solid white !important; }
    .fc-event-medium { background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%); color: #333 !important; }
    .fc-event-low { background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%); }
    .fc-toolbar-title { font-size: 1.2rem !important; font-weight: bold !important; }
    .fc-button-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important; border: none !important; }
    .fc-button-primary:hover { filter: brightness(0.95) !important; }
    .legend { display: flex; gap: 20px; margin-bottom: 20px; flex-wrap: wrap; background: white; padding: 10px 20px; border-radius: 10px; box-shadow: 0 1px 5px rgba(0,0,0,0.05); }
    .legend-item { display: flex; align-items: center; gap: 8px; font-size: 12px; }
    .legend-color { width: 20px; height: 20px; border-radius: 4px; }
    .modal-intervention { border-radius: 15px; overflow: hidden; }
    .modal-header-critical { background: #dc3545; color: white; }
    .modal-header-high { background: #fd7e14; color: white; }
    .modal-header-medium { background: #ffc107; color: #333; }
    .modal-header-low { background: #28a745; color: white; }
    .filter-bar { background: white; border-radius: 12px; padding: 12px 20px; margin-bottom: 20px; box-shadow: 0 1px 5px rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; }
    .view-buttons .btn { border-radius: 20px; padding: 5px 15px; font-size: 13px; }
    .view-btn.active { background-color: #667eea !important; color: white !important; border-color: #667eea !important; }
    .view-btn:not(.active) { background-color: transparent; color: #667eea; border: 1px solid #667eea; }
    .btn-export { background: #28a745; color: white; border: none; border-radius: 20px; padding: 5px 15px; font-size: 13px; }
    .btn-export:hover { background: #1e7e34; color: white; }
    .fc-daygrid-day { cursor: pointer; }
    .fc-daygrid-day:hover { background: #f8f9fa; }
    .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; border-radius: 8px; padding: 8px 20px; }
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="d-inline-block mb-0">
                <i class="fas fa-calendar-alt text-primary"></i> <?php echo t('calendar_view'); ?>
            </h2>
        </div>
        <div>
            <a href="?page=intervention_add" class="btn btn-primary text-white">
                <i class="fas fa-plus"></i> <?php echo t('new_intervention'); ?>
            </a>
            <a href="?page=planning" class="btn btn-secondary ms-2">
                <i class="fas fa-list"></i> <?php echo t('planning'); ?>
            </a>
        </div>
    </div>
    
    <div class="legend">
        <div class="legend-item"><div class="legend-color" style="background: linear-gradient(135deg, #dc3545, #c82333);"></div><span><?php echo t('critical'); ?></span></div>
        <div class="legend-item"><div class="legend-color" style="background: linear-gradient(135deg, #fd7e14, #e06a0a);"></div><span><?php echo t('high'); ?></span></div>
        <div class="legend-item"><div class="legend-color" style="background: linear-gradient(135deg, #ffc107, #e0a800);"></div><span><?php echo t('medium'); ?></span></div>
        <div class="legend-item"><div class="legend-color" style="background: linear-gradient(135deg, #28a745, #1e7e34);"></div><span><?php echo t('low'); ?></span></div>
        <div class="legend-item ms-auto"><i class="fas fa-info-circle text-muted"></i><span class="text-muted"><?php echo t('click_event_details'); ?></span></div>
    </div>
    
    <div class="filter-bar">
        <div class="view-buttons">
            <span class="me-2 text-muted"><?php echo t('view'); ?> :</span>
            <button class="btn btn-sm view-btn active" onclick="changeView('dayGridMonth', this)"><?php echo t('month_view'); ?></button>
            <button class="btn btn-sm view-btn" onclick="changeView('timeGridWeek', this)"><?php echo t('week_view'); ?></button>
            <button class="btn btn-sm view-btn" onclick="changeView('timeGridDay', this)"><?php echo t('day_view'); ?></button>
        </div>
        <div>
            <select id="technicianFilter" class="form-select form-select-sm" style="width: 200px; display: inline-block; margin-right: 10px;">
                <option value=""><?php echo t('all_technicians'); ?></option>
                <?php foreach($technicians as $tech): ?>
                <option value="<?php echo $tech['id']; ?>"><?php echo htmlspecialchars($tech['firstname'] . ' ' . $tech['lastname']); ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn-export" onclick="exportToICal()">
                <i class="fas fa-download"></i> <?php echo t('export_ical'); ?>
            </button>
        </div>
    </div>
    
    <div class="calendar-container mb-4">
        <div id="calendar"></div>
    </div>
</div>

<!-- Modal Détails -->
<div class="modal fade" id="interventionModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-intervention">
            <div class="modal-header" id="modalHeader">
                <h5 class="modal-title" id="modalTitle"></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalBody"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo t('close'); ?></button>
                <a href="#" id="modalEditLink" class="btn btn-primary text-white"><?php echo t('view'); ?></a>
            </div>
        </div>
    </div>
</div>

<script>
let calendar = null;
let currentEvents = <?php 
$events = [];
foreach($interventions as $inv) {
    $date = $inv['intervention_date'];
    $title = $inv['task_number'] . ' - ' . $inv['title'];
    if($inv['firstname']) $title .= ' (' . $inv['firstname'] . ' ' . $inv['lastname'] . ')';
    
    $events[] = [
        'id' => $inv['id'],
        'title' => $title,
        'start' => $date,
        'color' => $color_map[$inv['priority']] ?? '#3788d8',
        'className' => $event_class_map[$inv['priority']] ?? '',
        'extendedProps' => [
            'task_number' => $inv['task_number'],
            'equipment_name' => $inv['equipment_name'],
            'equipment_code' => $inv['equipment_code'],
            'priority' => $inv['priority'],
            'status' => $inv['task_status'],
            'technician_id' => $inv['intervenant_id'],
            'technician_name' => $inv['firstname'] ? $inv['firstname'] . ' ' . $inv['lastname'] : '<?php echo $unassigned_text; ?>',
            'technician_specialty' => $inv['specialty'],
            'description' => $inv['description'],
            'zone' => $inv['zone'],
            'localisation' => $inv['localisation'],
            'planned_duration' => $inv['planned_duration'],
            'completion_report' => $inv['completion_report']
        ]
    ];
}
echo json_encode($events);
?>;

document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    calendar = new FullCalendar.Calendar(calendarEl, {
        locale: '<?php echo getCurrentLanguage(); ?>',
        initialView: 'dayGridMonth',
        headerToolbar: { left: 'prev,next today', center: 'title', right: '' },
        events: currentEvents,
        eventClick: function(info) { showEventDetails(info.event); },
        dateClick: function(info) { window.location.href = '?page=planning&date=' + info.dateStr; },
        height: 'auto',
        firstDay: 1,
        timeZone: 'local',
        eventTimeFormat: { hour: '2-digit', minute: '2-digit', meridiem: false, hour12: false }
    });
    calendar.render();
});

function changeView(viewName, button) {
    document.querySelectorAll('.view-btn').forEach(btn => btn.classList.remove('active'));
    button.classList.add('active');
    calendar.changeView(viewName);
}

function showEventDetails(event) {
    moment.locale('<?php echo getCurrentLanguage(); ?>');
    const props = event.extendedProps;
    const priority = props.priority;
    let headerClass = '', priorityBadge = '';
    switch(priority) {
        case 'critical': headerClass = 'modal-header-critical'; priorityBadge = '<span class="badge bg-danger"><?php echo t('critical'); ?></span>'; break;
        case 'high': headerClass = 'modal-header-high'; priorityBadge = '<span class="badge bg-warning"><?php echo t('high'); ?></span>'; break;
        case 'medium': headerClass = 'modal-header-medium'; priorityBadge = '<span class="badge bg-info text-dark"><?php echo t('medium'); ?></span>'; break;
        default: headerClass = 'modal-header-low'; priorityBadge = '<span class="badge bg-success"><?php echo t('low'); ?></span>';
    }
    
    let statusHtml = '';
    switch(props.status) {
        case 'a_faire': statusHtml = '<span class="badge bg-secondary"><?php echo t('to_do'); ?></span>'; break;
        case 'en_cours': statusHtml = '<span class="badge bg-primary"><?php echo t('in_progress'); ?></span>'; break;
        case 'termine': statusHtml = '<span class="badge bg-success"><?php echo t('completed'); ?></span>'; break;
        default: statusHtml = '<span class="badge bg-secondary">' + props.status + '</span>';
    }
    
    document.getElementById('modalHeader').className = 'modal-header ' + headerClass;
    document.getElementById('modalTitle').innerHTML = props.task_number + ' - ' + (event.title.split(' - ')[1] || event.title);
    
    document.getElementById('modalBody').innerHTML = `
        <div class="mb-3 d-flex justify-content-between align-items-center">${priorityBadge}${statusHtml}</div>
        <div class="row mb-2"><div class="col-4"><strong><?php echo t('date'); ?> :</strong></div><div class="col-8">${moment(event.start).format('dddd D MMMM YYYY')}</div></div>
        <div class="row mb-2"><div class="col-4"><strong><?php echo t('equipment'); ?> :</strong></div><div class="col-8">${props.equipment_name}</div></div>
        <div class="row mb-2"><div class="col-4"><strong><?php echo t('technician'); ?> :</strong></div><div class="col-8">${props.technician_name}</div></div>
        ${props.description ? `<div class="row mb-2"><div class="col-4"><strong><?php echo t('description'); ?> :</strong></div><div class="col-8"><small>${props.description}</small></div></div>` : ''}
    `;
    document.getElementById('modalEditLink').href = '?page=intervention_view&id=' + event.id;
    new bootstrap.Modal(document.getElementById('interventionModal')).show();
}

function exportToICal() { window.location.href = 'export/ical_export.php'; }

document.getElementById('technicianFilter').addEventListener('change', function() {
    const technicianId = this.value;
    if(calendar) {
        if(technicianId) {
            calendar.removeAllEvents();
            calendar.addEventSource(currentEvents.filter(e => e.extendedProps.technician_id == technicianId));
        } else {
            calendar.removeAllEvents();
            calendar.addEventSource(currentEvents);
        }
    }
});
</script>
