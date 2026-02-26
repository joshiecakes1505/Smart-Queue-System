if (typeof window !== 'undefined') {
    if (!window.qzReady) {
        window.qzReady = new Promise((resolve, reject) => {
            if (window.qz) {
                resolve(window.qz);
                return;
            }

            const existingScript = document.querySelector('script[data-qz-tray="true"]');
            if (existingScript) {
                existingScript.addEventListener('load', () => resolve(window.qz));
                existingScript.addEventListener('error', () => reject(new Error('Failed to load QZ Tray library.')));
                return;
            }

            const script = document.createElement('script');
            script.src = 'https://unpkg.com/qz-tray/qz-tray.js';
            script.async = true;
            script.dataset.qzTray = 'true';
            script.onload = () => resolve(window.qz);
            script.onerror = () => reject(new Error('Failed to load QZ Tray library.'));
            document.head.appendChild(script);
        });
    }
}