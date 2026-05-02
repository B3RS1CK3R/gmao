<?php
// pages/calendar.php - Planning visuel type calendrier (Google Agenda like)
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

// Styles pour les événements
$event_class_map = [
    'critical' => 'fc-event-critical',
    'high' => 'fc-event-high',
    'medium' => 'fc-event-medium',
    'low' => 'fc-event-low'
];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendrier - GMAO Industrielle</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- FullCalendar -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales/fr.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f6f9; }
        
        .calendar-container {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .fc-event {
            cursor: pointer;
            transition: transform 0.2s, filter 0.2s;
            border: none !important;
            border-radius: 6px !important;
            padding: 2px 4px !important;
            font-size: 12px !important;
        }
        
        .fc-event:hover {
            transform: scale(1.02);
            filter: brightness(0.95);
        }
        
        .fc-event-critical {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            border-left: 3px solid white !important;
        }
        
        .fc-event-high {
            background: linear-gradient(135deg, #fd7e14 0%, #e06a0a 100%);
            border-left: 3px solid white !important;
        }
        
        .fc-event-medium {
            background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
            color: #333 !important;
        }
        
        .fc-event-low {
            background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%);
        }
        
        .fc-toolbar-title {
            font-size: 1.2rem !important;
            font-weight: bold !important;
        }
        
        .fc-button-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
            border: none !important;
        }
        
        .fc-button-primary:hover {
            filter: brightness(0.95) !important;
        }
        
        .legend {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            flex-wrap: wrap;
            background: white;
            padding: 10px 20px;
            border-radius: 10px;
            box-shadow: 0 1px 5px rgba(0,0,0,0.05);
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
        }
        
        .legend-color {
            width: 20px;
            height: 20px;
            border-radius: 4px;
        }
        
        .modal-intervention {
            border-radius: 15px;
            overflow: hidden;
        }
        
        .modal-header-critical { background: #dc3545; color: white; }
        .modal-header-high { background: #fd7e14; color: white; }
        .modal-header-medium { background: #ffc107; color: #333; }
        .modal-header-low { background: #28a745; color: white; }
        
        .filter-bar {
            background: white;
            border-radius: 12px;
            padding: 12px 20px;
            margin-bottom: 20px;
            box-shadow: 0 1px 5px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .view-buttons .btn {
            border-radius: 20px;
            padding: 5px 15px;
            font-size: 13px;
        }
        
        .btn-export {
            background: #28a745;
            color: white;
            border: none;
            border-radius: 20px;
            padding: 5px 15px;
            font-size: 13px;
        }
        
        .btn-export:hover {
            background: #1e7e34;
            color: white;
        }
        
        .fc-daygrid-day {
            cursor: pointer;
        }
        
        .fc-daygrid-day:hover {
            background: #f8f9fa;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 8px;
            padding: 8px 20px;
        }
        .btn-primary:hover {
            filter: brightness(0.95);
        }
        .btn-secondary {
            background: #6c757d;
            border: none;
            border-radius: 8px;
            padding: 8px 20px;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
    </style>
</head>
<body>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="?page=dashboard" class="btn btn-secondary me-2">
                <i class="fas fa-arrow-left"></i> <?php echo t('back'); ?>
            </a>
            <h2 class="d-inline-block mb-0">
                <i class="fas fa-calendar-alt"></i> <?php echo t('calendar_view'); ?>
            </h2>
        </div>
        <div>
            <a href="?page=intervention_add" class="btn btn-primary">
                <i class="fas fa-plus"></i> <?php echo t('new_intervention'); ?>
            </a>
            <a href="?page=planning" class="btn btn-secondary ms-2">
                <i class="fas fa-list"></i> <?php echo t('planning'); ?>
            </a>
        </div>
    </div>
    
    <!-- Légende -->
    <div class="legend">
        <div class="legend-item">
            <div class="legend-color" style="background: linear-gradient(135deg, #dc3545, #c82333);"></div>
            <span><?php echo t('critical'); ?></span>
        </div>
        <div class="legend-item">
            <div class="legend-color" style="background: linear-gradient(135deg, #fd7e14, #e06a0a);"></div>
            <span><?php echo t('high'); ?></span>
        </div>
        <div class="legend-item">
            <div class="legend-color" style="background: linear-gradient(135deg, #ffc107, #e0a800);"></div>
            <span><?php echo t('medium'); ?></span>
        </div>
        <div class="legend-item">
            <div class="legend-color" style="background: linear-gradient(135deg, #28a745, #1e7e34);"></div>
            <span><?php echo t('low'); ?></span>
        </div>
        <div class="legend-item ms-auto">
            <i class="fas fa-info-circle text-muted"></i>
            <span class="text-muted"><?php echo t('click_event_details'); ?></span>
        </div>
    </div>
    
    <!-- Barre de filtres -->
    <div class="filter-bar">
        <div class="view-buttons">
            <span class="me-2 text-muted"><?php echo t('view'); ?> :</span>
            <button class="btn btn-sm btn-outline-primary" onclick="changeView('dayGridMonth')"><?php echo t('month_view'); ?></button>
            <button class="btn btn-sm btn-outline-primary" onclick="changeView('timeGridWeek')"><?php echo t('week_view'); ?></button>
            <button class="btn btn-sm btn-outline-primary" onclick="changeView('timeGridDay')"><?php echo t('day_view'); ?></button>
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
    
    <!-- Calendrier -->
    <div class="calendar-container">
        <div id="calendar"></div>
    </div>
</div>

<!-- Modal pour afficher les détails de l'intervention -->
<div class="modal fade" id="interventionModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-intervention">
            <div class="modal-header" id="modalHeader">
                <h5 class="modal-title" id="modalTitle"></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalBody">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo t('close'); ?></button>
                <a href="#" id="modalEditLink" class="btn btn-primary"><?php echo t('view'); ?></a>
            </div>
        </div>
    </div>
</div>

<script>
// Données des événements
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
            'technician_name' => $inv['firstname'] ? $inv['firstname'] . ' ' . $inv['lastname'] : '<?php echo t('unassigned'); ?>',
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

// Initialisation du calendrier
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    calendar = new FullCalendar.Calendar(calendarEl, {
        locale: 'fr',
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: ''
        },
        events: currentEvents,
        eventClick: function(info) {
            showEventDetails(info.event);
        },
        dateClick: function(info) {
            window.location.href = '?page=planning&date=' + info.dateStr;
        },
        eventDrop: function(info) {
            updateEventDate(info.event.id, info.event.startStr);
        },
        eventResize: function(info) {
            updateEventDate(info.event.id, info.event.startStr);
        },
        height: 'auto',
        slotMinTime: '08:00:00',
        slotMaxTime: '20:00:00',
        firstDay: 1,
        timeZone: 'Europe/Paris',
        businessHours: {
            daysOfWeek: [1, 2, 3, 4, 5],
            startTime: '08:00',
            endTime: '18:00'
        },
        weekends: true,
        eventTimeFormat: {
            hour: '2-digit',
            minute: '2-digit',
            meridiem: false,
            hour12: false
        }
    });
    calendar.render();
});

// Changer la vue
function changeView(view) {
    if(calendar) {
        calendar.changeView(view);
    }
}

// Afficher les détails de l'événement
function showEventDetails(event) {
    const props = event.extendedProps;
    const priority = props.priority;
    
    let headerClass = '';
    let priorityBadge = '';
    
    switch(priority) {
        case 'critical':
            headerClass = 'modal-header-critical';
            priorityBadge = '<span class="badge bg-danger"><?php echo t('critical'); ?></span>';
            break;
        case 'high':
            headerClass = 'modal-header-high';
            priorityBadge = '<span class="badge bg-warning"><?php echo t('high'); ?></span>';
            break;
        case 'medium':
            headerClass = 'modal-header-medium';
            priorityBadge = '<span class="badge bg-info"><?php echo t('medium'); ?></span>';
            break;
        default:
            headerClass = 'modal-header-low';
            priorityBadge = '<span class="badge bg-secondary"><?php echo t('low'); ?></span>';
    }
    
    let statusHtml = '';
    switch(props.status) {
        case 'a_faire': statusHtml = '<span class="badge bg-secondary"><?php echo t('to_do'); ?></span>'; break;
        case 'en_cours': statusHtml = '<span class="badge bg-primary"><?php echo t('in_progress'); ?></span>'; break;
        case 'termine': statusHtml = '<span class="badge bg-success"><?php echo t('completed'); ?></span>'; break;
        case 'cloturee': statusHtml = '<span class="badge bg-dark"><?php echo t('closed'); ?></span>'; break;
        default: statusHtml = '<span class="badge bg-secondary">' + props.status + '</span>';
    }
    
    document.getElementById('modalHeader').className = 'modal-header ' + headerClass;
    document.getElementById('modalTitle').innerHTML = props.task_number + ' - ' + event.title.split(' - ')[1] || event.title;
    
    let locationHtml = '';
    if(props.zone || props.localisation) {
        locationHtml = `
            <div class="row mb-2">
                <div class="col-4"><strong><?php echo t('location'); ?> :</strong></div>
                <div class="col-8">${props.zone ? props.zone + (props.localisation ? ' / ' : '') : ''}${props.localisation || '-'}</div>
            </div>
        `;
    }
    
    document.getElementById('modalBody').innerHTML = `
        <div class="mb-3 d-flex justify-content-between align-items-center">
            ${priorityBadge}
            ${statusHtml}
        </div>
        <div class="row mb-2">
            <div class="col-4"><strong><?php echo t('date'); ?> :</strong></div>
            <div class="col-8">${moment(event.start).format('dddd D MMMM YYYY')}</div>
        </div>
        <div class="row mb-2">
            <div class="col-4"><strong><?php echo t('equipment'); ?> :</strong></div>
            <div class="col-8">${props.equipment_name}<br><small class="text-muted"><?php echo t('code'); ?>: ${props.equipment_code}</small></div>
        </div>
        ${locationHtml}
        <div class="row mb-2">
            <div class="col-4"><strong><?php echo t('technician'); ?> :</strong></div>
            <div class="col-8">${props.technician_name}<br>${props.technician_specialty ? '<small class="text-muted">' + props.technician_specialty + '</small>' : ''}</div>
        </div>
        ${props.planned_duration ? `
        <div class="row mb-2">
            <div class="col-4"><strong><?php echo t('planned_duration'); ?> :</strong></div>
            <div class="col-8">${props.planned_duration}</div>
        </div>
        ` : ''}
        ${props.description ? `
        <div class="row mb-2">
            <div class="col-4"><strong><?php echo t('description'); ?> :</strong></div>
            <div class="col-8"><small>${props.description}</small></div>
        </div>
        ` : ''}
        ${props.completion_report ? `
        <div class="row mb-2">
            <div class="col-4"><strong><?php echo t('report'); ?> :</strong></div>
            <div class="col-8"><small class="text-success"><?php echo t('report_available'); ?></small></div>
        </div>
        ` : ''}
    `;
    
    document.getElementById('modalEditLink').href = '?page=intervention_view&id=' + event.id;
    
    new bootstrap.Modal(document.getElementById('interventionModal')).show();
}

// Mettre à jour la date d'une intervention après drag & drop
function updateEventDate(eventId, newDate) {
    fetch('api/update_intervention_date.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            id: eventId,
            date: newDate
        })
    })
    .then(response => response.json())
    .then(data => {
        if(!data.success) {
            alert('<?php echo t('error_update_date'); ?>');
            location.reload();
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        location.reload();
    });
}

// Exporter le calendrier au format iCal
function exportToICal() {
    window.location.href = 'export/ical_export.php';
}

// Filtre par technicien
document.getElementById('technicianFilter').addEventListener('change', function() {
    const technicianId = this.value;
    if(calendar) {
        if(technicianId) {
            const filteredEvents = currentEvents.filter(event => 
                event.extendedProps.technician_id == technicianId
            );
            calendar.removeAllEvents();
            calendar.addEventSource(filteredEvents);
        } else {
            calendar.removeAllEvents();
            calendar.addEventSource(currentEvents);
        }
    }
});

// Rafraîchissement automatique toutes les 5 minutes
setInterval(function() {
    location.reload();
}, 300000);
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>