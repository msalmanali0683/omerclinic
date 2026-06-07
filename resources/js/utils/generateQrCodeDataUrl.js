import QRCode from 'qrcode';

export async function generateQrCodeDataUrl(text, options = {}) {
  if (!text) {
    return '';
  }

  return QRCode.toDataURL(text, {
    errorCorrectionLevel: 'M',
    margin: 1,
    width: 120,
    ...options,
  });
}

export function waitForBillQrCode(elementId, timeoutMs = 3000) {
  return new Promise((resolve) => {
    const deadline = Date.now() + timeoutMs;

    const check = () => {
      const root = document.getElementById(elementId);
      const img = root?.querySelector('.lab-bill-qr-code');

      if (img?.getAttribute('data-qr-ready') === 'true' && img.src) {
        resolve();
        return;
      }

      if (Date.now() >= deadline) {
        resolve();
        return;
      }

      requestAnimationFrame(check);
    };

    check();
  });
}
