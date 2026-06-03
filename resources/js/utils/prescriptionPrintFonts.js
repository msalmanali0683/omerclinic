export const PRESCRIPTION_PRINT_FONT_FACE_NAME = 'Jameel Noori Nastaleeq Kasheeda';

export const PRESCRIPTION_PRINT_FONT_FAMILY = `"${PRESCRIPTION_PRINT_FONT_FACE_NAME}", "Jameel Noori Nastaleeq", "Noto Nastaliq Urdu", serif`;

const LOCAL_FONT_FILES = [
    { file: 'JameelNooriNastaleeqKasheeda.woff2', format: 'woff2' },
    { file: 'JameelNooriNastaleeqKasheeda.ttf', format: 'truetype' },
];

const CDN_FONT_URL = 'https://cdn.jsdelivr.net/npm/jameel-noori-nastaliq-kasheeda@1.1.0/fonts/JameelNooriNastaliqKasheeda3.woff2';

export function resolvePrescriptionPrintFontBaseUrl(baseUrl = '') {
    if (baseUrl) {
        return baseUrl.replace(/\/$/, '');
    }

    if (typeof window !== 'undefined' && window.location?.origin) {
        return window.location.origin;
    }

    return '';
}

export function buildPrescriptionPrintFontFaceCss(baseUrl = '') {
    const origin = resolvePrescriptionPrintFontBaseUrl(baseUrl);
    const hostedSources = LOCAL_FONT_FILES
        .map(({ file, format }) => `url("${origin}/fonts/${file}") format("${format}")`)
        .join(',\n           ');

    return `
        @font-face {
            font-family: "${PRESCRIPTION_PRINT_FONT_FACE_NAME}";
            src: local("Jameel Noori Nastaleeq Kasheeda"),
                 local("Jameel Noori Nastaliq Kasheeda"),
                 local("JameelNooriNastaleeqKasheeda"),
                 ${hostedSources},
                 url("${CDN_FONT_URL}") format("woff2");
            font-weight: normal;
            font-style: normal;
            font-display: swap;
            unicode-range: U+0600-06FF, U+0750-077F, U+FB50-FDFF, U+FE70-FEFF;
        }
    `;
}

export const PRESCRIPTION_PRINT_FONT_RENDER_RULES = `
    font-variant-ligatures: normal;
    font-feature-settings: normal;
`;

export async function ensurePrescriptionPrintFontLoaded(documentRef = document, baseUrl = '') {
    if (!documentRef?.fonts?.load) {
        return;
    }

    const styleId = 'prescription-print-font-face';
    if (!documentRef.getElementById(styleId)) {
        const style = documentRef.createElement('style');
        style.id = styleId;
        style.textContent = buildPrescriptionPrintFontFaceCss(baseUrl);
        documentRef.head.appendChild(style);
    }

    try {
        await documentRef.fonts.load(`12pt ${PRESCRIPTION_PRINT_FONT_FAMILY}`);
        await documentRef.fonts.ready;
    } catch {
        // Continue printing with fallback fonts if custom font fails to load.
    }
}
