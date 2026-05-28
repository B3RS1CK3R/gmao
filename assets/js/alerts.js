// @ts-nocheck
// assets/js/alerts.js - Real-time alert management

class AlertSystem {
    constructor() {
        this.toastContainer = null;
        this.audioContext = null;
        this.lastAlerts = [];
        this.checkInterval = null;
        this.soundEnabled = localStorage.getItem('gmao_sound_enabled') !== 'false';
        this.popupsEnabled = localStorage.getItem('gmao_popups_enabled') !== 'false';
        this.notificationPermission = false;
        
        this.init();
    }
    
    init() {
        this.createContainer();
        this.loadPreferences();
        this.requestNotificationPermission();
        this.startPeriodicCheck();
        this.setupEventListeners();
        
        window.addEventListener('storage', (e) => {
            if (e.key === 'gmao_popups_enabled') {
                this.popupsEnabled = e.newValue !== 'false';
            }
            if (e.key === 'gmao_sound_enabled') {
                this.soundEnabled = e.newValue !== 'false';
            }
        });
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
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                this.checkAlerts();
            }
        });
    }
    
    startPeriodicCheck() {
        this.checkAlerts();
        this.checkInterval = setInterval(() => this.checkAlerts(), 30000);
    }
    
    async checkAlerts() {
        try {
            const response = await fetch('/gmao_GEMINI/api/get_alerts.php');
            const data = await response.json();
            
            if (data.success && data.alerts) {
                this.processAlerts(data.alerts);
                this.updateNotificationBadges(data.counts);
            }
        } catch (error) {
            console.error('Error checking alerts:', error);
        }
    }
    
    processAlerts(alerts) {
        const newAlerts = alerts.filter(alert => {
            return !this.lastAlerts.some(last => 
                last.id === alert.id && last.type === alert.type
            );
        });
        
        newAlerts.forEach(alert => {
            if (this.popupsEnabled) {
                this.showToast(alert);
            }
            
            if (this.notificationPermission && alert.priority === 'critical') {
                this.showSystemNotification(alert);
            }
            
            if (this.soundEnabled && alert.priority === 'critical') {
                this.playAlertSound();
            }
        });
        
        this.lastAlerts = alerts;
    }
    
    showToast(alert) {
        let icon = '🔔';
        let priorityClass = 'info';
        let title = 'Notification';
        let message = alert.message || 'You have a new alert';
        
        switch(alert.type) {
            case 'maintenance_overdue':
                icon = '⚠️';
                priorityClass = 'warning';
                title = 'Maintenance Overdue';
                message = alert.message || 'A preventive maintenance task is overdue';
                break;
            case 'stock_critical':
                icon = '📦';
                priorityClass = 'warning';
                title = 'Critical Stock Alert';
                message = alert.message || 'A spare part has reached critical stock level';
                break;
            case 'critical_intervention':
                icon = '🚨';
                priorityClass = 'critical';
                title = 'Critical Intervention';
                message = alert.message || 'A critical intervention requires immediate attention';
                break;
            case 'warranty_expired':
                icon = '⚠️';
                priorityClass = 'critical';
                title = 'Warranty Expired';
                message = alert.message || 'Equipment warranty has expired';
                break;
            case 'warranty_upcoming':
                icon = '📅';
                priorityClass = 'info';
                title = 'Warranty Expiring Soon';
                message = alert.message || 'Equipment warranty is about to expire';
                break;
            case 'unassigned_intervention':
                icon = '📋';
                priorityClass = 'warning';
                title = 'Unassigned Intervention';
                message = alert.message || 'An intervention is waiting for assignment';
                break;
            default:
                icon = '🔔';
                priorityClass = alert.priority === 'critical' ? 'critical' : (alert.priority === 'warning' ? 'warning' : 'info');
                title = alert.title || 'Notification';
                message = alert.message || 'You have a new notification';
        }
        
        if (alert.priority === 'critical') {
            priorityClass = 'critical';
        } else if (alert.priority === 'warning') {
            priorityClass = 'warning';
        }
        
        if (alert.title && alert.title !== title) {
            title = alert.title;
        }
        
        const toast = document.createElement('div');
        toast.className = `toast-notification ${priorityClass}`;
        toast.innerHTML = `
            <div class="toast-icon">${this.escapeHtml(icon)}</div>
            <div class="toast-content">
                <div class="toast-title">${this.escapeHtml(title)}</div>
                <div class="toast-message">${this.escapeHtml(message)}</div>
                <div class="toast-time">${new Date().toLocaleTimeString()}</div>
            </div>
            <div class="toast-close">✕</div>
        `;
        
        const closeBtn = toast.querySelector('.toast-close');
        closeBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            toast.style.animation = 'slideOutRight 0.3s ease-out';
            setTimeout(() => toast.remove(), 300);
        });
        
        toast.addEventListener('click', (e) => {
            if (e.target === closeBtn || closeBtn.contains(e.target)) {
                return;
            }
            if (alert.url) {
                window.location.href = alert.url;
            }
            toast.style.animation = 'slideOutRight 0.3s ease-out';
            setTimeout(() => toast.remove(), 300);
        });
        
        this.toastContainer.appendChild(toast);
        
        setTimeout(() => {
            if (toast.parentElement) {
                toast.style.animation = 'slideOutRight 0.3s ease-out';
                setTimeout(() => toast.remove(), 300);
            }
        }, 8000);
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    showSystemNotification(alert) {
        if (document.hidden) {
            let body = alert.message || 'You have a critical alert';
            new Notification(alert.title || 'Critical Alert', {
                body: body,
                icon: '/gmao_GEMINI/assets/icons/icon-192.png',
                tag: alert.id,
                requireInteraction: true
            });
        }
    }
    
    playAlertSound() {
        if (!this.audioContext) {
            this.audioContext = new (window.AudioContext || window.webkitAudioContext)();
        }
        
        if (this.audioContext.state === 'suspended') {
            this.audioContext.resume();
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
            const link = badge.closest('.nav-link');
            if (link && (link.getAttribute('href') === '?page=alerts' || link.getAttribute('href') === 'index.php?page=alerts')) {
                badge.textContent = total;
                badge.style.display = total > 0 ? 'inline-block' : 'none';
            }
        });
        
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
    
    togglePopups() {
        this.popupsEnabled = !this.popupsEnabled;
        localStorage.setItem('gmao_popups_enabled', this.popupsEnabled);
        return this.popupsEnabled;
    }
    
    stop() {
        if (this.checkInterval) {
            clearInterval(this.checkInterval);
        }
    }
}

let alertSystem = null;

document.addEventListener('DOMContentLoaded', () => {
    alertSystem = new AlertSystem();
});