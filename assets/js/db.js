// assets/js/db.js - Base de données IndexedDB pour mode hors ligne
const DB_NAME = 'gmao_db';
const DB_VERSION = 1;
let db = null;

// Initialisation de la base de données
function initDB() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open(DB_NAME, DB_VERSION);
        
        request.onerror = () => reject(request.error);
        request.onsuccess = () => {
            db = request.result;
            resolve(db);
        };
        
        request.onupgradeneeded = (event) => {
            const db = event.target.result;
            
            // Stockage des équipements
            if (!db.objectStoreNames.contains('equipment')) {
                const equipmentStore = db.createObjectStore('equipment', { keyPath: 'id' });
                equipmentStore.createIndex('code', 'code', { unique: false });
                equipmentStore.createIndex('name', 'name', { unique: false });
            }
            
            // Stockage des interventions
            if (!db.objectStoreNames.contains('interventions')) {
                const interventionsStore = db.createObjectStore('interventions', { keyPath: 'id' });
                interventionsStore.createIndex('status', 'status', { unique: false });
                interventionsStore.createIndex('technician_id', 'technician_id', { unique: false });
            }
            
            // Stockage des interventions hors ligne
            if (!db.objectStoreNames.contains('offline_interventions')) {
                db.createObjectStore('offline_interventions', { keyPath: 'id', autoIncrement: true });
            }
            
            // Stockage des techniciens
            if (!db.objectStoreNames.contains('technicians')) {
                db.createObjectStore('technicians', { keyPath: 'id' });
            }
            
            // Métadonnées de synchronisation
            if (!db.objectStoreNames.contains('sync_metadata')) {
                db.createObjectStore('sync_metadata', { keyPath: 'key' });
            }
        };
    });
}

// Sauvegarder des données en local
function saveOffline(storeName, data) {
    return new Promise((resolve, reject) => {
        const transaction = db.transaction([storeName], 'readwrite');
        const store = transaction.objectStore(storeName);
        const request = store.put(data);
        request.onsuccess = () => resolve(true);
        request.onerror = () => reject(request.error);
    });
}

// Récupérer des données locales
function getOffline(storeName, key = null) {
    return new Promise((resolve, reject) => {
        const transaction = db.transaction([storeName], 'readonly');
        const store = transaction.objectStore(storeName);
        let request;
        
        if (key) {
            request = store.get(key);
        } else {
            request = store.getAll();
        }
        
        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });
}

// Supprimer des données locales
function deleteOffline(storeName, key) {
    return new Promise((resolve, reject) => {
        const transaction = db.transaction([storeName], 'readwrite');
        const store = transaction.objectStore(storeName);
        const request = store.delete(key);
        request.onsuccess = () => resolve(true);
        request.onerror = () => reject(request.error);
    });
}

// Synchronisation avec le serveur
async function syncWithServer() {
    if (!navigator.onLine) return false;
    
    // Récupérer les interventions hors ligne
    const offlineInterventions = await getOffline('offline_interventions');
    
    for (const intervention of offlineInterventions) {
        try {
            const response = await fetch('/gmao/api/sync_intervention.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(intervention)
            });
            
            if (response.ok) {
                await deleteOffline('offline_interventions', intervention.id);
            }
        } catch (error) {
            console.error('Erreur sync:', error);
        }
    }
    
    // Mettre à jour les métadonnées
    await saveOffline('sync_metadata', { key: 'last_sync', value: new Date().toISOString() });
    
    return true;
}

// Vérifier la connexion et synchroniser
window.addEventListener('online', () => {
    syncWithServer();
});

// Initialisation
initDB().then(() => {
    if (navigator.onLine) {
        syncWithServer();
    }
});