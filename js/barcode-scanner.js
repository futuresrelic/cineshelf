// Barcode Scanner Integration for CineShelf
window.BarcodeScanner = (function() {
    let isScanning = false;

    function init() {
        document.getElementById('scanUpcBtn').addEventListener('click', open);
        
        document.getElementById('scannerModal').addEventListener('click', function(e) {
            if (e.target === this) {
                close();
            }
        });
    }

    function open() {
        const modal = document.getElementById('scannerModal');
        modal.classList.add('active');
        startCamera();
    }

    function close() {
        const modal = document.getElementById('scannerModal');
        modal.classList.remove('active');
        stopCamera();
    }

    async function startCamera() {
        if (isScanning) return;

        try {
            const scanner = document.getElementById('barcodeScanner');
            const overlay = document.getElementById('scannerOverlay');
            const placeholder = document.getElementById('scannerPlaceholder');

            const devices = await navigator.mediaDevices.enumerateDevices();
            const videoDevices = devices.filter(device => device.kind === 'videoinput');
            
            if (videoDevices.length === 0) {
                alert('No camera found on this device');
                return;
            }

            const config = {
                inputStream: {
                    name: "Live",
                    type: "LiveStream",
                    target: scanner,
                    constraints: {
                        width: { min: 480 },
                        height: { min: 320 },
                        facingMode: "environment"
                    }
                },
                decoder: {
                    readers: [
                        "ean_reader",
                        "ean_8_reader",
                        "code_128_reader",
                        "code_39_reader",
                        "upc_reader",
                        "upc_e_reader"
                    ]
                },
                locate: true,
                locator: {
                    patchSize: "medium",
                    halfSample: true
                },
                numOfWorkers: 2,
                frequency: 10,
                debug: false
            };

            await new Promise((resolve, reject) => {
                Quagga.init(config, (err) => {
                    if (err) {
                        reject(err);
                    } else {
                        resolve();
                    }
                });
            });

            Quagga.onDetected((result) => {
                const code = result.codeResult.code;
                if (code && code.length >= 8) {
                    onBarcodeDetected(code);
                }
            });

            Quagga.start();
            isScanning = true;
            
            overlay.style.display = 'block';
            placeholder.style.display = 'none';

        } catch (error) {
            console.error('Camera error:', error);
            alert(`Camera error: ${error.message}`);
            close();
        }
    }

    function stopCamera() {
        if (isScanning) {
            Quagga.stop();
            isScanning = false;
        }

        const overlay = document.getElementById('scannerOverlay');
        const placeholder = document.getElementById('scannerPlaceholder');
        
        overlay.style.display = 'none';
        placeholder.style.display = 'block';
    }

    function onBarcodeDetected(code) {
        document.getElementById('upc').value = code;
        
        playBeep();
        
        if (navigator.vibrate) {
            navigator.vibrate(100);
        }
        
        if (window.App && window.App.showStatus) {
            window.App.showStatus(`Barcode scanned: ${code}`, 'success');
        }
        
        setTimeout(() => {
            close();
        }, 500);
    }

    function playBeep() {
        try {
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();
            
            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);
            
            oscillator.frequency.setValueAtTime(800, audioContext.currentTime);
            oscillator.type = 'sine';
            
            gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.2);
            
            oscillator.start(audioContext.currentTime);
            oscillator.stop(audioContext.currentTime + 0.2);
            
        } catch (error) {
            console.log('Audio not supported:', error);
        }
    }

    return {
        init,
        open,
        close
    };
})();