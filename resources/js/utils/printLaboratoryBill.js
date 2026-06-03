import { buildLaboratoryBillPrintStyles } from '@/utils/laboratoryBillPrintStyles';

function waitForIframe(iframe) {
    return new Promise((resolve) => {
        iframe.onload = () => resolve();
        setTimeout(resolve, 300);
    });
}

export async function printLaboratoryBillElement(elementId = 'laboratory-test-bill-print-area') {
    const printElement = document.getElementById(elementId);
    if (!printElement) {
        throw new Error('Bill print area not found.');
    }

    const iframe = document.createElement('iframe');
    iframe.style.cssText = 'position:fixed;right:0;bottom:0;width:0;height:0;border:0';
    iframe.setAttribute('aria-hidden', 'true');
    document.body.appendChild(iframe);

    const doc = iframe.contentWindow?.document;
    if (!doc) {
        document.body.removeChild(iframe);
        throw new Error('Unable to create print frame.');
    }

    const styles = buildLaboratoryBillPrintStyles();

    doc.open();
    doc.write(`<!doctype html>
<html>
  <head>
    <meta charset="utf-8" />
    <title>Laboratory Bill</title>
    <style>${styles}</style>
  </head>
  <body>${printElement.outerHTML}</body>
</html>`);
    doc.close();

    await waitForIframe(iframe);

    iframe.contentWindow?.focus();
    iframe.contentWindow?.print();

    setTimeout(() => {
        if (iframe.parentNode) {
            iframe.parentNode.removeChild(iframe);
        }
    }, 1500);
}
