// Service Worker Registration and Setup for CineShelf PWA
(function() {
    // Service Worker Registration using a proper file
    if ('serviceWorker' in navigator) {
        // First create the service worker content as a separate file
        const swFileName = 'cineshelf-sw.js';
        
        navigator.serviceWorker.register(swFileName)
            .then(registration => {
                console.log('CineShelf: Service Worker registered successfully:', registration);
                
                // Check for updates
                registration.addEventListener('updatefound', () => {
                    const newWorker = registration.installing;
                    newWorker.addEventListener('statechange', () => {
                        if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                            // New content is available, refresh the page
                            if (confirm('CineShelf has been updated! Refresh to get the latest version?')) {
                                window.location.reload();
                            }
                        }
                    });
                });
            })
            .catch(error => {
                console.log('CineShelf: Service Worker registration failed, but app will still work:', error);
                // Don't show error to user - app works fine without service worker
            });

        // Listen for messages from service worker
        navigator.serviceWorker.addEventListener('message', event => {
            if (event.data && event.data.type === 'CACHE_UPDATED') {
                console.log('CineShelf: Cache updated');
            }
        });
    }

    // Install prompt handling
    let deferredPrompt;
    
    window.addEventListener('beforeinstallprompt', (e) => {
        console.log('CineShelf: Install prompt triggered');
        e.preventDefault();
        deferredPrompt = e;
        
        // Show custom install button or banner
        showInstallBanner();
    });

    window.addEventListener('appinstalled', (evt) => {
        console.log('CineShelf: App was installed');
        hideInstallBanner();
    });

    function showInstallBanner() {
        // Create install banner if it doesn't exist
        let banner = document.getElementById('install-banner');
        if (!banner) {
            banner = document.createElement('div');
            banner.id = 'install-banner';
            banner.style.cssText = `
                position: fixed;
                bottom: 20px;
                left: 50%;
                transform: translateX(-50%);
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 12px 20px;
                border-radius: 25px;
                box-shadow: 0 4px 20px rgba(0,0,0,0.3);
                z-index: 1000;
                font-size: 14px;
                font-weight: 600;
                cursor: pointer;
                backdrop-filter: blur(10px);
                border: 1px solid rgba(255,255,255,0.2);
                animation: slideUp 0.3s ease-out;
                max-width: 320px;
                text-align: center;
            `;
            banner.innerHTML = 'Install CineShelf App';
            
            // Add CSS animation
            const style = document.createElement('style');
            style.textContent = `
                @keyframes slideUp {
                    from { transform: translate(-50%, 100px); opacity: 0; }
                    to { transform: translate(-50%, 0); opacity: 1; }
                }
            `;
            document.head.appendChild(style);
            
            banner.addEventListener('click', installApp);
            document.body.appendChild(banner);
            
            // Auto-hide after 10 seconds
            setTimeout(hideInstallBanner, 10000);
        }
    }

    function hideInstallBanner() {
        const banner = document.getElementById('install-banner');
        if (banner) {
            banner.style.animation = 'slideDown 0.3s ease-out forwards';
            banner.style.cssText += `
                @keyframes slideDown {
                    to { transform: translate(-50%, 100px); opacity: 0; }
                }
            `;
            setTimeout(() => banner.remove(), 300);
        }
    }

    function installApp() {
        if (deferredPrompt) {
            deferredPrompt.prompt();
            deferredPrompt.userChoice.then((result) => {
                if (result.outcome === 'accepted') {
                    console.log('CineShelf: User accepted the install prompt');
                } else {
                    console.log('CineShelf: User dismissed the install prompt');
                }
                deferredPrompt = null;
                hideInstallBanner();
            });
        }
    }

    // Offline detection
    window.addEventListener('online', () => {
        console.log('CineShelf: Back online');
        if (window.App && window.App.showStatus) {
            window.App.showStatus('Back online!', 'success');
        }
    });

    window.addEventListener('offline', () => {
        console.log('CineShelf: Gone offline');
        if (window.App && window.App.showStatus) {
            window.App.showStatus('Offline mode - some features may be limited', 'error');
        }
    });

    // Expose install function globally
    window.CineShelfInstall = {
        showInstallBanner,
        hideInstallBanner,
        installApp
    };
})();