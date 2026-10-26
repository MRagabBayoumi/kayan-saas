/**
 * Kayan ERP — Barcode Camera Scanner Module
 * Live camera barcode detection via Web API with polyfill fallback and image parsing
 */

let kayanScannerStream = null;
let currentScannerTargetInput = null;
let isKayanScanningActive = false;
let scannerFacingMode = 'environment';
let barcodeDetectorInstance = null;
let polyfillPromise = null;

async function ensureBarcodeDetector() {
    if ('BarcodeDetector' in window) {
        return;
    }

    if (polyfillPromise) {
        return polyfillPromise;
    }

    polyfillPromise = (async () => {
        try {
            if (!window.zbarWasm) {
                await loadScript('/vendor/barcode/zbar.js');
            }
            if (!window.barcodeDetectorPolyfill) {
                await loadScript('/vendor/barcode/barcode-detector.js');
            }
            if (window.barcodeDetectorPolyfill?.BarcodeDetectorPolyfill) {
                window.BarcodeDetector = window.barcodeDetectorPolyfill.BarcodeDetectorPolyfill;
            }
        } catch (e) {
            console.warn('BarcodeDetector polyfill could not be loaded:', e);
        }
    })();

    return polyfillPromise;
}

function loadScript(src) {
    return new Promise((resolve, reject) => {
        const existing = document.querySelector(`script[src="${src}"]`);
        if (existing) {
            resolve();
            return;
        }
        const script = document.createElement('script');
        script.src = src;
        script.onload = () => resolve();
        script.onerror = (err) => reject(err);
        document.head.appendChild(script);
    });
}

export async function initBarcodeScanner() {
    if (window.__barcodeScannerInitialized) return;
    window.__barcodeScannerInitialized = true;

    const modal = document.getElementById('kayanBarcodeScannerModal');
    if (!modal) return;

    // Preload polyfill if native detector is missing
    ensureBarcodeDetector().then(createDetectorInstance);

    // Event delegation for opening scanner
    document.addEventListener('click', (e) => {
        const trigger = e.target.closest('[data-action="open-barcode-scanner"]');
        if (trigger) {
            e.preventDefault();
            const targetId = trigger.getAttribute('data-target-input') || 'productBarcode';
            openKayanBarcodeScanner(targetId);
        }

        const closeBtn = e.target.closest('[data-action="close-barcode-scanner"]');
        if (closeBtn) {
            e.preventDefault();
            closeKayanBarcodeScanner();
        }

        const switchBtn = e.target.closest('[data-action="switch-scanner-camera"]');
        if (switchBtn) {
            e.preventDefault();
            switchScannerCamera();
        }
    });

    // Close on backdrop click
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            closeKayanBarcodeScanner();
        }
    });

    // Close on Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal.classList.contains('open')) {
            closeKayanBarcodeScanner();
        }
    });

    // File input fallback change event
    const fileInput = document.getElementById('barcodeFileCapture');
    if (fileInput) {
        fileInput.addEventListener('change', () => {
            handleBarcodeImageFile(fileInput);
        });
    }

    // Expose window functions for compatibility if needed
    window.openKayanBarcodeScanner = openKayanBarcodeScanner;
    window.closeKayanBarcodeScanner = closeKayanBarcodeScanner;
    window.switchScannerCamera = switchScannerCamera;
}

function createDetectorInstance() {
    if ('BarcodeDetector' in window && !barcodeDetectorInstance) {
        try {
            barcodeDetectorInstance = new BarcodeDetector({
                formats: ['ean_13', 'code_128', 'qr_code', 'upc_a', 'upc_e', 'code_39', 'ean_8']
            });
        } catch (e) {
            try {
                barcodeDetectorInstance = new BarcodeDetector();
            } catch (err) {
                barcodeDetectorInstance = null;
            }
        }
    }
}

export async function openKayanBarcodeScanner(targetInputId) {
    currentScannerTargetInput = document.getElementById(targetInputId) 
        || document.getElementById('quickProductInput')
        || document.getElementById('productBarcode') 
        || document.getElementById('inventorySearchInput');
    const modal = document.getElementById('kayanBarcodeScannerModal');
    const errorNotice = document.getElementById('scannerErrorMessage');

    if (!modal) return;

    if (errorNotice) {
        errorNotice.style.display = 'none';
        errorNotice.innerHTML = '';
    }

    modal.classList.add('open');
    modal.setAttribute('aria-hidden', 'false');

    await startCameraStream();
}

export function closeKayanBarcodeScanner() {
    const modal = document.getElementById('kayanBarcodeScannerModal');
    if (modal) {
        modal.classList.remove('open');
        modal.setAttribute('aria-hidden', 'true');
    }

    isKayanScanningActive = false;

    if (kayanScannerStream) {
        kayanScannerStream.getTracks().forEach(track => track.stop());
        kayanScannerStream = null;
    }

    const video = document.getElementById('kayanBarcodeVideo');
    if (video) {
        video.srcObject = null;
    }
}

async function startCameraStream() {
    const video = document.getElementById('kayanBarcodeVideo');
    const errorNotice = document.getElementById('scannerErrorMessage');
    const statusBadge = document.getElementById('scannerStatusBadge');
    const snapBtn = document.getElementById('barcodeSnapBtn');

    if (!video) return;

    await ensureBarcodeDetector();
    createDetectorInstance();

    // Ensure mobile Safari inline playback
    video.setAttribute('playsinline', '');
    video.setAttribute('webkit-playsinline', '');
    video.playsInline = true;
    video.muted = true;

    if (kayanScannerStream) {
        kayanScannerStream.getTracks().forEach(track => track.stop());
    }

    const isLocalhost = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
    const isSecure = window.isSecureContext || window.location.protocol === 'https:' || isLocalhost;

    // Check if browser completely disables getUserMedia (e.g. mobile over HTTP)
    if (!isSecure && (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia)) {
        isKayanScanningActive = false;
        if (errorNotice) {
            errorNotice.style.display = 'block';
            errorNotice.innerHTML = `
                <div class="p-3 text-start bg-amber-500/10 border border-amber-500/30 rounded-lg text-xs leading-relaxed space-y-1.5">
                    <div class="font-bold text-amber-700 dark:text-amber-300 flex items-center gap-1.5">
                        <span>⚠️ تنبيه لأجهزة الموبايل (اتصال غير مشفر):</span>
                    </div>
                    <div class="text-[var(--text-secondary)]">
                        متصفح الموبايل يحظر تشغيل كاميرا الفيديو المباشرة عبر بروتوكول HTTP العادي.<br>
                        <strong>الحل الفوري:</strong> اضغط على زر <strong>"التقاط صورة للباركود (كاميرا الهاتف)"</strong> أدناه لتصوير الباركود بكاميرا هاتفك وسيتعرف عليه النظام فوراً.<br>
                        <span class="text-[11px] opacity-80">• لتفعيل المسح المباشر بالفيديو من الموبايل: شغّل ملف <code class="bg-black/10 dark:bg-white/10 px-1 py-0.5 rounded">start-mobile-https.bat</code> على السيرفر وادخل عبر رابط HTTPS.</span>
                    </div>
                </div>
            `;
        }
        if (snapBtn) {
            snapBtn.classList.remove('erp-btn-secondary');
            snapBtn.classList.add('erp-btn-primary');
        }
        if (statusBadge) statusBadge.textContent = 'استخدم زر التقاط الصورة بكاميرا الهاتف أدناه';
        return;
    }

    const constraintsVariants = [
        {
            video: {
                facingMode: { ideal: scannerFacingMode },
                width: { ideal: 1280 },
                height: { ideal: 720 }
            },
            audio: false
        },
        {
            video: {
                facingMode: { ideal: scannerFacingMode }
            },
            audio: false
        },
        {
            video: {
                facingMode: scannerFacingMode
            },
            audio: false
        },
        {
            video: true,
            audio: false
        }
    ];

    let stream = null;
    let lastError = null;

    for (const c of constraintsVariants) {
        try {
            if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
                stream = await navigator.mediaDevices.getUserMedia(c);
                if (stream) break;
            }
        } catch (err) {
            lastError = err;
        }
    }

    if (!stream) {
        isKayanScanningActive = false;
        if (errorNotice) {
            errorNotice.style.display = 'block';
            let msg = 'تعذر تشغيل الكاميرا: ';
            if (lastError?.name === 'NotAllowedError' || lastError?.name === 'PermissionDeniedError') {
                msg += 'تم رفض إذن الوصول للكاميرا. يرجى السماح بالوصول من إعدادات المتصفح.';
            } else if (lastError?.name === 'NotFoundError' || lastError?.name === 'DevicesNotFoundError') {
                msg += 'لم يتم العثور على كاميرا متصلة بالجهاز.';
            } else {
                msg += (lastError?.message || 'خطأ غير متوقع. يرجى استخدام زر التقاط الصورة أدناه.');
            }
            errorNotice.textContent = msg;
        }
        if (snapBtn) {
            snapBtn.classList.remove('erp-btn-secondary');
            snapBtn.classList.add('erp-btn-primary');
        }
        return;
    }

    try {
        kayanScannerStream = stream;
        video.srcObject = kayanScannerStream;
        await video.play();

        isKayanScanningActive = true;
        if (statusBadge) statusBadge.textContent = 'وجّه الكاميرا نحو باركود الصنف';

        requestAnimationFrame(scanVideoFrame);
    } catch (err) {
        isKayanScanningActive = false;
        console.warn('Video playback error:', err);
    }
}

export async function switchScannerCamera() {
    scannerFacingMode = (scannerFacingMode === 'environment') ? 'user' : 'environment';
    await startCameraStream();
}

async function scanVideoFrame() {
    if (!isKayanScanningActive) return;

    const video = document.getElementById('kayanBarcodeVideo');
    if (!video || video.readyState < 2 || video.videoWidth === 0) {
        requestAnimationFrame(scanVideoFrame);
        return;
    }

    if (barcodeDetectorInstance) {
        try {
            const barcodes = await barcodeDetectorInstance.detect(video);
            if (barcodes.length > 0 && barcodes[0].rawValue) {
                const detectedCode = barcodes[0].rawValue.trim();
                if (detectedCode) {
                    handleBarcodeDetected(detectedCode);
                    return;
                }
            }
        } catch (e) {
            // Frame detection error, continue next frame
        }
    }

    if (isKayanScanningActive) {
        requestAnimationFrame(scanVideoFrame);
    }
}

function handleBarcodeDetected(code) {
    if (!code) return;

    isKayanScanningActive = false;
    playScannerBeep();

    const reticle = document.getElementById('scannerReticle');
    if (reticle) reticle.classList.add('erp-scanner-active-border');

    const statusBadge = document.getElementById('scannerStatusBadge');
    if (statusBadge) statusBadge.textContent = 'تم المسح بنجاح: ' + code;

    setTimeout(() => {
        if (currentScannerTargetInput) {
            currentScannerTargetInput.value = code;
            currentScannerTargetInput.dispatchEvent(new Event('input', { bubbles: true }));
            currentScannerTargetInput.dispatchEvent(new Event('change', { bubbles: true }));

            // Dispatch Enter key for fast POS auto-scan/submit
            currentScannerTargetInput.dispatchEvent(new KeyboardEvent('keydown', {
                key: 'Enter',
                code: 'Enter',
                keyCode: 13,
                which: 13,
                bubbles: true,
                cancelable: true
            }));

            // If the target input is inside a search form, auto submit
            if (currentScannerTargetInput.form && currentScannerTargetInput.type === 'search') {
                currentScannerTargetInput.form.submit();
            }
        }

        closeKayanBarcodeScanner();
        if (reticle) reticle.classList.remove('erp-scanner-active-border');
    }, 450);
}

function playScannerBeep() {
    try {
        const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = audioCtx.createOscillator();
        const gain = audioCtx.createGain();

        osc.type = 'sine';
        osc.frequency.setValueAtTime(1200, audioCtx.currentTime);
        gain.gain.setValueAtTime(0.2, audioCtx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.15);

        osc.connect(gain);
        gain.connect(audioCtx.destination);

        osc.start();
        osc.stop(audioCtx.currentTime + 0.15);
    } catch (e) {}
}

export async function handleBarcodeImageFile(fileInput) {
    if (!fileInput.files || fileInput.files.length === 0) return;
    const file = fileInput.files[0];

    const statusBadge = document.getElementById('scannerStatusBadge');
    if (statusBadge) statusBadge.textContent = 'جاري تحليل صورة الباركود...';

    await ensureBarcodeDetector();
    createDetectorInstance();

    const img = new Image();
    img.src = URL.createObjectURL(file);
    img.onload = async () => {
        if (barcodeDetectorInstance) {
            try {
                const barcodes = await barcodeDetectorInstance.detect(img);
                if (barcodes.length > 0 && barcodes[0].rawValue) {
                    handleBarcodeDetected(barcodes[0].rawValue.trim());
                    return;
                }
            } catch (e) {
                console.warn('Image detection failed:', e);
            }
        }

        const errorNotice = document.getElementById('scannerErrorMessage');
        if (errorNotice) {
            errorNotice.style.display = 'block';
            errorNotice.textContent = 'تعذر قراءة باركود واضح من الصورة. يرجى تجربة صورة أوضح أو إدخال الرقم يدوياً.';
        }
        if (statusBadge) statusBadge.textContent = 'فشل التعرف على الباركود من الصورة';
    };
}
