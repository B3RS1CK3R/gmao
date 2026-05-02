// assets/js/alerts.js - Gestion des alertes temps réel
class AlertSystem {
    constructor() {
        this.toastContainer = null;
        this.audioContext = null;
        this.lastAlerts = [];
        this.checkInterval = null;
        this.soundEnabled = true;
        this.notificationPermission = false;
        
        this.init();
    }
    
    init() {
        // Créer le conteneur de toasts
        this.createContainer();
        
        // Charger les préférences
        this.loadPreferences();
        
        // Demander la permission pour les notifications
        this.requestNotificationPermission();
        
        // Démarrer la vérification périodique
        this.startPeriodicCheck();
        
        // Écouter les événements de connexion
        this.setupEventListeners();
    }
    
    createContainer() {
        if (!document.querySelector('.toast-container')) {
            const container = document.createElement('div');
            container.className = 'toast-container';
            document.body.appendChild(container);
            this.toastContainer = container;
        } else {
            this.toastContainer = document.querySelector('.toast-container');
        }
    }
    
    loadPreferences() {
        const savedSound = localStorage.getItem('gmao_sound_enabled');
        if (savedSound !== null) {
            this.soundEnabled = savedSound === 'true';
        }
    }
    
    requestNotificationPermission() {
        if ('Notification' in window) {
            Notification.requestPermission().then(permission => {
                this.notificationPermission = permission === 'granted';
            });
        }
    }
    
    setupEventListeners() {
        // Recharger les alertes quand la page devient visible
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                this.checkAlerts();
            }
        });
    }
    
    startPeriodicCheck() {
        this.checkAlerts(); // Vérification immédiate
        this.checkInterval = setInterval(() => this.checkAlerts(), 30000); // Toutes les 30 secondes
    }
    
    async checkAlerts() {
        try {
            const response = await fetch('/gmao/api/get_alerts.php');
            const data = await response.json();
            
            if (data.success && data.alerts) {
                this.processAlerts(data.alerts);
                this.updateNotificationBadges(data.counts);
            }
        } catch (error) {
            console.error('Erreur vérification alertes:', error);
        }
    }
    
    processAlerts(alerts) {
        const newAlerts = alerts.filter(alert => {
            return !this.lastAlerts.some(last => 
                last.id === alert.id && last.type === alert.type
            );
        });
        
        newAlerts.forEach(alert => {
            this.showToast(alert);
            
            // Notification système (si permis)
            if (this.notificationPermission && alert.priority === 'critical') {
                this.showSystemNotification(alert);
            }
            
            // Son d'alerte
            if (this.soundEnabled && alert.priority === 'critical') {
                this.playAlertSound();
            }
        });
        
        this.lastAlerts = alerts;
    }
    
    showToast(alert) {
        let icon = '🔔';
        let borderColor = 'info';
        
        switch(alert.type) {
            case 'maintenance_overdue':
                icon = '⚠️';
                borderColor = 'warning';
                break;
            case 'stock_critical':
                icon = '📦';
                borderColor = 'warning';
                break;
            case 'critical_intervention':
                icon = '🚨';
                borderColor = 'critical';
                break;
            case 'warranty_expiring':
                icon = '📅';
                borderColor = 'info';
                break;
            default:
                icon = '🔔';
        }
        
        const toast = document.createElement('div');
        toast.className = `toast-notification ${borderColor}`;
        toast.innerHTML = `
            <div class="toast-icon">${icon}</div>
            <div class="toast-content">
                <div class="toast-title">${alert.title}</div>
                <div class="toast-message">${alert.message}</div>
                <div class="toast-time">${new Date().toLocaleTimeString()}</div>
            </div>
            <div class="toast-close" onclick="this.parentElement.remove()">✕</div>
        `;
        
        toast.addEventListener('click', () => {
            if (alert.url) {
                window.location.href = alert.url;
            }
            toast.remove();
        });
        
        this.toastContainer.appendChild(toast);
        
        // Auto-suppression après 8 secondes
        setTimeout(() => {
            if (toast.parentElement) {
                toast.style.animation = 'slideOutRight 0.3s ease-out';
                setTimeout(() => toast.remove(), 300);
            }
        }, 8000);
    }
    
    showSystemNotification(alert) {
        if (document.hidden) {
            new Notification(alert.title, {
                body: alert.message,
                icon: '/gmao/assets/icons/icon-192.png',
                tag: alert.id,
                requireInteraction: true
            });
        }
    }
    
    playAlertSound() {
        if (!this.audioContext) {
            this.audioContext = new (window.AudioContext || window.webkitAudioContext)();
        }
        
        const oscillator = this.audioContext.createOscillator();
        const gainNode = this.audioContext.createGain();
        
        oscillator.connect(gainNode);
        gainNode.connect(this.audioContext.destination);
        
        oscillator.frequency.value = 880;
        gainNode.gain.value = 0.3;
        
        oscillator.start();
        gainNode.gain.exponentialRampToValueAtTime(0.00001, this.audioContext.currentTime + 1);
        oscillator.stop(this.audioContext.currentTime + 1);
    }
    
    updateNotificationBadges(counts) {
        const badges = document.querySelectorAll('.notification-badge .badge-count');
        const total = (counts.critical || 0) + (counts.warning || 0);
        
        badges.forEach(badge => {
            if (badge.closest('.nav-link').getAttribute('href') === '?page=alerts') {
                badge.textContent = total;
                badge.style.display = total > 0 ? 'inline-block' : 'none';
            }
        });
        
        // Mettre à jour le titre de la page
        if (total > 0) {
            document.title = `(${total}) GMAO Pro`;
        } else {
            document.title = 'GMAO Pro';
        }
    }
    
    toggleSound() {
        this.soundEnabled = !this.soundEnabled;
        localStorage.setItem('gmao_sound_enabled', this.soundEnabled);
        return this.soundEnabled;
    }
    
    stop() {
        if (this.checkInterval) {
            clearInterval(this.checkInterval);
        }
    }
}

// Initialisation au chargement de la page
let alertSystem = null;

document.addEventListener('DOMContentLoaded', () => {
    alertSystem = new AlertSystem();
});