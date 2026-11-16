/**
 * Msantha Poultry Management System - Main JS (ES Module)
 * Mobile PWA, Service Worker Registration, IndexedDB Offline Queue & Sync
 */

export class OfflineQueue {
    static dbName = 'MsanthaFarmOfflineDB';
    static storeName = 'sync_queue';
    static dbVersion = 1;

    static async openDB() {
        return new Promise((resolve, reject) => {
            if (!('indexedDB' in window)) {
                reject(new Error('IndexedDB not supported in this browser'));
                return;
            }

            const request = indexedDB.open(this.dbName, this.dbVersion);

            request.onupgradeneeded = (event) => {
                const db = event.target.result;
                if (!db.objectStoreNames.contains(this.storeName)) {
                    db.createObjectStore(this.storeName, { keyPath: 'client_uuid' });
                }
            };

            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    static async enqueue(type, payload) {
        const db = await this.openDB();
        const client_uuid = (window.crypto && crypto.randomUUID) ? crypto.randomUUID() : this.generateUUID();
        const record = {
            client_uuid,
            type,
            payload,
            queued_at: new Date().toISOString()
        };

        return new Promise((resolve, reject) => {
            const tx = db.transaction(this.storeName, 'readwrite');
            const store = tx.objectStore(this.storeName);
            const req = store.put(record);

            req.onsuccess = () => {
                this.updateBadge();
                resolve(client_uuid);
            };
            req.onerror = () => reject(req.error);
        });
    }

    static async getItems() {
        const db = await this.openDB();
        return new Promise((resolve, reject) => {
            const tx = db.transaction(this.storeName, 'readonly');
            const store = tx.objectStore(this.storeName);
            const req = store.getAll();

            req.onsuccess = () => resolve(req.result || []);
            req.onerror = () => reject(req.error);
        });
    }

    static async remove(client_uuid) {
        const db = await this.openDB();
        return new Promise((resolve, reject) => {
            const tx = db.transaction(this.storeName, 'readwrite');
            const store = tx.objectStore(this.storeName);
            const req = store.delete(client_uuid);

            req.onsuccess = () => {
                this.updateBadge();
                resolve(true);
            };
            req.onerror = () => reject(req.error);
        });
    }

    static async count() {
        try {
            const db = await this.openDB();
            return new Promise((resolve) => {
                const tx = db.transaction(this.storeName, 'readonly');
                const store = tx.objectStore(this.storeName);
                const req = store.count();

                req.onsuccess = () => resolve(req.result || 0);
                req.onerror = () => resolve(0);
            });
        } catch (e) {
            return 0;
        }
    }

    static async updateBadge() {
        const cnt = await this.count();
        const badge = document.getElementById('syncBadge');
        const countSpan = document.getElementById('syncCount');

        if (badge && countSpan) {
            countSpan.textContent = String(cnt);
            if (cnt > 0) {
                badge.classList.remove('d-none');
            } else {
                badge.classList.add('d-none');
            }
        }
    }

    static generateUUID() {
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
            const r = Math.random() * 16 | 0;
            const v = c === 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    }
}

export class SyncRunner {
    static isSyncing = false;

    static async syncPending() {
        if (this.isSyncing) return;
        if (!navigator.onLine) return;

        const items = await OfflineQueue.getItems();
        if (!items || items.length === 0) {
            await OfflineQueue.updateBadge();
            return;
        }

        this.isSyncing = true;
        const badge = document.getElementById('syncBadge');
        if (badge) {
            badge.innerHTML = `<span class="spinner-border spinner-border-sm me-1"></span>Syncing...`;
        }

        try {
            // Determine base URL dynamically
            const syncEndpoint = document.querySelector('link[rel="manifest"]')?.href?.replace(/manifest\.webmanifest.*$/, 'api/sync') || '/api/sync';

            const response = await fetch(syncEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ items })
            });

            if (response.ok) {
                const data = await response.json();
                let syncedCount = 0;

                if (Array.isArray(data.results)) {
                    for (const res of data.results) {
                        if (res.status === 'success' || res.status === 'already_synced') {
                            await OfflineQueue.remove(res.client_uuid);
                            if (res.status === 'success') syncedCount++;
                        }
                    }
                }

                if (syncedCount > 0) {
                    App.showToast(`Synced ${syncedCount} offline record(s) successfully!`, 'success');
                }
            } else if (response.status === 401) {
                App.showToast('Please sign in to sync pending offline records.', 'warning');
            }
        } catch (err) {
            console.error('[Sync] Failed to sync offline items:', err);
        } finally {
            this.isSyncing = false;
            if (badge) {
                badge.innerHTML = `<i class="bi bi-arrow-repeat me-1"></i>Pending Sync (<span id="syncCount">0</span>)`;
            }
            await OfflineQueue.updateBadge();
        }
    }
}

export class App {
    static deferredPrompt = null;

    static init() {
        this.setupAlertDismissal();
        this.setupDeleteConfirmation();
        this.setupNetworkStatus();
        this.registerServiceWorker();
        this.setupInstallPrompt();
        this.setupOfflineForms();

        // Check offline queue count on start
        OfflineQueue.updateBadge();

        // Listen for sync badge click
        const badge = document.getElementById('syncBadge');
        if (badge) {
            badge.addEventListener('click', () => SyncRunner.syncPending());
        }
    }

    static showToast(message, type = 'success') {
        const container = document.getElementById('toastContainer');
        if (!container) return;

        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-bg-${type} border-0 show mb-2 shadow`;
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');

        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body fw-semibold">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        `;

        container.appendChild(toast);
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 4500);
    }

    static setupAlertDismissal() {
        document.querySelectorAll('.alert-dismissible .btn-close').forEach(btn => {
            btn.addEventListener('click', () => {
                const alert = btn.closest('.alert');
                if (alert) alert.remove();
            });
        });
    }

    static setupDeleteConfirmation() {
        document.querySelectorAll('[data-confirm]').forEach(el => {
            el.addEventListener('click', (e) => {
                const message = el.getAttribute('data-confirm') || 'Are you sure you want to proceed?';
                if (!window.confirm(message)) {
                    e.preventDefault();
                }
            });
        });
    }

    static setupNetworkStatus() {
        const updateStatus = () => {
            const badge = document.getElementById('offlineBadge');
            if (badge) {
                if (!navigator.onLine) {
                    badge.classList.remove('d-none');
                } else {
                    badge.classList.add('d-none');
                    // Automatically sync offline items when coming back online
                    SyncRunner.syncPending();
                }
            }
        };

        window.addEventListener('online', updateStatus);
        window.addEventListener('offline', updateStatus);
        updateStatus();
    }

    static registerServiceWorker() {
        if ('serviceWorker' in navigator && (window.location.protocol === 'https:' || window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1')) {
            const swUrl = document.querySelector('link[rel="manifest"]')?.href?.replace(/manifest\.webmanifest.*$/, 'sw.js') || './sw.js';
            navigator.serviceWorker.register(swUrl)
                .then((reg) => {
                    console.log('[PWA] ServiceWorker registered with scope:', reg.scope);
                })
                .catch((err) => {
                    console.warn('[PWA] ServiceWorker registration failed:', err);
                });
        }
    }

    static setupInstallPrompt() {
        const banner = document.getElementById('pwaInstallBanner');
        const installBtn = document.getElementById('pwaInstallBtn');
        const dismissBtn = document.getElementById('pwaDismissBtn');

        const dismissedAt = localStorage.getItem('msantha_pwa_dismissed');
        const now = Date.now();
        const sevenDays = 7 * 24 * 60 * 60 * 1000;

        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            this.deferredPrompt = e;

            if (banner && (!dismissedAt || (now - Number(dismissedAt)) > sevenDays)) {
                banner.classList.remove('d-none');
                banner.classList.add('d-flex');
            }
        });

        if (installBtn) {
            installBtn.addEventListener('click', async () => {
                if (this.deferredPrompt) {
                    this.deferredPrompt.prompt();
                    const choiceResult = await this.deferredPrompt.userChoice;
                    if (choiceResult.outcome === 'accepted') {
                        console.log('[PWA] User accepted install prompt');
                    }
                    this.deferredPrompt = null;
                }
                if (banner) {
                    banner.classList.add('d-none');
                    banner.classList.remove('d-flex');
                }
            });
        }

        if (dismissBtn) {
            dismissBtn.addEventListener('click', () => {
                localStorage.setItem('msantha_pwa_dismissed', String(Date.now()));
                if (banner) {
                    banner.classList.add('d-none');
                    banner.classList.remove('d-flex');
                }
            });
        }
    }

    static setupOfflineForms() {
        // Intercept Quick Daily Entry form when offline
        const quickEntryForm = document.querySelector('form[action*="/flocks/quick-entry"]');
        if (quickEntryForm) {
            quickEntryForm.addEventListener('submit', async (e) => {
                if (!navigator.onLine) {
                    e.preventDefault();
                    const formData = new FormData(quickEntryForm);
                    const flockId = formData.get('flock_id');
                    const eventDate = formData.get('event_date') || new Date().toISOString().split('T')[0];
                    const deaths = parseInt(formData.get('deaths') || '0', 10);
                    const remarks = formData.get('remarks') || 'Quick entry (offline)';

                    if (deaths > 0 && flockId) {
                        await OfflineQueue.enqueue('death', {
                            flock_id: parseInt(flockId, 10),
                            event_date: eventDate,
                            quantity: deaths,
                            remarks: remarks
                        });
                        App.showToast('Offline death entry queued! Will sync when connection is restored.', 'info');
                    }
                }
            });
        }

        // Intercept Egg Collection form when offline
        const eggForm = document.querySelector('form[action*="/eggs"]');
        if (eggForm) {
            eggForm.addEventListener('submit', async (e) => {
                if (!navigator.onLine) {
                    e.preventDefault();
                    const formData = new FormData(eggForm);
                    const breedId = formData.get('breed_id');
                    const flockId = formData.get('flock_id');
                    const prodDate = formData.get('prod_date') || new Date().toISOString().split('T')[0];
                    const collected = parseInt(formData.get('collected') || '0', 10);

                    if (breedId && collected >= 0) {
                        await OfflineQueue.enqueue('egg', {
                            breed_id: parseInt(breedId, 10),
                            flock_id: flockId ? parseInt(flockId, 10) : null,
                            prod_date: prodDate,
                            collected: collected,
                            broken: parseInt(formData.get('broken') || '0', 10),
                            consumed: parseInt(formData.get('consumed') || '0', 10),
                            sold: parseInt(formData.get('sold') || '0', 10),
                            incubated: parseInt(formData.get('incubated') || '0', 10),
                            kept: parseInt(formData.get('kept') || '0', 10),
                            remarks: formData.get('remarks') || 'Egg collection (offline)'
                        });
                        App.showToast('Offline egg collection queued! Will sync when connection is restored.', 'info');
                    }
                }
            });
        }
    }
}

// Auto-initialize when DOM is ready or immediately if already loaded
function bootstrapApp() {
    App.init();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootstrapApp);
} else {
    bootstrapApp();
}

// Global Folding Z-Fold Interactive Controller (attached directly to document immediately)
document.addEventListener('click', (e) => {
    // 1. Z-fold card header click or toggle button -> toggle fold
    const header = e.target.closest('.zfold-header');
    if (header) {
        // Don't toggle if user clicked an anchor, input, select, textarea, button (unless zfold-toggle-btn), or .no-fold
        if (e.target.closest('a') || e.target.closest('input') || e.target.closest('select') || e.target.closest('textarea') || e.target.closest('button:not(.zfold-toggle-btn)') || e.target.closest('.no-fold')) {
            return;
        }
        const card = header.closest('.zfold-card');
        if (card) {
            card.classList.toggle('is-unfolded');
        }
        return;
    }

    // Also support clicking directly on a zfold-toggle-btn if outside header
    const toggleBtn = e.target.closest('.zfold-toggle-btn');
    if (toggleBtn) {
        const card = toggleBtn.closest('.zfold-card');
        if (card) {
            card.classList.toggle('is-unfolded');
        }
        return;
    }

    // 2. Expand all / Collapse all button
    const toggleAllBtn = e.target.closest('[data-zfold-toggle-all]');
    if (toggleAllBtn) {
        const containerSelector = toggleAllBtn.dataset.zfoldToggleAll || '.zfold-container';
        const container = document.querySelector(containerSelector);
        if (container) {
            const cards = container.querySelectorAll('.zfold-card');
            const shouldExpand = toggleAllBtn.dataset.state !== 'expanded';
            cards.forEach(c => {
                if (shouldExpand) c.classList.add('is-unfolded');
                else c.classList.remove('is-unfolded');
            });
            toggleAllBtn.dataset.state = shouldExpand ? 'expanded' : 'collapsed';
            toggleAllBtn.innerHTML = shouldExpand 
                ? '<i class="bi bi-chevron-bar-contract me-1"></i>Fold All' 
                : '<i class="bi bi-chevron-bar-expand me-1"></i>Unfold All';
        }
    }
});

// Global Notification Action Handlers
window.markAllNotificationsSeen = async function() {
    try {
        const appUrl = window.APP_URL || '';
        const res = await fetch(appUrl + '/api/notifications/mark-seen', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: '_csrf_token=' + encodeURIComponent(window.CSRF_TOKEN || '') + '&key=all'
        });
        const data = await res.json();
        if (data.success) {
            const badge = document.getElementById('topbarUnreadBadge');
            if (badge) badge.remove();
            document.querySelectorAll('.notification-item.is-unread').forEach(el => {
                el.classList.remove('is-unread');
                el.classList.add('is-seen');
                const dot = el.querySelector('.unread-dot');
                if (dot) dot.remove();
                const trailTag = el.querySelector('.seen-trail-tag');
                if (trailTag) trailTag.classList.remove('d-none');
            });
            const sub = document.getElementById('notificationSubtitle');
            if (sub) sub.textContent = 'All alerts marked as seen (in trail)';
        }
    } catch (e) {
        console.error('Failed to mark notifications seen', e);
    }
};

window.dismissNotification = async function(event, key) {
    if (event) {
        event.stopPropagation();
        event.preventDefault();
    }
    try {
        const appUrl = window.APP_URL || '';
        const res = await fetch(appUrl + '/api/notifications/dismiss', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: '_csrf_token=' + encodeURIComponent(window.CSRF_TOKEN || '') + '&key=' + encodeURIComponent(key)
        });
        const data = await res.json();
        if (data.success) {
            const item = document.getElementById('notif_' + key);
            if (item) {
                item.style.transition = 'all 0.2s ease';
                item.style.opacity = '0';
                item.style.transform = 'translateX(20px)';
                setTimeout(() => item.remove(), 220);
            }
        }
    } catch (e) {
        console.error('Failed to dismiss notification', e);
    }
};

window.dismissMobileAlert = function(key) {
    const el = document.getElementById('mobileAlertPopup');
    if (el) el.remove();
    if (key) {
        sessionStorage.setItem('dismissed_alert_' + key, '1');
        if (typeof window.dismissNotification === 'function') {
            window.dismissNotification(null, key);
        }
    }
};
